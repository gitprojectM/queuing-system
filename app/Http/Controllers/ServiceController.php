<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with(['windows', 'queues' => function($q) {
            $q->where('status', 'assigned');
        }])->get();
        return Inertia::render('Services', ['services' => $services]);
    }

    public function dashboardServices()
    {
        $services = Service::with([
            'windows.users',
            'users',
            'queues' => function($q) {
                $q->where('status', 'assigned');
            }
        ])->get();
        return response()->json(['services' => $services]);
    }

    public function create()
    {
        return Inertia::render('ServiceForm');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        Service::create($validated);
        return redirect()->route('services.index');
    }

    public function edit(Service $service)
    {
        return Inertia::render('ServiceForm', ['service' => $service]);
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $service->update($validated);
        return redirect()->route('services.index');
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return redirect()->route('services.index');
    }
}
