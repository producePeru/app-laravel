<?php

namespace App\Services;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

class GoogleCalendarService
{
    protected $client;

    public function __construct()
    {
        // Configura el cliente de Google
        $this->client = new Google_Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect'));
        $this->client->addScope(Google_Service_Calendar::CALENDAR);
    }

    public function authenticate($authCode)
    {
        $accessToken = $this->client->fetchAccessTokenWithAuthCode($authCode);
        $this->client->setAccessToken($accessToken);

        return $accessToken;
    }

    public function createEvent($accessToken, $eventData)
    {
        $this->client->setAccessToken($accessToken);

        $service = new Google_Service_Calendar($this->client);

        $event = new Google_Service_Calendar_Event([
            'summary' => $eventData['summary'],
            'location' => $eventData['location'],
            'description' => $eventData['description'],
            'start' => [
                'dateTime' => $eventData['start'],
                'timeZone' => $eventData['timeZone'],
            ],
            'end' => [
                'dateTime' => $eventData['end'],
                'timeZone' => $eventData['timeZone'],
            ],
        ]);

        return $service->events->insert('primary', $event);
    }

    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }
}
