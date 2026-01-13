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
    public function completeQueue(Request $request)
    {
        $window = Window::where('id', $request->window_id)->first();
        if (!$window) {
            return redirect()->back()->with('error', 'Window not found.');
        }
        // Find the assigned queue for this window
        $queue = Queue::where('window_id', $window->id)
            ->where('status', 'assigned')
            ->orderByDesc('created_at')
            ->first();
        if ($queue) {
            // Try to complete current step and advance to next service if defined
            $advanced = false;
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
                    // Move queue to next service, keep same number and mark as waiting
                    $queue->service_id = $nextStep->service_id;
                    $queue->status = 'waiting';
                    $queue->window_id = null;
                    $queue->save();
                    $advanced = true;
                }
            }

            if (!$advanced) {
                // No further steps: mark entire queue as completed
                $queue->status = 'completed';
                $queue->save();
            }
            event(new QueueUpdated($queue));
        }
        $window->current_client_id = null;
        $window->save();
        event(new WindowUpdated($window));
        return redirect()->back()->with('success', 'Window marked as idle.');
    }
    public function nextQueue(Request $request)
    {
        \Log::info('DEBUG: nextQueue endpoint hit', [
            'user_id' => auth()->id(),
            'window_id' => $request->window_id ?? null,
            'request_data' => $request->all()
        ]);
        $user = auth()->user();
        if (!$user) {
            return redirect()->back()->with('error', 'Not authenticated.');
        }

        // Use window_id from request if provided and belongs to user, else fallback
        $window = null;
        if ($request->window_id) {
            $window = $user->windows()->where('windows.id', $request->window_id)->first();
            // If window is busy, complete the current client first
            if ($window && $window->current_client_id !== null) {
                $currentQueue = \App\Models\Queue::find($window->current_client_id);
                if ($currentQueue && $currentQueue->status !== 'completed') {
                    // Reuse the same multi-step advancement logic as completeQueue
                    $advanced = false;
                    $currentStep = QueueStep::where('queue_id', $currentQueue->id)
                        ->where('service_id', $currentQueue->service_id)
                        ->whereIn('status', ['waiting', 'assigned'])
                        ->orderBy('step_order')
                        ->first();
                    if ($currentStep) {
                        $currentStep->status = 'completed';
                        $currentStep->completed_at = now();
                        $currentStep->save();

                        $nextStep = QueueStep::where('queue_id', $currentQueue->id)
                            ->where('step_order', '>', $currentStep->step_order)
                            ->orderBy('step_order')
                            ->first();
                        if ($nextStep) {
                            $nextStep->status = 'waiting';
                            $nextStep->save();
                            $currentQueue->service_id = $nextStep->service_id;
                            $currentQueue->status = 'waiting';
                            $currentQueue->window_id = null;
                            $currentQueue->save();
                            $advanced = true;
                        }
                    }

                    if (! $advanced) {
                        $currentQueue->status = 'completed';
                        $currentQueue->save();
                    }

                    \Log::info('DEBUG: nextQueue - Auto-completed/advanced busy queue', ['queue_id' => $currentQueue->id, 'advanced' => $advanced]);
                    event(new \App\Events\QueueUpdated($currentQueue));
                }
                $window->current_client_id = null;
                $window->save();
                event(new \App\Events\WindowUpdated($window));
            }
        }
        if (!$window) {
            $window = $user->windows()->first();
        }
        if (!$window) {
            \Log::info('DEBUG: nextQueue - No available window assigned', [
                'user_id' => $user->id,
                'requested_window_id' => $request->window_id,
                'user_window_ids' => $user->windows()->pluck('windows.id')->toArray()
            ]);
            return redirect()->back()->with('error', 'No available window assigned to you.');
        }

        $serviceIds = $window->services()->pluck('services.id');
        $queue = Queue::where('status', 'waiting')
            ->whereIn('service_id', $serviceIds)
            ->orderBy('queue_number')
            ->first();

        if (!$queue) {
            \Log::info('DEBUG: nextQueue - No waiting client found', [
                'window_id' => $window->id,
                'service_ids' => $serviceIds,
                'user_id' => $user->id
            ]);
            return redirect()->back()->with('error', 'No waiting client for your window.');
        }

        // Always update window_id and status
        $queue->window_id = $window->id;
        $queue->status = 'assigned';
        $queue->save();
        $window->current_client_id = $queue->id;
        $window->save();

        \Log::info('DEBUG: nextQueue - Assigned client', [
            'queue_id' => $queue->id,
            'window_id' => $window->id,
            'queue_status' => $queue->status,
            'queue_name' => $queue->name
        ]);
        \Log::info('NextQueue: Updated queue', $queue->toArray());
        \Log::info('NextQueue: Updated window', $window->toArray());

    \Log::info('DEBUG: Firing QueueUpdated event', ['queue_id' => $queue->id]);
    event(new \App\Events\QueueUpdated($queue));
    \Log::info('DEBUG: Firing WindowUpdated event', ['window_id' => $window->id]);
    event(new \App\Events\WindowUpdated($window));

        return redirect()->back()->with('success', 'Next client assigned to your window ' . $window->name . '.');
    }
    public function list()
    {
        $queues = Queue::with(['service', 'window'])->orderBy('created_at', 'desc')->get();
        return \Inertia\Inertia::render('QueueList', [
            'queues' => $queues,
        ]);
    }
    public function monitor()
    {
        $user = auth()->user();
        $windowIds = $user ? $user->windows()->pluck('windows.id') : [];
        $serviceIds = $user ? $user->services()->pluck('services.id') : [];
        $queues = Queue::with(['service', 'window'])
            ->where(function($q) use ($windowIds, $serviceIds) {
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
        // Only windows that are not occupied (no current client assigned)
        $windows = Window::with('services')
            ->whereNull('current_client_id')
            ->get();
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

        // Always create as waiting, never assign a window on registration
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

        // Use global daily numbering (same as multi-service) so numbers don't reset per service
        $lastQueue = Queue::whereDate('queue_date', $today)
            ->orderByDesc('queue_number')
            ->first();
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
        // Ensure there is at least one step for this queue (single-service visit)
        QueueStep::firstOrCreate(
            [
                'queue_id' => $queue->id,
                'service_id' => $queue->service_id,
                'step_order' => 1,
            ],
            [
                'status' => 'waiting',
            ]
        );
        \Log::info('Register: Waiting queue', $queue->toArray());
        \Log::info('Broadcasting QueueUpdated', $queue->toArray());
        event(new \App\Events\QueueUpdated($queue));
        // Redirect to ticket page
        return redirect()->route('queue.ticket', ['queue' => $queue->id]);
    }

    public function ticket($queueId)
    {
        $queue = Queue::with(['service', 'window'])->findOrFail($queueId);
        return Inertia::render('QueueTicket', [
            'queue' => $queue,
        ]);
    }
    }
