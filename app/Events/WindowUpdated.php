<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WindowUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $window;

    public function __construct($window)
    {
        // Always load relationships for broadcasting
        $window->loadMissing(['currentClient.service', 'currentClient.user']);
        $this->window = $window;
    }

    public function broadcastOn()
    {
        return new Channel('windows');
    }
}
