<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliverRider extends Model
{
    use HasFactory;

    protected $table = 'delivery_riders';

    protected $fillable = [
        'agent',
        'email',
        'address',
        'mobile_number',
        'date_of_birth',
    ];
}
