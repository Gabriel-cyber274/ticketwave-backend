<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'description',
        'title',
        'is_read',
        'event_id',
        'customer_id',
        'withdraw_id',

    ];



    public function user()
    {
        return $this->belongsTo(User::class);
    }


    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'event_id' => 'integer',
            'customer_id' => 'integer',
            'withdraw_id' => 'integer'
        ];
    }
}
