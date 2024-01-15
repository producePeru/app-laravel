<?php

namespace App\Mail;

use App\Models\WorkshopDetails;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AcceptInvitationWorkshopDetails extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public $workshop, public $mype)
    {
        // No es necesario agregar nada aquí.
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(env('MAIL_FROM_ADDRESS', 'digitalizacion.pnte@gmail.com'), 'Taller de Ruta Digital')
                    ->subject('Bienvenido al taller')
                    ->view('email.accept_invitation_workshop')
                    ->with([
                        'workshop' => $this->workshop,
                        'mype' => $this->mype,
                    ]);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
