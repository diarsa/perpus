<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogUserActivity
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if ($event instanceof Login) {
            $event->user->logActivity('login', 'User logged in');
        } elseif ($event instanceof Logout) {
            if ($event->user) {
                $event->user->logActivity('logout', 'User logged out');
            }
        }
    }
}
