<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Service;
use App\Models\Window;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserListController extends Controller
{
    public function index()
    {
        $users = User::with(['services', 'windows'])->get();
        return Inertia::render('UserList', [
            'users' => $users,
        ]);
    }

    public function edit(User $user)
    {
        $services = Service::all();
        $windows = Window::all();
        $user->load(['services', 'windows']);
        return Inertia::render('UserAssign', [
            'user' => $user,
            'services' => $services,
            'windows' => $windows,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'service_id' => 'nullable|exists:services,id',
            'window_id' => 'nullable|exists:windows,id',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        if (!empty($validated['service_id'])) {
            $user->services()->sync([$validated['service_id']]);
        } else {
            $user->services()->detach();
        }

        if (!empty($validated['window_id'])) {
            $user->windows()->sync([$validated['window_id']]);
        } else {
            $user->windows()->detach();
        }

        return redirect()->route('users.list')->with('success', 'User updated successfully!');
    }
}
