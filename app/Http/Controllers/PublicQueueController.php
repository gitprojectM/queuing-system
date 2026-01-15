<?php

namespace App\Http\Controllers;

use App\Models\Window;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use App\Models\AppSetting;

class PublicQueueController extends Controller
{
    private const VIDEO_URL_KEY = 'display_video_url';

    /**
     * JSON endpoint for polling fallback (used by public displays).
     */
    public function data(Request $request)
    {
        $selectedServices = $request->get('services');

        if ($selectedServices) {
            $serviceIds = explode(',', $selectedServices);
            $services = \App\Models\Service::with(['windows.currentClient.service', 'windows.currentClient.user'])
                ->whereIn('id', $serviceIds)
                ->get();
        } else {
            $services = \App\Models\Service::with(['windows.currentClient.service', 'windows.currentClient.user'])->get();
        }

        return response()->json([
            'services' => $services,
            'selectedServiceIds' => $selectedServices ? explode(',', $selectedServices) : [],
        ]);
    }

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
        
        $videoUrl = Cache::rememberForever(self::VIDEO_URL_KEY, function () {
            return AppSetting::where('key', self::VIDEO_URL_KEY)->value('value') ?: '';
        });

        return Inertia::render('PublicQueue', [
            'services' => $services,
            'allServices' => \App\Models\Service::all(), // For service selector
            'selectedServiceIds' => $selectedServices ? explode(',', $selectedServices) : [],
            'videoUrl' => $videoUrl,
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
