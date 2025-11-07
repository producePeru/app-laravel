<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class FormalizationRuc20ExportReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $filename;
    public string $pathFile;

    public function __construct(string $filename, string $pathFile)
    {
        $this->filename = $filename;
        $this->pathFile = $pathFile;
    }

    public function build()
    {
        return $this->subject('Exportación de Formalizaciones RUC 20 Lista')
            ->view('emails.advisories_export_ready')
            ->attach($this->pathFile, [
                'as' => $this->filename,
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
