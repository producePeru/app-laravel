<?php

namespace App\Mail;

use App\Models\Tienda;
use App\Models\TiendaContacto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TiendaContactoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contacto;

    public $tienda;

    public function __construct(
        TiendaContacto $contacto,
        Tienda $tienda
    ) {
        $this->contacto = $contacto;
        $this->tienda = $tienda;
    }

    public function build()
    {
        return $this
            ->subject('Gracias por tu interés en '.$this->tienda->nombre)
            ->view('emails.tienda-contacto');
    }
}
