<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
        'service_id',
        'window_id',
        'queue_number',
        'status',
        'priority',
        'queue_date', // new: date for daily reset
    ];

    protected $dates = [
        'queue_date',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function window()
    {
        return $this->belongsTo(Window::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
