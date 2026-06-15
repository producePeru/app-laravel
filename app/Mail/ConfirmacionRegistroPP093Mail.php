<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmacionRegistroPP093Mail extends Mailable
{
    use Queueable, SerializesModels;

    public $dataUsuario;
    public $actividadesDetalle;

    public function __construct($dataUsuario, $actividadesDetalle)
    {
        $this->dataUsuario = $dataUsuario;
        $this->actividadesDetalle = $actividadesDetalle;
    }

    public function build()
    {
        return $this->subject('Confirmación de Inscripción a tus Cursos')
            ->view('emails.confirmacion_registro');
    }
}
