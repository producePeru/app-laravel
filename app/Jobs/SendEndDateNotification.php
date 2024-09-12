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

class SendEndDateNotification implements ShouldQueue
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
        $recipients = ['mmelendez@produce.gob.pe', 'rsantisteban@produce.gob.pe', 'tuempresa_temp401@produce.gob.pe'];
        foreach ($recipients as $email) {
            Mail::to($email)->send(new EndDateNotificationMail($this->agreement));
        }
    }
}
