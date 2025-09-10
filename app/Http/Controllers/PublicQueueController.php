<?php

namespace App\Http\Controllers;

use App\Models\Window;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicQueueController extends Controller
{
    public function index()
    {
        // Check if specific services are requested via URL parameters
        $selectedServices = request()->get('services');
        
        if ($selectedServices) {
            // Convert comma-separated string to array
            $serviceIds = explode(',', $selectedServices);
            $services = \App\Models\Service::with(['windows.currentClient.service', 'windows.currentClient.user'])
                ->whereIn('id', $serviceIds)
                ->get();
        } else {
            // Get all services with their windows and each window's current client
            $services = \App\Models\Service::with(['windows.currentClient.service', 'windows.currentClient.user'])->get();
        }
        
        return Inertia::render('PublicQueue', [
            'services' => $services,
            'allServices' => \App\Models\Service::all(), // For service selector
            'selectedServiceIds' => $selectedServices ? explode(',', $selectedServices) : [],
        ]);
    }

    public function selectServices()
    {
        // Get all services for selection
        $allServices = \App\Models\Service::all();
        
        return Inertia::render('ServiceSelector', [
            'services' => $allServices,
        ]);
    }

    public function serviceView($serviceId)
    {
        // Get specific service with its windows and current clients
        $service = \App\Models\Service::with(['windows.currentClient.service', 'windows.currentClient.user'])
            ->findOrFail($serviceId);
        
        return Inertia::render('ServiceNowServing', [
            'service' => $service,
        ]);
    }

    public function windowView($windowId)
    {
        // Get specific window with its current client
        $window = Window::with(['currentClient.service', 'currentClient.user', 'service'])
            ->findOrFail($windowId);
        
        return Inertia::render('WindowNowServing', [
            'window' => $window,
        ]);
    }
}
