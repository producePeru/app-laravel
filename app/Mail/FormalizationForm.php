<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FormalizationForm extends Mailable
{
    use Queueable, SerializesModels;

    public $formalizationform;

    public function __construct($formalizationform)
    {
        $this->formalizationform = $formalizationform;
    }

    public function build()
    {
        return $this->from(env('MAIL_FROM_ADDRESS', 'jloo6778@gmail.com'), 'Taller de Ruta Digital')
                    ->subject('Bienvenido al taller')
                    ->view('email.accept_invitation_workshop')
                    ->with(['mype' => $this->formalizationform]);
    }

    public function attachments(): array
    {
        return [];
    }
}
