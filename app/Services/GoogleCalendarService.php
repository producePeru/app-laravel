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

    public function createEvent(array $eventData)
    {
        $calendarId = config('google');

        // Depuración: Verifica el valor del calendarId
        if (!$calendarId) {
            throw new \Exception("El ID del calendario no se encuentra configurado correctamente.");
        }

        $event = new \Google\Service\Calendar\Event($eventData);
        return $this->calendar->events->insert(config('google.calendar_id'), $event);
    }

    public function listEvents($calendarId = null, $maxResults = 10)
    {
        $calendarId = $calendarId ?? config('google.calendar_id');

        // return $calendarId;

        if (!$calendarId) {
            throw new \Exception("El ID del calendario no se encuentra configurado correctamente.");
        }

        $events = $this->calendar->events->listEvents($calendarId, [
            'maxResults' => $maxResults,
            'orderBy' => 'startTime',
            'singleEvents' => true,
        ]);

        $formattedEvents = [];

        foreach ($events->getItems() as $event) {
            $formattedEvents[] = [
                'id' => $event->getId(),
                'summary' => $event->getSummary(),
                'start' => $event->getStart()->getDateTime() ?? $event->getStart()->getDate(),
                'end' => $event->getEnd()->getDateTime() ?? $event->getEnd()->getDate(),
            ];
        }

        return $formattedEvents;
    }
}
