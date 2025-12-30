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
            ->subject('Constancia de participación de curso del Ministerio de la Producción')
            ->with(['email' => $this->email])
            ->attach($filePath)
            ->cc('tuempresa_temp265@produce.gob.pe');        // capacitaciones_tuempresa@produce.gob.pe || tuempresa_temp265@produce.gob.pe
    }
}
