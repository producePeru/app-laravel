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
        return $this->subject('¡Impulsa tus Ventas Digitales - Con las Sesiones de Entrenamiento Digital -Evento Gratuito presencial ! 💼🚀')
            ->view('emails.sed')
            ->with('content', $this->messageContent);
    }
}
