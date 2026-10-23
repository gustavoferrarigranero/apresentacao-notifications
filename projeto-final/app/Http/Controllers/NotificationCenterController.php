<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class NotificationCenterController extends Controller
{
    public function index(User $user): View
    {
        return view('notifications.index', [
            'user' => $user,
            'notifications' => $user->notifications()->latest()->paginate(20),
        ]);
    }

    public function read(User $user, string $notification): RedirectResponse
    {
        $user->notifications()->findOrFail($notification)->markAsRead();

        return back();
    }
}
