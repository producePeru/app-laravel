<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanAccionMail extends Mailable
{
  use Queueable, SerializesModels;

  public array $data;
  public string $pdfContent;
  public string $fileName;

  public function __construct(array $data, string $pdfContent, string $fileName)
  {
    $this->data = $data;
    $this->pdfContent = $pdfContent;
    $this->fileName = $fileName;
  }

  public function envelope(): Envelope
  {
    return new Envelope(
      subject: 'Tu Plan de Acción - Mujer Produce',
    );
  }

  public function content(): Content
  {
    return new Content(
      view: 'emails.plan_accion',
      with: ['data' => $this->data],
    );
  }

  public function attachments(): array
  {
    return [
      Attachment::fromData(fn() => $this->pdfContent, $this->fileName)
        ->withMime('application/pdf'),
    ];
  }
}
