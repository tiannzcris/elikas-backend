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
        public string $roleName,
    ) {}

    public function build()
    {
        return $this->subject('Welcome to E-LIKAS — Your Account Details')
            ->view('emails.welcome-user', [
                // Only barangay officials use the offline desktop
                // companion -- CSWD/admin accounts only ever use the web
                // dashboard, so showing them a desktop app download link
                // would be confusing, not just unnecessary.
                // Available to every staff role, not just barangay officials --
                // CSWD/admin staff can also end up doing on-site registration
                // with no internet (e.g. deployed at an evacuation center
                // during an actual disaster), so the same offline need applies.
                'showDesktopDownload' => true,
                'downloadUrl' => url(config('elikas.desktop_app_download_url')),
            ]);
    }
}
