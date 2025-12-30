<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
  protected $client;
  protected $service;

  public function __construct()
  {
    $this->client = new Client();
    $this->client->setAuthConfig(base_path(env('GOOGLE_DRIVE_CREDENTIALS')));
    $this->client->addScope(Drive::DRIVE_FILE);

    $this->service = new Drive($this->client);
  }

  public function uploadFile($localPath, $fileName, $mimeType)
  {
    $fileMetadata = new Drive\DriveFile([
      'name' => $fileName,
    ]);

    // Si definiste carpeta
    if (env('GOOGLE_DRIVE_FOLDER_ID')) {
      $fileMetadata->setParents([env('GOOGLE_DRIVE_FOLDER_ID')]);
    }

    $content = file_get_contents($localPath);

    $file = $this->service->files->create(
      $fileMetadata,
      [
        'data' => $content,
        'mimeType' => $mimeType,
        'uploadType' => 'multipart',
        'fields' => 'id, name, mimeType, size, webViewLink, webContentLink',
      ]
    );

    return $file;
  }
}
