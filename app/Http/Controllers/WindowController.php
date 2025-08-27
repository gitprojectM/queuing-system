<?php

namespace App\Http\Controllers;

use App\Models\Window;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WindowController extends Controller
{
    public function index()
    {
    $windows = Window::with('services')->get();
    return Inertia::render('Windows', ['windows' => $windows]);
    }

    public function create()
    {
        $services = \App\Models\Service::all();
        return Inertia::render('WindowForm', [
            'services' => $services,
            'selectedServices' => [],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'services' => 'array',
            'services.*' => 'exists:services,id',
        ]);
        $window = Window::create($validated);
        if ($request->has('services')) {
            $window->services()->sync($request->input('services'));
        }
        return redirect()->route('windows.index');
    }

    public function edit(Window $window)
    {
        $services = \App\Models\Service::all();
        $selectedServices = $window->services()->pluck('services.id');
        return Inertia::render('WindowForm', [
            'window' => $window,
            'services' => $services,
            'selectedServices' => $selectedServices,
        ]);
    }

    public function update(Request $request, Window $window)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'services' => 'array',
            'services.*' => 'exists:services,id',
        ]);
        $window->update($validated);
        if ($request->has('services')) {
            $window->services()->sync($request->input('services'));
        }
        return redirect()->route('windows.index');
    }

    public function destroy(Window $window)
    {
        $window->delete();
        return redirect()->route('windows.index');
    }
}
