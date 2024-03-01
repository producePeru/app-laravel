<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

use App\Mail\AcceptInvitationWorkshopDetails;

class FormalizationFormPublicWorkshopJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $email;
    public $formalizationform;

    public function __construct($email, $formalizationform)
    {
        $this->email = $email;
        $this->formalizationform = $formalizationform;
    }

    public function handle(): void
    {
        Mail::to($this->email)
            ->send(new AcceptInvitationWorkshopDetails($this->formalizationform));
    }
}
