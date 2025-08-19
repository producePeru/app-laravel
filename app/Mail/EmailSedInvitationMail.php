<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmailSedInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $messageContent;

    public function __construct($messageContent)
    {
        $this->messageContent = $messageContent;
    }


    public function build()
    {
        return $this->subject('Gestión de Tecnologías de la Información 🚀')
            ->view('emails.invitacionProvincia')
            ->with('content', $this->messageContent);
    }
}



// sed
// invitacionProvincia