<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingSchedule extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'tenant_id',
        'landlord_id',
        'property_id',
        'schedule_id',
        'status'
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id')->with('account');
    }

    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}
