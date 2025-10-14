<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InquiryMessage extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'inquiry_id',
        'sender_id',
        'message',
    ];

    // 🔹 Message belongs to an inquiry
    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class);
    }

    // 🔹 Message belongs to a sender (user, could be tenant or landlord)
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
