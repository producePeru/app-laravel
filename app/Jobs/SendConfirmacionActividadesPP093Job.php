<?php

namespace App\Jobs;

use App\Models\ActividadPnte;
use App\Models\SedDescripcion;  // ← Agregar
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

  public function __construct(array $payloadData, string $mailer)
  {
    $this->payloadData = $payloadData;
    $this->mailer = $mailer;
  }

  public function handle()
  {
    try {
      $correoDestino = $this->payloadData['correo_electronico'] ?? null;

      if (!$correoDestino) {
        Log::warning("No se pudo procesar el Job de correo: Falta el campo 'correo_electronico'.");
        return;
      }

      $dataUsuario = [
        'nombres'      => $this->payloadData['nombres'] ?? 'Usuario',
        'ruc'          => $this->payloadData['ruc'] ?? '',
        'razon_social' => $this->payloadData['razon_social'] ?? '',
      ];

      $actividadesDetalle = [];
      $actividades = $this->payloadData['actividades'] ?? [];

      foreach ($actividades as $act) {
        $actividadBase = ActividadPnte::where('slug', $act['slug'])->first();

        if ($actividadBase) {
          // ← Buscar SedDescripcion usando slug_actividad_pnte
          $sedDescripcion = SedDescripcion::where('slug_actividad_pnte', $actividadBase->slug)->first();

          $actividadesDetalle[] = [
            'tema'                 => $actividadBase->tema,
            'entidad_organizadora' => $actividadBase->entidad_organizadora ?? 'Plataforma PNTE',
            'lugar'                => $actividadBase->lugar ?? 'Virtual',
            'link'                 => $actividadBase->link,
            'fecha_seleccionada'   => date('d/m/Y', strtotime($act['fecha_seleccionada'])),
            'horario_inicio'       => $act['horario_inicio'],
            'horario_fin'          => $act['horario_fin'],
            'mensaje_correo'       => $sedDescripcion->mensaje_correo ?? null, // ← Agregar
          ];
        }
      }

      if (!empty($actividadesDetalle)) {
        Mail::mailer($this->mailer)
          ->to($correoDestino)
          ->send(new ConfirmacionRegistroPP093Mail($dataUsuario, $actividadesDetalle));
      }
    } catch (\Exception $e) {
      Log::error("Error al ejecutar SendConfirmacionActividadesPP093Job para {$correoDestino}: " . $e->getMessage());
      throw $e;
    }
  }
}
