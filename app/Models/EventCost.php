<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventCost extends Model
{
    use HasFactory;


    protected $fillable = [
        'event_id',
        'level',
        'cost',
        'available'
    ];



    public function event()
    {
        return $this->belongsTo(Event::class);
    }



    public function validated_tickets()
    {
        return $this->hasMany(ValidatedTicket::class, 'type_id');
    }
}
