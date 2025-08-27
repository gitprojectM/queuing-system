<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Queue;

class ClientQueueController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $queues = Queue::with(['service', 'window'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($queue) {
                return $queue->toArray();
            });
        return Inertia::render('ClientQueue', [
            'queues' => $queues,
            'userName' => $user->name,
        ]);
    }
}
