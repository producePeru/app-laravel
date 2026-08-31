<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecordatorioPP093DesdeAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $dataUsuario;

    public array $actividad;

    public function __construct(array $dataUsuario, array $actividad)
    {
        $this->dataUsuario = $dataUsuario;
        $this->actividad = $actividad;
    }

    public function build()
    {
        return $this->subject('⏰ Recordatorio: Tu capacitación está por iniciar')
            ->view('emails.recordatorio_pp093_desde_admin');
    }
}
