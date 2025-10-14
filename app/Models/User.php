<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable; // ✅ added

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable; // ✅ added Notifiable

    protected $fillable =
    [
        'username',
        'password',
        'role',
        'status'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function account()
    {
        return $this->hasOne(Account::class, 'user_id');
    }

    public function sentInquiries()
    {
        return $this->hasMany(Inquiry::class, 'tenant_id');
    }

    public function receivedInquiries()
    {
        return $this->hasMany(Inquiry::class, 'landlord_id');
    }

}