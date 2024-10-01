<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class withdraw extends Model
{
    use HasFactory;


    protected $fillable = [
        'user_id',
        'amount',
        'is_accepted',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }



    protected function casts(): array
    {
        return [
            'is_accepted' => 'boolean',
        ];
    }
}
