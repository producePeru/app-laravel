<?php

namespace App\Jobs;

use App\Models\ActividadPnte;
use App\Mail\ConfirmacionRegistroPP093Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendConfirmacionActividadesPP093Job implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected $payloadData;
  protected $mailer;

  /**
   * Crear una nueva instancia del Job.
   *
   * @param array $payloadData Datos provenientes del Request
   * @param string $mailer Remitente seleccionado ('digitalizacion', 'capacitaciones', etc.)
   */
  public function __construct(array $payloadData, string $mailer)
  {
    $this->payloadData = $payloadData;
    $this->mailer = $mailer;
  }

  /**
   * Ejecutar el trabajo en la cola.
   */
  public function handle()
  {
    try {
      $correoDestino = $this->payloadData['correo_electronico'] ?? null;

      if (!$correoDestino) {
        Log::warning("No se pudo procesar el Job de correo: Falta el campo 'correo_electronico'.");
        return;
      }

      // 1. Estructuramos los datos básicos del usuario para la plantilla
      $dataUsuario = [
        'nombres'      => $this->payloadData['nombres'] ?? 'Usuario',
        'ruc'          => $this->payloadData['ruc'] ?? '',
        'razon_social' => $this->payloadData['razon_social'] ?? '',
      ];

      $actividadesDetalle = [];
      $actividades = $this->payloadData['actividades'] ?? [];

      // 2. Recorremos el array de actividades para enriquecerlo con el modelo ActividadPnte
      foreach ($actividades as $act) {
        $actividadBase = ActividadPnte::where('slug', $act['slug'])->first();

        if ($actividadBase) {
          $actividadesDetalle[] = [
            'tema'                 => $actividadBase->tema,
            'entidad_organizadora' => $actividadBase->entidad_organizadora ?? 'Plataforma PNTE',
            'lugar'                => $actividadBase->lugar ?? 'Virtual',
            'link'                 => $actividadBase->link,
            'fecha_seleccionada'   => date('d/m/Y', strtotime($act['fecha_seleccionada'])),
            'horario_inicio'       => $act['horario_inicio'],
            'horario_fin'          => $act['horario_fin'],
          ];
        }
      }

      // 3. Si se lograron mapear actividades válidas, procedemos al envío usando el mailer dinámico
      if (!empty($actividadesDetalle)) {
        Mail::mailer($this->mailer) // ✨ Aquí se aplica dinámicamente el remitente 'digitalizacion'
          ->to($correoDestino)
          ->send(new ConfirmacionRegistroPP093Mail($dataUsuario, $actividadesDetalle));
      }
    } catch (\Exception $e) {
      Log::error("Error al ejecutar SendConfirmacionActividadesPP093Job para {$correoDestino}: " . $e->getMessage());
      throw $e; // Relanzamos para que Laravel sepa que falló y aplique reintentos si es necesario
    }
  }
}
