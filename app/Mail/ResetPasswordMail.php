<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $email;

    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    // public function build()
    // {
    //     $resetUrl = "http://localhost:8080/reset-password?token={$this->token}&email={$this->email}";

    //     return $this->subject('Reset Your Password')
    //         ->view('emails.reset-password')
    //         ->with([
    //             'resetUrl' => $resetUrl,
    //             'email' => $this->email
    //         ]);
    // }
    public function build()
    {
        $frontendUrl = config('app.frontend_url');
        $resetUrl = "{$frontendUrl}/reset-password?token={$this->token}&email={$this->email}";

        return $this->subject('Reset Your Password')
                    ->view('emails.reset-password')
                    ->with([
                        'resetUrl' => $resetUrl,
                        'email' => $this->email
                    ]);
    }

}
