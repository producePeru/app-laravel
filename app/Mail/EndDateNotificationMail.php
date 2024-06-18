<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EndDateNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $agreement;


    public function __construct($agreement)
    {
        $this->agreement = $agreement;
    }

    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: 'End Date Notification Mail',
    //     );
    // }

    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'view.name',
    //     );
    // }

    public function build()
    {
        return $this->view('emails.endDateNotification')
                    ->with(['agreement' => $this->agreement]);
    }

    public function attachments(): array
    {
        return [];
    }
}
