<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmailService extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $messageContent;

    /**
     * Create a new message instance.
     *
     * @param string $messageContent
     */
    public function __construct($messageContent)
    {
        $this->messageContent = $messageContent;
    }

    /**
     * Build the message.           // desde el VUEXV...................                  ->view('emails.sed   pp03    ')
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('PERÚ PRODUCE')
            ->view('emails.template')
            ->with('content', $this->messageContent);
    }
}
