<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_id',
        'quantity',
        'paid',
        'ticket_cost',
        'ticket_type'
    ];

    
    protected function casts(): array
    {
        return [
            'paid' => 'bool',
        ];
    }

    

    
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

}
