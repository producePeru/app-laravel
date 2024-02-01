<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\AcceptInvitationWorkshopDetails;

class AcceptInvitationWorkshopJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $email;
    public $mype;

    public function __construct($email, $mype)
    {
        $this->email = $email;
        $this->mype = $mype;
    }

    public function handle(): void
    {
        Mail::to($this->email)
            ->send(new AcceptInvitationWorkshopDetails($this->mype));
    }
}
