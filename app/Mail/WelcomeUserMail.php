<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once, when an administrator creates a new CSWD personnel or
 * barangay official account. Includes login credentials directly --
 * a known, deliberate tradeoff given this system's support model
 * (staff message the admin to change their password, not a self-service
 * reset flow), not something to silently repeat elsewhere in this system
 * without the same consideration.
 */
class WelcomeUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $roleDisplayName,
    ) {}

    public function build()
    {
        return $this->subject('Welcome to E-LIKAS — Your Account Details')
            ->view('emails.welcome-user');
    }
}
