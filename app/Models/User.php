<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'fullname',
        'email',
        'agree',
        'password',
        'admin',
        'profile_pic',
        'account_balance',
        'bank',
        'acc_no'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

    public function event()
    {
        return $this->hasMany(Event::class, 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }


    public function registrations()
    {
        return $this->hasMany(Register::class, 'user_id');
    }


    public function withdraws()
    {
        return $this->hasMany(withdraw::class, 'user_id');
    }



    public function volunteer()
    {
        return $this->hasOne(Volunteer::class, 'user_id');
    }





    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'admin' => 'boolean'
        ];
    }
}
