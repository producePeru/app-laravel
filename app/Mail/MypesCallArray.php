<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MypesCallArray extends Mailable
{
    use Queueable, SerializesModels;

    public $empresa;

    public function __construct($empresa)
    {
        $this->empresa = $empresa;
    }

    public function build()
    {
        return $this->view('emails.empresa')
                    ->subject('CAPACITA-TEC 2024')
                    ->with([
                        'empresa' => $this->empresa
                    ]);
    }
}