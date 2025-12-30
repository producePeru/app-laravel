<?php

namespace App\Jobs;

use App\Mail\MypesCallArray;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EnviarCorreosAyacuchoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $correo;

    public function __construct($correo)
    {
        $this->correo = $correo;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Enviar el correo para cada empresa
        Mail::to($this->correo['email'])->send(new MypesCallArray($this->correo['empresa']));
    }
}
