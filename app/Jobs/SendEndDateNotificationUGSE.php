<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Mail\EndDateNotificationMail;
use Illuminate\Support\Facades\Mail;

class SendEndDateNotificationUGSE implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $agreement;

    public function __construct($agreement)
    {
        $this->agreement = $agreement;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $recipients = ['digitalizacion.pnte@gmail.com', 'jackytamaris@gmail.com'];
        foreach ($recipients as $email) {
            Mail::to($email)->send(new EndDateNotificationMail($this->agreement));
        }
    }
}
