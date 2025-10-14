<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'landlord_id',
        'tenant_id',
        'property_id',
        'schedule_id',
        'booking_date',
        'booking_time',
        'status'
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }
}
