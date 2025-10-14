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
        $messages = $inquiry->messages()
            ->with('sender')
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
                "{$tenantName} has inquired for <strong>{$propertyName}</strong>"
            ));
        } 
        elseif ($user->id === $landlord->id) {
            // 🧑‍💼 Landlord replies → Notify tenant
            $tenant->notify(new SystemNotifications(
                "Inquiry Property",
                "From landlord regarding the <strong>{$propertyName}</strong>: \"{$inquiryMessage}\""
            ));
        }

        return response()->json($message);
    }
}