<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FairSedInfoMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        public string $mensaje,
        public string $qrData
    ) {
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.confirmacion_registro')
            ->with([
                'mensaje' => $this->mensaje,
            ])
            ->attachData($this->qrData, 'codigo-qr.png', [
                'mime' => 'image/png',
            ]);
    }
}
