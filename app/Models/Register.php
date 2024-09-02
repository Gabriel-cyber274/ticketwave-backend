<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Register extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_id',
        'ticket_type',
        'ticket_quantity',
        'ticket_cost',
        'reference',
        'transaction'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

}
