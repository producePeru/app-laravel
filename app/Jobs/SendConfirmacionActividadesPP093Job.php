<?php

namespace App\Jobs;

use App\Models\ActividadPnte;
use App\Models\SedDescripcion;
use App\Mail\ConfirmacionRegistroPP093Mail;
use App\Jobs\SendRecordatorioPP093Job; // ← Importamos el Job del recordatorio
use Carbon\Carbon;                     // ← Importamos Carbon para las fechas
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
    $this->mailer      = $mailer;
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
        $actividadBase = ActividadPnte::find($act['actividad_id']);

        if ($actividadBase) {
          $sedDescripcion = SedDescripcion::where('slug_actividad_pnte', $actividadBase->slug)->first();

          $actividadItem = [
            'id'                   => $actividadBase->id,
            'slug'                 => $actividadBase->slug,
            'tema'                 => $actividadBase->tema,
            'entidad_organizadora' => $actividadBase->entidad_organizadora ?? 'Plataforma PNTE',
            'lugar'                => $actividadBase->lugar ?? 'Virtual',

            // EL LINK DE MEET SE MANTIENE EXACTAMENTE IGUAL PARA AMBOS CORREOS
            'link_meet'            => $actividadBase->link,

            // Enlace hacia la evaluación / test de entrada
            'link_test' => 'https://inscripcion.soporte-pnte.com/pp093-test-entrada/'
              . $act['slug']
              . '?'
              . http_build_query([
                'id'        => $act['actividad_id'],
                'date'      => $act['fecha_seleccionada'],
                'hourStart' => $act['horario_inicio'],
                'hourEnd'   => $act['horario_fin'],
              ]),

            'fecha_seleccionada' => date('d/m/Y', strtotime($act['fecha_seleccionada'])),
            'horario_inicio'     => $act['horario_inicio'],
            'horario_fin'        => $act['horario_fin'],
            'mensaje_correo'     => $sedDescripcion->mensaje_correo ?? null,
          ];

          $actividadesDetalle[] = $actividadItem;

          // =============================================================
          // PROGRAMAR EL RECORDATORIO 2 HORAS ANTES DEL EVENTO
          // =============================================================
          try {
            // Con los datos de tu payload: "2026-07-24" y "12:00"
            $fechaHoraEvento = Carbon::createFromFormat(
              'Y-m-d H:i',
              "{$act['fecha_seleccionada']} {$act['horario_inicio']}",
              config('app.timezone', 'America/Lima')
            );

            // Resta 2 horas (para enviar el correo a las 10:00 AM)
            $fechaHoraEnvio = $fechaHoraEvento->copy()->subHours(2);

            // Solo desparrama en la cola si la fecha programada es futura
            if ($fechaHoraEnvio->isFuture()) {
              SendRecordatorioPP093Job::dispatch(
                $correoDestino,
                $dataUsuario,
                $actividadItem,
                $this->mailer
              )->delay($fechaHoraEnvio);
            }
          } catch (\Exception $eRecordatorio) {
            Log::error("Error calculando recordatorio para {$correoDestino}: " . $eRecordatorio->getMessage());
          }
        }
      }

      // Enviar correo de confirmación inmediato
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
