<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ForgotPassword extends Notification
{
    use Queueable;

    private $messages;

    /**
     * Create a new notification instance.
     */
    public function __construct($messages)
    {
        $this->messages = $messages;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->line($this->messages['greeting'])
                                ->line($this->messages['message'])
                                ->line($this->messages['link'])
                                ->line('Thank you');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
