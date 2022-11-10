<?php
namespace App\Classes;

use App\Models\User;
use App\Notifications\MainNotificaiton;

class Notify
{
    public static function send(int $userId, string $subject, string $body, string $url = ''): void
    {
        $user = User::find($userId);
        if ($user) {
            $user->notify(new MainNotificaiton($subject, $body, $url));
        }
    }

    public static function sendToAll(int $companyId, string $subject, string $body): void
    {
        User::where('company_id', $companyId)->get()->each(function ($user) use ($subject, $body) {
            $user->notify(new MainNotificaiton($subject, $body));
        });
    }
}
