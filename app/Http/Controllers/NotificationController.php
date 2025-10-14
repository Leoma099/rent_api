<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

class NotificationController extends Controller
{
    // Fetch notifications for the authenticated user (paginated)
    public function index(Request $request)
    {
        // Paginate notifications, 10 per page
        $notifications = $request->user()->notifications()->latest()->paginate(10);
        
        return response()->json($notifications);
    }

    // Mark a specific notification as read
    public function markAsRead($id)
    {
        // Find the notification by ID
        $notification = Notification::findOrFail($id);
        
        // Check if the notification belongs to the authenticated user
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Mark the notification as read
        $notification->markAsRead();
        
        return response()->json($notification);
    }
    
    // Mark all notifications as read for the authenticated user
    public function markAllAsRead(Request $request)
    {
        // Mark all notifications as read for the authenticated user
        $request->user()->notifications->each->markAsRead();
        
        return response()->json(['message' => 'All notifications marked as read.']);
    }

    // Fetch unread notifications
    public function unread(Request $request)
    {
        // Get only unread notifications (notifications without a 'read_at' timestamp)
        $unreadNotifications = $request->user()->notifications()->whereNull('read_at')->latest()->get();
        
        return response()->json($unreadNotifications);
    }

    public function destroy($id)
    {
        $user = Auth::user();

        // Find the notification for the current user
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification)
        {
            $notification->delete();

            return response()->json([
                'message' => 'Notification deleted successfully'
            ], 200);
        }
        else
        {
            return response()->json([
                'message' => 'Notification not found'
            ], 404);
        }
    }

    public function destroyAll()
    {
        $user = Auth::user();

        // Delete all notifications of the user
        $user->notifications()->delete();

        return response()->json([
            'message' => 'All notifications deleted successfully'
        ], 200);
    }
}
