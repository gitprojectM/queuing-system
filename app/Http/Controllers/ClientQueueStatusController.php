<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientQueueStatusController extends Controller
{
    public function show(Request $request, Queue $queue)
    {
        $queue->load(['service', 'window', 'steps.service']);

        $ahead = 0;
        if ($queue->status === 'waiting') {
            $ahead = Queue::where('service_id', $queue->service_id)
                ->where('status', 'waiting')
                ->where('queue_number', '<', $queue->queue_number)
                ->count();
        }

        return Inertia::render('ClientQueueStatus', [
            'queue' => $queue,
            'ahead' => $ahead,
        ]);
    }

    /**
     * Polling endpoint — returns fresh queue status as JSON.
     */
    public function data(Queue $queue)
    {
        $queue->load(['service', 'window', 'steps.service']);

        $ahead = 0;
        if ($queue->status === 'waiting') {
            $ahead = Queue::where('service_id', $queue->service_id)
                ->where('status', 'waiting')
                ->where('queue_number', '<', $queue->queue_number)
                ->count();
        }

        return response()->json([
            'queue' => $queue,
            'ahead' => $ahead,
        ]);
    }

    /**
     * Allow a client to cancel their own queue entry via a simple token check.
     */
    public function cancel(Request $request, Queue $queue)
    {
        if (!in_array($queue->status, ['waiting', 'assigned'])) {
            return redirect()->back()->with('error', 'This queue entry cannot be cancelled.');
        }

        $queue->update(['status' => 'cancelled', 'window_id' => null]);

        if ($queue->window_id) {
            $window = $queue->window;
            if ($window && $window->current_client_id === $queue->id) {
                $window->update(['current_client_id' => null]);
            }
        }

        event(new \App\Events\QueueUpdated($queue));

        return redirect()->route('queue.register')->with('success', 'Your queue entry has been cancelled.');
    }
}
