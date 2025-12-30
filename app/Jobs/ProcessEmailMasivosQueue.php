<?php

namespace App\Jobs;

use App\Mail\EmailServiceMasivos;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessEmailMasivosQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $email;
    public $messageContent;
    public $mailer;

    /**
     * Create a new job instance.
     *
     * @param string $email
     * @param string $messageContent
     */
    public function __construct($email, $messageContent, $mailer)
    {
        $this->email = $email;
        $this->messageContent = $messageContent;
        $this->mailer = $mailer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::mailer($this->mailer)->to($this->email)->send(new EmailServiceMasivos($this->messageContent));
    }
}
