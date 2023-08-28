<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewCustomerNotificaiton extends Notification
{
    use Queueable;

    public function __construct(private string $customerName) {}

    public function via(object $notifiable): array { return ['database','mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Customer Registered: ' . $this->customerName)
            ->line('A new customer has registered: ' . $this->customerName)
            ->action('View Customers', url('/customers'));
    }

    public function toArray(object $notifiable): array
    {
        return ['message' => 'New customer: ' . $this->customerName];
    }
}
