<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;

class GoogleCalendarService
{
    protected $client;
    protected $calendar;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(config('google.credentials_file'));
        $this->client->addScope(Calendar::CALENDAR);

        $this->calendar = new Calendar($this->client);
    }

    public function createEvent(string $idCalendar, array $eventData)
    {
        if (!$idCalendar) {
            throw new \Exception("El ID del calendario no se encuentra configurado.");
        }

        $event = new \Google\Service\Calendar\Event($eventData);
        return $this->calendar->events->insert($idCalendar, $event);
    }

    public function listEvents($calendarId, $maxResults = 50, $pageToken = null, $search = null)
    {
        if (!$calendarId) {
            throw new \Exception("El ID del calendario no se encuentra configurado correctamente.");
        }

        // Configurar parámetros para la solicitud
        $params = [
            'maxResults' => $maxResults,
            'orderBy' => 'startTime',
            'singleEvents' => true,
        ];

        // Agregar el token de paginación si existe
        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        // Filtrar por término de búsqueda o fecha si existe
        if ($search) {
            if (preg_match('/\d{2}\/\d{2}\/\d{4}/', $search)) {
                $searchDate = \DateTime::createFromFormat('d/m/Y', $search);
                if ($searchDate) {
                    $params['timeMin'] = $searchDate->format('Y-m-d\TH:i:s\Z');
                    $params['timeMax'] = $searchDate->modify('+1 day')->format('Y-m-d\TH:i:s\Z');
                }
            } else {
                $params['q'] = $search;
            }
        }

        // Obtener los eventos
        $events = $this->calendar->events->listEvents($calendarId, $params);

        // Formatear los eventos
        $formattedEvents = [];
        foreach ($events->getItems() as $event) {
            $formattedEvents[] = [
                'id' => $event->getId(),
                'summary' => $event->getSummary(),
                'start' => $event->getStart()->getDateTime() ?? $event->getStart()->getDate(),
                'end' => $event->getEnd()->getDateTime() ?? $event->getEnd()->getDate(),
                'description' => $event->getDescription(),
                'color' => $event->colorId
            ];
        }

        // Ordenar los eventos por fecha de inicio descendente
        usort($formattedEvents, function ($a, $b) {
            return strtotime($b['start']) - strtotime($a['start']);
        });

        // Crear estructura de paginación similar a Laravel
        $pagination = [
            'data' => $formattedEvents,
            'current_page' => $pageToken ? (int) $pageToken : 1,
            'per_page' => $maxResults,
            'next_page_token' => $events->getNextPageToken(),
            'has_more_pages' => $events->getNextPageToken() !== null,
            'total' => count($formattedEvents),
        ];

        return $pagination;
    }


    public function deleteEvent($eventId, $calendarId = null)
    {
        $calendarId = $calendarId ?? config('google.calendar_id');

        if (!$calendarId) {
            throw new \Exception("El ID del calendario no se encuentra configurado correctamente.");
        }

        if (!$eventId) {
            throw new \Exception("El ID del evento es requerido para eliminar.");
        }

        try {
            $this->calendar->events->delete($calendarId, $eventId);
            return [
                'success' => true,
                'message' => "El evento con ID {$eventId} ha sido eliminado exitosamente."
            ];
        } catch (\Google_Service_Exception $e) {
            throw new \Exception("Error al intentar eliminar el evento: " . $e->getMessage());
        }
    }
}
