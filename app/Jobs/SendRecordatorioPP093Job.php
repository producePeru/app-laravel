<?php

namespace App\Jobs;

use App\Mail\RecordatorioActividadPP093Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendRecordatorioPP093Job implements ShouldQueue
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
        ->send(new RecordatorioActividadPP093Mail($this->dataUsuario, $this->actividad));

      Log::info("Recordatorio enviado con éxito a {$this->correoDestino} para la actividad ID: {$this->actividad['id']}");
    } catch (Throwable $e) {
      Log::error("Error enviando recordatorio a {$this->correoDestino}: " . $e->getMessage());
      throw $e;
    }
  }
}
