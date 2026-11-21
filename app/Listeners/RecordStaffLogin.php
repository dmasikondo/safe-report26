<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

class RecordStaffLogin
{
    /**
     * Stamp the staff member's last sign-in time.
     */
    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $event->user->recordLogin();
        }
    }
}
