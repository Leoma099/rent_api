<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BorrowNotification extends Model
{
    protected $fillable = [
        'notified_to',
        'notified_by',
        'message',
        'data',
        'is_read',
    ];

    public function notifiedTo()
    {
        return $this->belongsTo(Account::class, 'notified_to');
    }

    public function notifiedBy()
    {
        return $this->belongsTo(Account::class, 'notified_by');
    }

    public static function markAsReadByUser($userId)
    {
        return static::where('notified_to', $userId)
                    ->where('is_read', false)
                    ->update(['is_read' => true]);
    }
}
