<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemNotifications extends Notification
{
    use Queueable;

    protected $title;
    protected $message;
    protected $inquiryId;

    public function __construct($title, $message, $type = null, $inquiryId = null)
    {
        $this->title   = $title;
        $this->message = $message;
        $this->type      = $type;       // e.g., 'inquiry'
        $this->inquiryId = $inquiryId;  // store inquiry ID
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title'   => $this->title,
            'message' => $this->message,
            'type'       => $this->type,
            'inquiry_id' => $this->inquiryId,
        ];
    }
}
