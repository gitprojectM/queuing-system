<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\QueueStep;
use App\Models\Window;
use Illuminate\Http\Request;
use App\Events\QueueUpdated;

class MultiServiceQueueController extends Controller
{
    /**
     * Create a queue with multiple ordered services (one ticket, many steps).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
        ]);

        $today = now()->toDateString();
        // Auto-complete any queues from previous days so numbering and views reset
        Queue::whereDate('queue_date', '<', $today)
            ->whereIn('status', ['waiting', 'assigned'])
            ->update([
                'status' => 'completed',
                'window_id' => null,
            ]);
        // Also clear windows that are still pointing at old-day clients
        Window::whereHas('currentClient', function ($q) use ($today) {
                $q->whereDate('queue_date', '<', $today);
            })
            ->update(['current_client_id' => null]);

        // Use global daily numbering (not per service) for multi-service tickets
        $lastQueue = Queue::whereDate('queue_date', $today)->orderByDesc('queue_number')->first();
        $nextQueueNumber = $lastQueue ? $lastQueue->queue_number + 1 : 1;

        $userId = auth()->check() ? auth()->id() : null;
        $firstServiceId = $validated['service_ids'][0];

        $queue = Queue::create([
            'name' => $validated['name'],
            'user_id' => $userId,
            'service_id' => $firstServiceId,
            'window_id' => null,
            'queue_number' => $nextQueueNumber,
            'status' => 'waiting',
            'queue_date' => $today,
        ]);

        foreach ($validated['service_ids'] as $index => $serviceId) {
            QueueStep::create([
                'queue_id' => $queue->id,
                'service_id' => $serviceId,
                'step_order' => $index + 1,
                'status' => $index === 0 ? 'waiting' : 'waiting',
            ]);
        }

        event(new QueueUpdated($queue));

        // Behave like the single-service register: redirect to ticket page
        return redirect()->route('queue.ticket', ['queue' => $queue->id]);
    }
}
