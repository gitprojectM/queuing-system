<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Window extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * The services that belong to the window.
     */
    public function services()
    {
        return $this->belongsToMany(Service::class);
    }

    /**
     * The users assigned to the window.
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * The current queue entry (client) assigned to the window.
     */
    public function currentClient()
    {
        return $this->belongsTo(\App\Models\Queue::class, 'current_client_id');
    }

    /**
     * All waiting queues for this window (status = waiting).
     */
    public function waitingQueues()
    {
        return $this->hasMany(\App\Models\Queue::class, 'window_id')
            ->where('status', 'waiting');
    }

    public function queueSteps()
    {
        return $this->hasMany(\App\Models\QueueStep::class);
    }
}
