<?php

namespace App\Jobs;

use App\Mail\EmailProvinciaInvitacionMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class CorreosProvinciaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $email;
    public $messageContent;
    public $mailer;

    public function __construct($email, $messageContent, $mailer)
    {
        $this->email = $email;
        $this->messageContent = $messageContent;
        $this->mailer = $mailer;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Mail::mailer($this->mailer)->to($this->email)->send(new EmailProvinciaInvitacionMail($this->messageContent));
    }
}
