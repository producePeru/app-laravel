<?php

namespace App\Jobs;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

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

                if (! empty($this->copias)) {
                    $message->cc($this->copias);
                }

                // $message->bcc('capacitaciones_tuempresa@produce.gob.pe');
            });
    }
}
