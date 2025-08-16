<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FairSedInfoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $messageContent;
    public $qrPath;
    public $participantName;
    public $fair;

    public function __construct($messageContent, $qrPath, $participantName, $fair)
    {
        $this->messageContent = $messageContent;
        $this->qrPath = $qrPath;
        $this->participantName = $participantName;
        $this->fair = $fair;
    }

    public function build()
    {
        return $this->view('emails.fair_info')
            ->subject('Detalles del evento')
            ->attach($this->qrPath, [
                'as' => 'entrada.pdf',
                'mime' => 'application/pdf',
            ])
            ->with([
                'messageContent' => $this->messageContent,
                'participantName' => $this->participantName,
                'fair' => $this->fair,
            ]);
    }
}
