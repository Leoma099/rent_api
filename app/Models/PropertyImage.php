<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyImage extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'property_id',
        'photo_1',
        'photo_2',
        'photo_3',
        'photo_4'
    ];

    public function properties()
    {
        return $this->belongsTo(Property::class);
    }
}
