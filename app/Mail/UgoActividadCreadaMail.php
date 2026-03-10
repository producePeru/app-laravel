<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UgoActividadCreadaMail extends Mailable
{
    public $attendance;
    public $asesor;
    public $link;
    public $isEdit;
    public $changes;

    public function __construct($attendance, $asesor, $link, $isEdit, $changes)
    {
        $this->attendance = $attendance;
        $this->asesor = $asesor;
        $this->link = $link;
        $this->isEdit = $isEdit;
        $this->changes = $changes;
    }

    public function build()
    {
        return $this->subject(
            $this->isEdit
                ? 'Actualización de actividad'
                : 'Nuevo registro de actividad'
        )->view('emails.eventoCreadoUgo');
    }
}
