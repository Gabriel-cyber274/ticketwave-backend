<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValidatedTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'register_id',
        'message',
        'type_id',
        'user_id',
    ];


    public function register()
    {
        return $this->belongsTo(Register::class, 'register_id');
    }

    public function ticket_type()
    {
        return $this->belongsTo(EventCost::class, 'type_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
