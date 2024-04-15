<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TestMail extends Notification
{
    use Queueable;
    public function via($notifiable): array { return ['mail']; }
    public function toMail($notifiable): MailMessage {
        return (new MailMessage)
            ->subject('Test Email from StockManager')
            ->line('This is a test email to verify your SMTP configuration.')
            ->line('If you received this, your mail settings are correct.')
            ->action('Go to Dashboard', url('/dashboard'));
    }
}
