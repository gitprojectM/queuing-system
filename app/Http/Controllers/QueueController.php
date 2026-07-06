<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Window;
use App\Models\Queue;
use App\Models\QueueStep;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Events\QueueUpdated;
use App\Events\WindowUpdated;

class QueueController extends Controller
{
    /**
     * Advance queue to its next step, or mark it completed if no steps remain.
     * Returns true if the queue moved to a next step, false if it was completed.
     */
    private function advanceQueueStep(Queue $queue): bool
    {
        $currentStep = QueueStep::where('queue_id', $queue->id)
            ->where('service_id', $queue->service_id)
            ->whereIn('status', ['waiting', 'assigned'])
            ->orderBy('step_order')
            ->first();

        if ($currentStep) {
            $currentStep->status = 'completed';
            $currentStep->completed_at = now();
            $currentStep->save();

            $nextStep = QueueStep::where('queue_id', $queue->id)
                ->where('step_order', '>', $currentStep->step_order)
                ->orderBy('step_order')
                ->first();

            if ($nextStep) {
                $nextStep->status = 'waiting';
                $nextStep->save();
                $queue->service_id = $nextStep->service_id;
                $queue->status = 'waiting';
                $queue->window_id = null;
                $queue->save();
                return true;
            }
        }

        $queue->status = 'completed';
        $queue->save();
        return false;
    }

    public function completeQueue(Request $request)
    {
        $window = Window::find($request->window_id);
        if (!$window) {
            return redirect()->back()->with('error', 'Window not found.');
        }

        $queue = Queue::where('window_id', $window->id)
            ->where('status', 'assigned')
            ->orderByDesc('created_at')
            ->first();

        if ($queue) {
            $this->advanceQueueStep($queue);
            event(new QueueUpdated($queue));
        }

        $window->current_client_id = null;
        $window->save();
        event(new WindowUpdated($window));

        return redirect()->back()->with('success', 'Window marked as idle.');
    }

    public function nextQueue(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->back()->with('error', 'Not authenticated.');
        }

        $window = null;
        if ($request->window_id) {
            $window = $user->windows()->where('windows.id', $request->window_id)->first();

            if ($window && $window->current_client_id !== null) {
                $currentQueue = Queue::find($window->current_client_id);
                if ($currentQueue && $currentQueue->status !== 'completed') {
                    $this->advanceQueueStep($currentQueue);
                    event(new QueueUpdated($currentQueue));
                }
                $window->current_client_id = null;
                $window->save();
                event(new WindowUpdated($window));
            }
        }

        if (!$window) {
            $window = $user->windows()->first();
        }

        if (!$window) {
            return redirect()->back()->with('error', 'No available window assigned to you.');
        }

        $serviceIds = $window->services()->pluck('services.id');
        $queue = Queue::where('status', 'waiting')
            ->whereIn('service_id', $serviceIds)
            ->orderBy('priority', 'desc')
            ->orderBy('queue_number')
            ->first();

        if (!$queue) {
            return redirect()->back()->with('error', 'No waiting client for your window.');
        }

        $queue->window_id = $window->id;
        $queue->status = 'assigned';
        $queue->save();
        $window->current_client_id = $queue->id;
        $window->save();

        event(new QueueUpdated($queue));
        event(new WindowUpdated($window));

        return redirect()->back()->with('success', 'Next client assigned to your window ' . $window->name . '.');
    }

    public function list()
    {
        $queues = Queue::with(['service', 'window'])->orderBy('created_at', 'desc')->get();
        return Inertia::render('QueueList', [
            'queues' => $queues,
        ]);
    }

    public function monitor()
    {
        $user = auth()->user();
        $windowIds = $user ? $user->windows()->pluck('windows.id') : [];
        $serviceIds = $user ? $user->services()->pluck('services.id') : [];
        $queues = Queue::with(['service', 'window'])
            ->where(function ($q) use ($windowIds, $serviceIds) {
                if (count($windowIds)) {
                    $q->whereIn('window_id', $windowIds);
                }
                if (count($serviceIds)) {
                    $q->orWhereIn('service_id', $serviceIds);
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();
        return Inertia::render('QueueMonitor', [
            'queues' => $queues,
        ]);
    }

    public function showRegisterForm()
    {
        $services = Service::all();
        $windows = Window::with('services')->whereNull('current_client_id')->get();
        return Inertia::render('QueueRegister', [
            'services' => $services,
            'windows' => $windows,
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'service_id' => 'required|exists:services,id',
        ]);

        $today = now()->toDateString();
        $this->resetPreviousDayQueues($today);

        $lastQueue = Queue::whereDate('queue_date', $today)->orderByDesc('queue_number')->first();
        $nextQueueNumber = $lastQueue ? $lastQueue->queue_number + 1 : 1;

        $userId = auth()->check() ? auth()->id() : null;
        $queue = Queue::create([
            'name' => $validated['name'],
            'user_id' => $userId,
            'service_id' => $validated['service_id'],
            'window_id' => null,
            'queue_number' => $nextQueueNumber,
            'status' => 'waiting',
            'queue_date' => $today,
        ]);

        QueueStep::firstOrCreate(
            ['queue_id' => $queue->id, 'service_id' => $queue->service_id, 'step_order' => 1],
            ['status' => 'waiting']
        );

        event(new QueueUpdated($queue));

        return redirect()->route('queue.ticket', ['queue' => $queue->id]);
    }

    public function ticket($queueId)
    {
        $queue = Queue::with(['service', 'window'])->findOrFail($queueId);

        $ahead = Queue::where('service_id', $queue->service_id)
            ->where('status', 'waiting')
            ->where('queue_number', '<', $queue->queue_number)
            ->count();

        return Inertia::render('QueueTicket', [
            'queue' => $queue,
            'ahead' => $ahead,
        ]);
    }

    /**
     * Auto-complete stale queues from previous days and free their windows.
     * Also called from the scheduled command.
     */
    public static function resetPreviousDayQueues(string $today): void
    {
        Queue::whereDate('queue_date', '<', $today)
            ->whereIn('status', ['waiting', 'assigned'])
            ->update(['status' => 'completed', 'window_id' => null]);

        Window::whereHas('currentClient', function ($q) use ($today) {
            $q->whereDate('queue_date', '<', $today);
        })->update(['current_client_id' => null]);
    }
}
