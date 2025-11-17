<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inquiry;
use App\Models\InquiryMessage;
use App\Notifications\SystemNotifications;
use Illuminate\Support\Facades\Auth;

class InquiryMessageController extends Controller
{
    public function index(Inquiry $inquiry)
    {
        $user = Auth::user();

        // 🔐 Ensure user owns this inquiry (either tenant or landlord)
        if (
            $user->id !== $inquiry->tenant_id &&
            $user->id !== $inquiry->landlord_id
        ) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        // ✅ Load messages with sender info
        $messages = $inquiry->messages()
            ->with('sender.account')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    public function store(Request $request, Inquiry $inquiry)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = Auth::user();

        // Store the new message
        $message = $inquiry->messages()->create([
            'sender_id' => $user->id,
            'message'   => $request->message,
        ]);

        $message->load('sender');
        $inquiry->load(['tenant.account', 'landlord.account', 'property']);

        $tenant        = $inquiry->tenant;
        $landlord      = $inquiry->landlord;
        $propertyName  = $inquiry->property->title ?? 'a property';
        $tenantName    = $tenant->account->full_name ?? $tenant->username;
        $inquiryMessage = $request->message;

        /**
         * 📩 Notification logic
         */
        if ($user->id === $tenant->id) {
            // 🧍‍♂️ Tenant sends a message → Notify landlord
            $landlord->notify(new SystemNotifications(
                "Inquire Property",
                "{$tenantName} has inquired for {$propertyName}",
                "inquiry",
                $inquiry->id  // pass inquiry ID
            ));
        } 
        elseif ($user->id === $landlord->id) {
            // 🧑‍💼 Landlord replies → Notify tenant
            $tenant->notify(new SystemNotifications(
                "Inquiry Property",
                "From landlord regarding the {$propertyName}: \"{$inquiryMessage}\"",
                "inquiry",
                $inquiry->id  // pass inquiry ID
            ));
        }

        return response()->json($message);
    }

    public function markAsRead(Inquiry $inquiry)
    {
        $user = Auth::user();

        // Ensure the user belongs to this inquiry
        if ($user->id !== $inquiry->tenant_id && $user->id !== $inquiry->landlord_id) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        // Update all messages not sent by the current user
        $inquiry->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Messages marked as read']);
    }

}