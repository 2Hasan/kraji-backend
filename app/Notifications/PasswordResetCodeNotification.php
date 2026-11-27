<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $code) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('كود استعادة كلمة المرور')
            ->greeting('السلام عليكم')
            ->line('كود استعادة كلمة المرور لتطبيق كراجي: '.$this->code)
            ->line('الكود صالح 15 دقيقة. إذا ما طلبت هذا، تجاهل الرسالة.');
    }
}
