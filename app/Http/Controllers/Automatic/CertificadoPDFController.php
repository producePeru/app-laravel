<?php

namespace App\Http\Controllers\Automatic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\SendPdfCertificadosEmailsJob;

class CertificadoPDFController extends Controller
{
    public function sendEmailWithCertificates(Request $request)
    {
        $emails = $request->input('emails');
        $groupedEmails = [];

        // Agrupar destinatarios por archivo PDF
        foreach ($emails as $emailData) {
            $email = $emailData['correo'];
            $pdfFile = $emailData['archivo'] . '.pdf';

            if (!isset($groupedEmails[$pdfFile])) {
                $groupedEmails[$pdfFile] = [];
            }
            $groupedEmails[$pdfFile][] = $email;
        }

        // Enviar trabajos agrupados
        foreach ($groupedEmails as $pdfFile => $recipients) {
            SendPdfCertificadosEmailsJob::dispatch($recipients, $pdfFile);
        }

        return response()->json(['message' => 'Emails are being processed.']);
    }
}