<?php

namespace App\Jobs;

use App\Mail\RecordatorioPP093DesdeAdminMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendRecordatorioPP093DesdeAdminJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $correoDestino;
    protected array $dataUsuario;
    protected array $actividad;
    protected string $mailer;

    public function __construct(string $correoDestino, array $dataUsuario, array $actividad, string $mailer)
    {
        $this->correoDestino = $correoDestino;
        $this->dataUsuario   = $dataUsuario;
        $this->actividad     = $actividad;
        $this->mailer        = $mailer;
    }

    public function handle(): void
    {
        try {
            Mail::mailer($this->mailer)
                ->to($this->correoDestino)
                ->send(new RecordatorioPP093DesdeAdminMail($this->dataUsuario, $this->actividad));

            Log::info("Recordatorio (desde admin) enviado con éxito a {$this->correoDestino} para la actividad ID: {$this->actividad['id']}");
        } catch (Throwable $e) {
            Log::error("Error enviando recordatorio (desde admin) a {$this->correoDestino}: ".$e->getMessage());
            throw $e;
        }
    }
}