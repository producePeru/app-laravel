<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Mail\CertificadoEnviadoMail;
use Illuminate\Support\Facades\Mail;

class SendPdfCertificadosEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $pdfFile;

    public function __construct($email, $pdfFile)
    {
        $this->email = $email;
        $this->pdfFile = $pdfFile;
    }

    public function handle()
    {
        Mail::to($this->email)
            ->send(new CertificadoEnviadoMail($this->email, $this->pdfFile));
    }
}
