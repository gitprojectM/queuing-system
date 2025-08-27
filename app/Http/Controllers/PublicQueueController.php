<?php

namespace App\Http\Controllers;

use App\Models\Window;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicQueueController extends Controller
{
    public function index()
    {
        // Get all services with their windows and each window's current client
        $services = \App\Models\Service::with(['windows.currentClient.service', 'windows.currentClient.user'])->get();
        return Inertia::render('PublicQueue', [
            'services' => $services,
        ]);
    }
}
