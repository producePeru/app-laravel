<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class DisclaimerMail extends Mailable
{
    public $pdf;
    public $data;

    public function __construct($pdf, $data)
    {
        $this->pdf = $pdf;
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject('Declaración Jurada - Cyber Wow')
            ->view('emails.disclaimer')
            ->with(['data' => $this->data])
            ->attachData($this->pdf, 'declaracion-jurada.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
