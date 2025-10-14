<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Inquiry;
use App\Models\Property;

class InquiryController extends Controller
{
    // Landlord views inquiries
    public function index(Request $request)
    {
        $query = Auth::user()->receivedInquiries()
            ->with('tenant.account', 'property', 'messages.sender')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;

            $query->whereHas('tenant.account', function ($tenantQuery) use ($search) {
                $tenantQuery->where('full_name', 'like', "%{$search}%");
            });
        }

        $inquiries = $query->get();

        return response()->json($inquiries);
    }


    public function store(Request $request)
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'landlord_id' => 'required|exists:users,id',
            'message'     => 'required|string',
        ]);

        $inquiry = Inquiry::firstOrCreate(
            [
                'property_id' => $request->property_id,
                'tenant_id'   => Auth::id(),
                'landlord_id' => $request->landlord_id,
            ],
            ['status' => 0] // Pending by default
        );

        $message = $inquiry->messages()->create([
            'sender_id' => Auth::id(),
            'message'   => $request->message,
        ]);

        return response()->json($inquiry->load(['property','tenant','landlord','messages.sender']));
    }

}