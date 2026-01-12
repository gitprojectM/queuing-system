<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueueStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'queue_id',
        'service_id',
        'window_id',
        'step_order',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function queue()
    {
        return $this->belongsTo(Queue::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function window()
    {
        return $this->belongsTo(Window::class);
    }
}
