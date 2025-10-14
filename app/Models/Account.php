<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable =
        [
            'user_id',
            'full_name',
            'email',
            'mobile_number',
        ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function borrows()
    {
        return $this->hasMany(Borrow::class, 'account_id');
    }

    public function calendars()
    {
        return $this->hasMany(Calendar::class, 'account_id');
    }

    public function loggedBorrowNotifications()
    {
        return $this->hasMany(BorrowNotification::class, 'notified_to', 'id');
    }
}
