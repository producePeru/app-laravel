<?php

namespace App\Services;

use App\Models\ActividadPnte;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendarApi;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\ConferenceSolutionKey;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleMeetCalendarService
{
  protected GoogleClient $client;
  protected GoogleCalendarApi $service;
  protected string $calendarId;
  protected string $timezone;

  public function __construct()
  {
    $this->timezone   = config('app.timezone', 'America/Lima');
    $this->calendarId = config('services.google.calendar_id', 'primary');

    $this->client = new GoogleClient();
    $this->client->setClientId(config('services.google.client_id'));
    $this->client->setClientSecret(config('services.google.client_secret'));
    $this->client->setRedirectUri(config('services.google.redirect_uri'));
    $this->client->setAccessType('offline');
    $this->client->addScope(GoogleCalendarApi::CALENDAR_EVENTS);

    $refreshToken = config('services.google.refresh_token');

    if (!$refreshToken) {
      throw new \RuntimeException(
        'Falta GOOGLE_OAUTH_REFRESH_TOKEN en .env.'
      );
    }

    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
    $this->service = new GoogleCalendarApi($this->client);
  }

  protected function obtenerNombreComponente(?int $componenteId): string
  {
    $componentes = [
      1 => 'Acceso al financiamiento',
      2 => 'Desarrollo productivo',
      3 => 'Digitalización',
      4 => 'Gestión empresarial',
    ];

    return $componentes[$componenteId] ?? 'Capacitación PNTE';
  }

  protected function construirTitulo(ActividadPnte $actividad): string
  {
    $componenteId = $actividad->componente_id ?? $actividad->unidad;
    return $this->obtenerNombreComponente($componenteId);
  }

  /**
   * Crea eventos en Google Calendar y mapea los IDs generados dentro del array de horario.
   *
   * @return array{meetLink: string|null, horarioActualizado: array}
   */
  public function crearEventosParaActividad(ActividadPnte $actividad, array $horario, ?string $tema = null): array
  {
    if (empty($horario)) {
      return ['meetLink' => null, 'horarioActualizado' => $horario];
    }

    $titulo    = $this->construirTitulo($actividad);
    $temaTexto = $tema ?: 'Sin tema especificado';

    $expositorNombre = $actividad->representante->name
      ?? $actividad->representante->nombre
      ?? 'Por asignar';

    $meetLink             = null;
    $conferenceDataCreada = null;
    $eventosInsertados    = [];
    $horarioActualizado   = $horario; // Copia para actualizar los IDs

    foreach (array_values($horario) as $index => $item) {
      $fecha      = $item['fecha'];
      $horaInicio = $item['horaInicio'];
      $horaFin    = $item['horaFin'];

      $inicio = Carbon::parse("{$fecha} {$horaInicio}", $this->timezone);
      $fin    = Carbon::parse("{$fecha} {$horaFin}", $this->timezone);

      $descripcion = "TEMA:\n{$temaTexto}\n\nEXPOSITOR:\n{$expositorNombre}";

      $event = new GoogleEvent([
        'summary'     => $titulo,
        'description' => $descripcion,
        'start' => new EventDateTime([
          'dateTime' => $inicio->toRfc3339String(),
          'timeZone' => $this->timezone,
        ]),
        'end' => new EventDateTime([
          'dateTime' => $fin->toRfc3339String(),
          'timeZone' => $this->timezone,
        ]),
      ]);

      $params = [];

      if ($index === 0) {
        $event->setConferenceData(new ConferenceData([
          'createRequest' => new CreateConferenceRequest([
            'requestId'             => 'meet-' . $actividad->id . '-' . uniqid(),
            'conferenceSolutionKey' => new ConferenceSolutionKey([
              'type' => 'hangoutsMeet',
            ]),
          ]),
        ]));
        $params['conferenceDataVersion'] = 1;
      } elseif ($conferenceDataCreada) {
        $event->setConferenceData($conferenceDataCreada);
        $params['conferenceDataVersion'] = 1;
      }

      try {
        $eventoCreado = $this->service->events->insert($this->calendarId, $event, $params);
        $eventosInsertados[] = $eventoCreado;

        // REEMPLAZAMOS EL ID TEMPORAL POR EL ID OFICIAL DE GOOGLE CALENDAR
        $horarioActualizado[$index]['id'] = $eventoCreado->getId();

        if ($index === 0) {
          $conferenceDataCreada = $eventoCreado->getConferenceData();
          $meetLink             = $eventoCreado->getHangoutLink();
        }
      } catch (Throwable $e) {
        Log::error("GoogleCalendarService: error creando evento (actividad {$actividad->id}, fecha {$fecha}): " . $e->getMessage());
      }
    }

    // Si tenemos link de Meet, actualizamos la descripción de los eventos creados
    if ($meetLink) {
      $descripcionCompleta = "TEMA:\n{$temaTexto}\n\nEXPOSITOR:\n{$expositorNombre}\n\nENLACE A LA SALA MEET:\n{$meetLink}";

      foreach ($eventosInsertados as $evento) {
        try {
          $evento->setDescription($descripcionCompleta);
          $this->service->events->patch($this->calendarId, $evento->getId(), $evento);
        } catch (Throwable $eUpdate) {
          Log::error("GoogleCalendarService: error actualizando descripción del evento {$evento->getId()}: " . $eUpdate->getMessage());
        }
      }
    }

    return [
      'meetLink'           => $meetLink,
      'horarioActualizado' => $horarioActualizado,
    ];
  }

  /**
   * Elimina un evento de Google Calendar pasando directamente su ID.
   */
  public function eliminarEvento(string $googleEventId): bool
  {
    try {
      $this->service->events->delete($this->calendarId, $googleEventId);
      return true;
    } catch (Throwable $e) {
      Log::error("GoogleCalendarService: Error al eliminar evento {$googleEventId}: " . $e->getMessage());
      return false;
    }
  }
}
