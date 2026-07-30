<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecordatorioActividadPP093Mail extends Mailable
{
  use Queueable, SerializesModels;

  public array $dataUsuario;
  public array $actividad;

  public function __construct(array $dataUsuario, array $actividad)
  {
    $this->dataUsuario = $dataUsuario;
    $this->actividad   = $actividad;
  }

  public function build()
  {
    return $this->subject('⏰ Recordatorio: Tu capacitación inicia en unas horas')
      ->view('emails.recordatorio_actividad');
  }
}
