<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'landlord_id', // User account
        'property_id', // What property is scheduled
        'available_day',
        'start_time',
        'end_time',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    public function bookings()
    {
        return $this->hasMany(BookingSchedule::class);
    }

    public function getIsBookedAttribute()
    {
        // true if any booking is still active (pending or approved)
        return $this->bookings()->whereIn('status', [0, 1])->exists();
    }

}
