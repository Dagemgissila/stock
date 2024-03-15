<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MainNotificaiton extends Notification
{
    use Queueable;
    public function __construct(
        private string $subject,
        private string $body,
        private string $actionUrl  = '',
        private string $actionText = 'View'
    ) {}
    public function via(object $notifiable): array { return ['database','mail']; }
    public function toMail(object $notifiable): MailMessage {
        $mail = (new MailMessage)->subject($this->subject)->line($this->body);
        if ($this->actionUrl) $mail->action($this->actionText, $this->actionUrl);
        return $mail;
    }
    public function toArray(object $notifiable): array {
        return ['subject'=>$this->subject,'body'=>$this->body,'action_url'=>$this->actionUrl];
    }
}
