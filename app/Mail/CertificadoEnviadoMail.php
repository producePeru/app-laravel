<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CertificadoEnviadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $pdfFile;

    public function __construct($email, $pdfFile)
    {
        $this->email = $email;
        $this->pdfFile = $pdfFile;
    }

    public function build()
    {
        $filePath = storage_path('app/public/certificados/' . $this->pdfFile);

        return $this->view('emails.emailCertificadoEnviado')
                    ->subject('Constancia de participación de curso de Ministerio de la Produccion')
                    ->with(['email' => $this->email])
                    ->attach($filePath);
    }
}
