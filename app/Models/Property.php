<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'landlord_id', // User account
        'title',
        'description',
        'address',
        'lat',
        'lng',
        'price',
        'property_type',
        'photo',
        'floor_plan',
        'status',
        'is_featured',
        'size',
        'propertyStats'
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    public function landmarks()
    {
        return $this->hasMany(Landmark::class);
    }

    public function inquiries()
    {
        return $this->hasMany(Inquiry::class);
    }

    public function leases()
    {
        return $this->hasMany(Lease::class);
    }

}
