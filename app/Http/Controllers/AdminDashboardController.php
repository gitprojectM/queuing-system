<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\Service;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $services = Service::with([
            'windows.users',
            'users',
            'queues' => fn($q) => $q->where('status', 'waiting'),
        ])->get();

        $todayStats = [
            'total'     => Queue::whereDate('queue_date', $today)->count(),
            'waiting'   => Queue::whereDate('queue_date', $today)->where('status', 'waiting')->count(),
            'serving'   => Queue::whereDate('queue_date', $today)->where('status', 'assigned')->count(),
            'completed' => Queue::whereDate('queue_date', $today)->where('status', 'completed')->count(),
        ];

        return Inertia::render('Dashboard', [
            'services'   => $services,
            'todayStats' => $todayStats,
        ]);
    }
}
