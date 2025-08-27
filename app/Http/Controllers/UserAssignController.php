<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Service;
use App\Models\Window;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserAssignController extends Controller
{
    public function create()
    {
        $services = Service::all();
        $windows = Window::all();
        return Inertia::render('UserAssign', [
            'services' => $services,
            'windows' => $windows,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'service_id' => 'required|exists:services,id',
            'window_id' => 'required|exists:windows,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Assign user to service and window (custom logic, e.g. pivot tables or direct assignment)
        // Example: attach to service and window if relationships exist
        if (method_exists($user, 'services')) {
            $user->services()->attach($validated['service_id']);
        }
        if (method_exists($user, 'windows')) {
            $user->windows()->attach($validated['window_id']);
        }

        return redirect()->back()->with('success', 'User added and assigned successfully!');
    }
}
