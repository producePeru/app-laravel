<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventMujerProduceMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;


    public function __construct(array $data)
    {
        $this->data = $data;
    }


    public function build()
    {
        return $this->subject('Mujer Produce - Enlace a sala virtual 😉')
            ->view('emails.enviarLinkMujerProduce');
    }
}
