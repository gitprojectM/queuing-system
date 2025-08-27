<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Window;

class UserDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        // Get all windows assigned to this user, with current client and waiting queues
        $windows = $user->windows()->with(['currentClient', 'waitingQueues' => function($q) {
            $q->orderBy('queue_number');
        }])->get()->map(function ($window) {
            return $window->toArray();
        });
        // Get all services associated with the user, with waiting queues
        $services = $user->services()->with(['queues' => function($q) {
            $q->where('status', 'waiting')->orderBy('queue_number');
        }])->get()->map(function ($service) {
            return $service->toArray();
        });
        return Inertia::render('UserDashboard', [
            'windows' => $windows,
            'services' => $services,
            'userName' => $user->name,
        ]);
    }
}
