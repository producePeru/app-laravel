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
use Google\Service\Meet as GoogleMeetApi;
use Google\Service\Meet\ModerationRestrictions as MeetModerationRestrictions;
use Google\Service\Meet\Space as MeetSpace;
use Google\Service\Meet\SpaceConfig as MeetSpaceConfig;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleMeetCalendarService
{
  protected GoogleClient $client;
  protected GoogleCalendarApi $service;
  protected ?GoogleMeetApi $meet = null;
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

  /**
   * Cliente de la API de Meet (controles del anfitrión).
   * Requiere que el refresh token tenga el scope meetings.space.settings
   * (si devuelve 403, el dueño de la cuenta debe re-autorizar con ese scope).
   */
  protected function meet(): GoogleMeetApi
  {
    if (! $this->meet) {
      $client = new GoogleClient();
      $client->setClientId(config('services.google.client_id'));
      $client->setClientSecret(config('services.google.client_secret'));
      $client->setRedirectUri(config('services.google.redirect_uri'));
      $client->setAccessType('offline');
      $client->addScope(GoogleCalendarApi::CALENDAR_EVENTS);
      $client->addScope(GoogleMeetApi::MEETINGS_SPACE_SETTINGS);
      $client->addScope(GoogleMeetApi::MEETINGS_SPACE_READONLY);

      $refreshToken = config('services.google.refresh_token');
      if (! $refreshToken) {
        throw new \RuntimeException('Falta GOOGLE_OAUTH_REFRESH_TOKEN en .env.');
      }

      $client->fetchAccessTokenWithRefreshToken($refreshToken);
      $this->meet = new GoogleMeetApi($client);
    }

    return $this->meet;
  }

  /**
   * Deja habilitados los controles del anfitrión en la sala Meet:
   * - Administración del anfitrión: activada
   * - Compartir pantalla: permitido a colaboradores
   * - Enviar reacciones: permitido a colaboradores
   * - Mensajes de participantes: permitidos
   * - Tipo de acceso: Abierta
   * - Seguimiento de asistencia: genera informe por correo
   *
   * No tienen equivalente en la API y quedan con su valor por defecto:
   * "Chat continuo" y "El anfitrión debe unirse antes que los demás".
   */
  public function aplicarControlesAnfitrion(?string $meetLink): bool
  {
    if (! $meetLink || ! preg_match('~meet\.google\.com/([a-z0-9\-]+)~i', $meetLink, $m)) {
      return false;
    }

    try {
      $config = new MeetSpaceConfig();
      $config->setAccessType('OPEN');
      $config->setModeration('ON');
      $config->setModerationRestrictions(new MeetModerationRestrictions([
        'chatRestriction'     => 'NO_RESTRICTION',
        'presentRestriction'  => 'NO_RESTRICTION',
        'reactionRestriction' => 'NO_RESTRICTION',
      ]));
      $config->setAttendanceReportGenerationType('GENERATE_REPORT');

      $space = new MeetSpace();
      $space->setConfig($config);

      $this->meet()->spaces->patch('spaces/' . $m[1], $space, [
        'updateMask' => 'config.accessType,config.moderation,config.moderationRestrictions,config.attendanceReportGenerationType',
      ]);

      return true;
    } catch (Throwable $e) {
      Log::error("GoogleCalendarService: no se pudo aplicar controles de anfitrión a {$meetLink}: " . $e->getMessage());
      return false;
    }
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
   * Cada sesión obtiene su PROPIA sala de Meet (guardada en meetLink del item).
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

    $meetLink           = null;
    $eventosInsertados  = [];
    $horarioActualizado = array_values($horario); // Copia para actualizar los IDs

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

      // Cada sesión tiene su PROPIA sala de Meet (una por fecha+horario)
      $event->setConferenceData(new ConferenceData([
        'createRequest' => new CreateConferenceRequest([
          'requestId'             => 'meet-' . $actividad->id . '-' . $index . '-' . uniqid(),
          'conferenceSolutionKey' => new ConferenceSolutionKey([
            'type' => 'hangoutsMeet',
          ]),
        ]),
      ]));
      $params = ['conferenceDataVersion' => 1];

      try {
        $eventoCreado = $this->service->events->insert($this->calendarId, $event, $params);
        $eventosInsertados[] = $eventoCreado;

        // REEMPLAZAMOS EL ID TEMPORAL POR EL ID OFICIAL DE GOOGLE CALENDAR
        // y guardamos el link de SU propia sala de Meet en la sesión
        $horarioActualizado[$index]['id'] = $eventoCreado->getId();
        $horarioActualizado[$index]['meetLink'] = $eventoCreado->getHangoutLink();
        $this->aplicarControlesAnfitrion($eventoCreado->getHangoutLink());

        if ($index === 0) {
          $meetLink = $eventoCreado->getHangoutLink();
        }
      } catch (Throwable $e) {
        Log::error("GoogleCalendarService: error creando evento (actividad {$actividad->id}, fecha {$fecha}): " . $e->getMessage());
      }
    }

    // Cada evento lleva en su descripción el link de SU propia sala de Meet
    foreach ($eventosInsertados as $evento) {
      $linkEvento = $evento->getHangoutLink();
      if (! $linkEvento) {
        continue;
      }
      try {
        $evento->setDescription("TEMA:\n{$temaTexto}\n\nEXPOSITOR:\n{$expositorNombre}\n\nENLACE A LA SALA MEET:\n{$linkEvento}");
        $this->service->events->patch($this->calendarId, $evento->getId(), $evento);
      } catch (Throwable $eUpdate) {
        Log::error("GoogleCalendarService: Error actualizando descripción del evento {$evento->getId()}: " . $eUpdate->getMessage());
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

  /**
   * Los IDs temporales del drawer son Date.now() (solo dígitos);
   * los IDs reales de Google Calendar son alfanuméricos.
   */
  protected function esIdGoogle(string $id): bool
  {
    return $id !== '' && !ctype_digit($id);
  }

  /**
   * Arma título, descripción e inicio/fin de una sesión del cronograma.
   *
   * @return array{0: string, 1: string, 2: Carbon, 3: Carbon}
   */
  protected function datosSesion(ActividadPnte $actividad, array $sesion, ?string $tema, ?string $meetLink): array
  {
    $titulo    = $this->construirTitulo($actividad);
    $temaTexto = $tema ?: 'Sin tema especificado';

    $expositorNombre = $actividad->representante->name
      ?? $actividad->representante->nombre
      ?? 'Por asignar';

    $descripcion = "TEMA:\n{$temaTexto}\n\nEXPOSITOR:\n{$expositorNombre}";
    if ($meetLink) {
      $descripcion .= "\n\nENLACE A LA SALA MEET:\n{$meetLink}";
    }

    $inicio = Carbon::parse("{$sesion['fecha']} {$sesion['horaInicio']}", $this->timezone);
    $fin    = Carbon::parse("{$sesion['fecha']} {$sesion['horaFin']}", $this->timezone);

    return [$titulo, $descripcion, $inicio, $fin];
  }

  /**
   * Actualiza fecha/hora y datos de un evento ya existente en Google Calendar.
   */
  public function actualizarEvento(
    string $googleEventId,
    ActividadPnte $actividad,
    array $sesion,
    ?string $tema = null,
    ?string $meetLink = null
  ): bool {
    try {
      [$titulo, $descripcion, $inicio, $fin] = $this->datosSesion($actividad, $sesion, $tema, $meetLink);

      $patch = new GoogleEvent([
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

      $this->service->events->patch($this->calendarId, $googleEventId, $patch);
      return true;
    } catch (Throwable $e) {
      Log::error("GoogleCalendarService: Error al actualizar evento {$googleEventId} (actividad {$actividad->id}): " . $e->getMessage());
      return false;
    }
  }

  /**
   * Inserta una sesión nueva en Google Calendar con su PROPIA sala de Meet.
   *
   * @return array{id: string|null, meetLink: string|null}
   */
  protected function insertarSesion(
    ActividadPnte $actividad,
    array $sesion,
    ?string $tema
  ): array {
    try {
      [$titulo, $descripcion, $inicio, $fin] = $this->datosSesion($actividad, $sesion, $tema, null);

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

      // Sala de Meet propia para esta fecha+horario
      $event->setConferenceData(new ConferenceData([
        'createRequest' => new CreateConferenceRequest([
          'requestId'             => 'meet-' . $actividad->id . '-' . uniqid(),
          'conferenceSolutionKey' => new ConferenceSolutionKey([
            'type' => 'hangoutsMeet',
          ]),
        ]),
      ]));

      $creado = $this->service->events->insert($this->calendarId, $event, ['conferenceDataVersion' => 1]);
      $link = $creado->getHangoutLink();
      $this->aplicarControlesAnfitrion($link);

      if ($link) {
        try {
          $creado->setDescription($descripcion . "\n\nENLACE A LA SALA MEET:\n{$link}");
          $this->service->events->patch($this->calendarId, $creado->getId(), $creado);
        } catch (Throwable $eDesc) {
          Log::error("GoogleCalendarService: Error actualizando descripción del evento {$creado->getId()}: " . $eDesc->getMessage());
        }
      }

      return ['id' => $creado->getId(), 'meetLink' => $link];
    } catch (Throwable $e) {
      Log::error("GoogleCalendarService: Error creando sesión (actividad {$actividad->id}): " . $e->getMessage());
      return ['id' => null, 'meetLink' => null];
    }
  }

  /**
   * Sincroniza Google Calendar con el cronograma editado:
   * - sesiones quitadas del drawer → se eliminan de Google,
   * - sesiones existentes (con ID de Google) → se actualizan (patch),
   * - sesiones nuevas (ID temporal) → se crean con su PROPIA sala de Meet.
   *
   * @return array{meetLink: string|null, horarioActualizado: array}
   */
  public function sincronizarEventosParaActividad(
    ActividadPnte $actividad,
    array $horarioAnterior,
    array $horarioNuevo,
    ?string $tema = null
  ): array {
    $horarioAnterior = array_values($horarioAnterior);
    $horarioNuevo    = array_values($horarioNuevo);

    $idsAnteriores = [];
    foreach ($horarioAnterior as $vieja) {
      if (isset($vieja['id'])) {
        $idsAnteriores[] = (string) $vieja['id'];
      }
    }
    $idsNuevos = [];
    foreach ($horarioNuevo as $nueva) {
      if (isset($nueva['id'])) {
        $idsNuevos[] = (string) $nueva['id'];
      }
    }

    // 1. Sesiones quitadas → eliminar de Google
    foreach ($horarioAnterior as $vieja) {
      $gid = (string) ($vieja['id'] ?? '');
      if ($gid !== '' && !in_array($gid, $idsNuevos, true) && $this->esIdGoogle($gid)) {
        $this->eliminarEvento($gid);
      }
    }

    // 2. Existentes → patch (con el link de SU sesión), nuevas → insert con Meet propio
    $meetLink = $actividad->link;
    $horarioActualizado = $horarioNuevo;

    foreach ($horarioActualizado as $index => &$item) {
      $gid = (string) ($item['id'] ?? '');
      if ($gid !== '' && in_array($gid, $idsAnteriores, true) && $this->esIdGoogle($gid)) {
        $this->actualizarEvento($gid, $actividad, $item, $tema, $item['meetLink'] ?? $meetLink);
        continue;
      }

      $creado = $this->insertarSesion($actividad, $item, $tema);
      if (! empty($creado['id'])) {
        $item['id'] = $creado['id'];
        $item['meetLink'] = $creado['meetLink'];
        if (empty($meetLink)) {
          // meetLink global (compatibilidad): el de la primera sesión con sala
          $meetLink = $creado['meetLink'];
        }
      }
    }
    unset($item);

    return [
      'meetLink'           => $meetLink,
      'horarioActualizado' => $horarioActualizado,
    ];
  }
}
