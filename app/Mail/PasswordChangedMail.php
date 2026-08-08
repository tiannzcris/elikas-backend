<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when an administrator resets an existing user's password via
 * UserController::update() -- same tradeoff as WelcomeUserMail: includes
 * the new plaintext password directly, since an email that only said
 * "your password changed" with no way to actually use the new one
 * wouldn't be genuinely actionable, and this system has no self-service
 * reset flow for the user to fall back on.
 */
class PasswordChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $password,
    ) {}

    public function build()
    {
        return $this->subject('Your E-LIKAS Password Was Changed')
            ->view('emails.password-changed');
    }
}
