<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $myID = Auth()->id();
        $notifications = Notification::with(['user'])->where('user_id', $myID)->get();
        $sortedNot = collect($notifications)->sortByDesc('id');
        $finalL = $sortedNot->values()->all();


        return response([
            'notifications' => $finalL,
            'message' => 'notifications retrieved successfully',
            'success' => true,
        ], 200);
    }



    public function readNotificationAll()
    {
        $myID = Auth::id();
        $notifications = Notification::with(['user'])->where('user_id', $myID)->get();

        // Check if there are no notifications first
        if ($notifications->isEmpty()) {
            return response([
                'notifications' => [],
                'message' => 'No notifications to read',
                'success' => true,
            ], 200);
        }

        // Sort notifications by ID in descending order
        $sortedNot = $notifications->sortByDesc('id');
        $finalL = $sortedNot->values()->all();

        // Mark notifications as read
        foreach ($notifications as $notification) {
            $notification->update(['is_read' => true]);
        }

        return response([
            'notifications' => $finalL,
            'message' => 'Notifications read successfully',
            'success' => true,
        ], 200);
    }



    public function destroy($id)
    {
        try {
            $notification = Notification::with(['user'])->findOrFail($id);

            $notification->delete();

            return response([
                'message' => 'notification deleted successfully',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }
}
