<?php
namespace App\Observers;
use App\Models\User;
class UserObserver {
    public function created(User $user): void {
        \Log::info('New user created: '.$user->email.' for company '.$user->company_id);
    }
}
