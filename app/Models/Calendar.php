<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    use HasFactory;

    protected $fillable = [
        'event',
        'place',
        'date',
        'description',
        'account_id'
    ];

    public function Account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }
}
