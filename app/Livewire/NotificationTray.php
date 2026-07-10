<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationTray extends Component
{
    public function getListeners()
    {
        return [
            'notificationRead' => '$refresh',
        ];
    }

    public function markAsRead($notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
            $this->dispatch('notificationRead');
        }
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        $this->dispatch('notificationRead');
    }

    public function markAsReadAndRedirect($notificationId, $url)
    {
        $notification = Auth::user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
        }
        return redirect($url);
    }

    public function render()
    {
        $user = Auth::user();
        $notifications = $user ? $user->unreadNotifications()->take(5)->get() : collect();
        $unreadCount = $user ? $user->unreadNotifications()->count() : 0;

        return view('livewire.notification-tray', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }
}
