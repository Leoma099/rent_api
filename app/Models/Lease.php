<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Notifications\SystemNotifications;

class Lease extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'property_id',
        'landlord_id',
        'start_date',
        'end_date',
        'status'
    ];

    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Automatically update lease status based on dates.
     * Status values:
     * 0 = Pending
     * 1 = Active
     * 2 = Expiring Soon
     * 3 = Expired
     */
    public function autoUpdateStatus()
    {
        $today = Carbon::now();
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);
        $previousStatus = $this->status;

        // Pending → Active if current date >= start date
        // if ($this->status === 0 && $today->gte($startDate)) {
        //     $this->status = 1;
        // }

        // Expiring Soon (<=2 days left)
        $daysLeft = $today->diffInDays($endDate, false);
        if ($this->status === 1 && $daysLeft <= 2 && $daysLeft >= 0) {
            $this->status = 2;
        }

        // Expired
        if ($today->gt($endDate) && $this->status !== 3) {
            $this->status = 3;
        }

        // Save only if status changed
        if ($previousStatus !== $this->status) {
            $this->save();

            // Send notifications individually
            $this->sendStatusNotification();
        }
    }

    /**
     * Send notification to landlord and tenant for the current status.
     */
    // CHANGE THIS ↓
    public function sendStatusNotification()
    {
        switch ($this->status) {
            case 1:
                $title = 'Lease Activated';
                $messageLandlord = "You activated the lease for {$this->tenant->name} at {$this->property->title}.";
                $messageTenant = "Your lease for {$this->property->title} has been approved and is now active.";
                break;
            case 2:
                $title = 'Lease Expiring Soon';
                $messageLandlord = "The {$this->property->title} rented by {$this->tenant->name} is about to expire on {$this->end_date}.";
                $messageTenant = "Your rental in {$this->property->title} is about to expire on {$this->end_date}.";
                break;
            case 3:
                $title = 'Lease Expired';
                $messageLandlord = "The {$this->property->title} rented by {$this->tenant->name} has expired.";
                $messageTenant = "Your rental in {$this->property->title} has expired.";
                break;
            default:
                return;
        }

        $this->landlord->notify(new SystemNotifications($title, $messageLandlord));
        $this->tenant->notify(new SystemNotifications($title, $messageTenant));
    }

}
