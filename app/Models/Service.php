<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * The windows that belong to the service.
     */
    public function windows()
    {
        return $this->belongsToMany(Window::class);
    }

    /**
     * The users assigned to the service.
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * The queues for this service.
     */
    public function queues()
    {
        return $this->hasMany(Queue::class);
    }
}
