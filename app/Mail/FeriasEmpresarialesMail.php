<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Fair;
use Carbon\Carbon;

class FeriasEmpresarialesMail extends Mailable
{
    use Queueable, SerializesModels;

    public $fair;

    public function __construct(Fair $fair)
    {
        $this->fair = $fair;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmación de Registro – Feria Virtual Perú Produce Cyber Wow Julio 2025',
        );
    }

    public function build()
    {
        $formattedDate = Carbon::parse($this->fair->endDate)->format('d/m/Y');

        return $this->view('emails.feriasEmpresariales')
            ->subject('Welcome to the Fair')
            ->with([
                'fair' => $this->fair,
                'formattedDate' => $formattedDate
            ]);
    }
}
