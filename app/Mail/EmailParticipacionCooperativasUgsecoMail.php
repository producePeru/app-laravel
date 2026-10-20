<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailParticipacionCooperativasUgsecoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $nombres;
    public $capacitacion;

    public function __construct(?string $nombres, array $capacitacion)
    {
        $this->nombres = $nombres;
        $this->capacitacion = $capacitacion;
    }

    public function build()
    {
        return $this->subject('Confirmación de inscripción - '.$this->capacitacion['tema'])
            ->view('emails.email_participacion_cooperativas_ugseco');
    }
}
