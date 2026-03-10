<?php

namespace App\Jobs;

use App\Models\EmailTemplate;
use App\Models\EmailSend;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class SendHonstigerTemplateEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $template;
    protected $asunto;
    protected $copias;

    public function __construct($email, EmailTemplate $template, $asunto, $copias = [])
    {
        $this->email = $email;
        $this->template = $template;
        $this->asunto = $asunto;
        $this->copias = $copias;
    }

    public function handle()
    {
        $mailer = 'hostinger3k';

        Mail::mailer($mailer)
            ->html($this->template->content, function ($message) {

                $message->to($this->email)
                    ->subject($this->asunto);

                if (!empty($this->copias)) {
                    $message->cc($this->copias);
                }
            });
    }
}
