<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'event_title',
        'event_website',
        'venue_details',
        // 'event_cost',
        'event_category',
        'organizer_details',
        'event_description',
        'event_start',
        // 'event_end',
        'event_image'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function costs()
    {
        return $this->hasMany(EventCost::class, 'event_id');
    }


    
    public function tags()
    {
        return $this->hasMany(EventTag::class, 'event_id');
    }


    public function registrations()
    {
       return $this->hasMany(Register::class, 'event_id');
    }

    
}
