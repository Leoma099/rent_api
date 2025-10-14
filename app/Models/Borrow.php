<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Borrow extends Model
{
    use HasFactory;

    protected $fillable =
        [
            'account_id',
            'equipment_id',
            'full_name',
            'id_number',
            'office_name',
            'office_address',
            'type',
            'brand',
            'model',
            'quantity',
            'property_number',
            'equipment',
            'position',
            'mobile_number',
            'purpose',
            'status',
            'date_borrow',
            'date_return',

            'agent',
            'date',
        ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id', 'id');
    }

    public function BorrowEquipmentCreator()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function autoUpdateStatus()
    {
        $today = Carbon::now();
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);

        $landlord = $this->landlord;

        // Update status
        if ($today < $startDate) {
            $this->status = 0; // Pending
        } 
        elseif ($today >= $startDate && $today <= $endDate) {
            $this->status = 1; // Active
        }

        // Check for Expiring Soon (within 3 days before endDate date)
        if ($this->status == 1 && $today->diffInDays($endDate, false) <= 3 && $today <= $endDate) {
            $this->status = 2; // Expiring Soon

            // Notify landlord
            $title = "Lease Expiring Soon";
            $message = "The lease for tenant {$this->tenant->name} at property {$this->property->title} is about to expire.";
            $landlord->notify(new SystemNotifications($title, $message));
        }

        // Check for Expired
        if ($today > $endDate) {
            $this->status = 3; // Expired

            // Notify landlord
            $title = "Lease Expired";
            $message = "The lease for tenant {$this->tenant->name} at property {$this->property->title} has expired.";
            $landlord->notify(new SystemNotifications($title, $message));
        }

        $this->save();
    }

    public function index()
    {
        $leases = Lease::with(['property', 'tenant.account'])
            ->where('landlord_id', Auth::id())
            ->latest()
            ->get();

        // Auto-update status for each lease
        foreach ($leases as $lease)
        {
            $lease->autoUpdateStatus();
        }

        return response()->json($leases);
    }

    // Create a new lease contract
    public function store(Request $request)
    {
        $request->validate([
            'property_id'   => 'required|exists:properties,id',
            'tenant_id'     => 'required|exists:accounts,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after:start_date',
        ]);

        $lease = Lease::create([
            'property_id'   => $request->property_id,
            'landlord_id'   => Auth::id(),
            'tenant_id'     => $request->tenant_id,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'status'        => 0, // Pending
        ]);

        // Notify landlord
        $landlord = Auth::user();
        $landlord->notify(new SystemNotifications(
            'New Lease Contract Created',
            'You have created a new lease contract'
        ));

        return response()->json($lease);
    }

    // Delete a lease
    public function destroy($id)
    {
        $lease = Lease::where('landlord_id', Auth::id())->findOrFail($id);
        $lease->delete();

        return response()->json([
            'message' => 'Lease deleted successfully'
        ]);
    }

    // Manually activate the lease (status 1)
    public function updateStatus(Request $request, $id)
    {
        $lease = Lease::where('landlord_id', Auth::id())
            ->with(['tenant', 'property'])
            ->findOrFail($id);

        // Update status to Active only
        $lease->update(['status' => 1]);

        $landlord = Auth::user();

        // Notify landlord about the update
        $titleStatus = "Status Update";
        $messageStatus = "You updated the status for tenant {$lease->tenant->name} renting property {$lease->property->title}.";
        $landlord->notify(new SystemNotifications($titleStatus, $messageStatus));

        return response()->json([
            'message' => 'Status updated successfully!',
            'lease' => $lease
        ]);
    }
};