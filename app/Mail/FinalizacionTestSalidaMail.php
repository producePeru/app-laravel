<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;

class FinalizacionTestSalidaMail extends Mailable
{
  use Queueable, SerializesModels;

  public $data;

  public function __construct(array $data)
  {
    $this->data = $data;
  }

  public function build()
  {
    return $this
      ->subject('¡Gracias por participar en nuestra capacitación!')
      ->view('emails.finalizacion_test_salida');
  }
}
