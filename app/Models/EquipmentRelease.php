<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipmentRelease extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'borrow_id',
        'type',
        'release_to',
        'department',
        'date',
        'full_name'
    ];

    public function borrow()
    {
        return $this->belongsTo(Borrow::class, 'borrow_id', 'id');
    }

    public function EquipmentReleaseCreator()
    {
        return $this->belongsTo(Borrow::class, 'borrow_id');
    }
}
