<?php

namespace App\Jobs;

use App\Models\WorkshopDetails;
use App\Mail\AcceptInvitationWorkshopDetails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class AcceptInvitationWorkshopJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $workshopDetail;
    public $email;
    public $workshop;
    public $mype;

    /**
     * Create a new job instance.
     */
    public function __construct(WorkshopDetails $workshopDetail, $email, $workshop, $mype)
    {
        $this->workshopDetail = $workshopDetail;
        $this->email = $email;
        $this->workshop = $workshop;
        $this->mype = $mype;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->email)
            ->send(new AcceptInvitationWorkshopDetails($this->workshop, $this->mype));
    }
}

