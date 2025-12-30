<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AcceptInvitationWorkshopDetails extends Mailable
{
    use Queueable, SerializesModels;

    public $mype;

    public function __construct($mype)
    {
        $this->mype = $mype;
    }

    public function build()
    {
        return $this->from(env('MAIL_FROM_ADDRESS', 'digitalizacion.pnte@gmail.com'), 'Taller de Ruta Digital')
                    ->subject('Bienvenido al taller')
                    ->view('email.accept_invitation_workshop')
                    ->with(['mype' => $this->mype]);
    }

    public function attachments(): array
    {
        return [];
    }
}
