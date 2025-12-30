<?php

namespace App\Http\Controllers;

use App\Models\GoogleVideosPnte;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Google_Client;
use Google_Service_Drive;
use App\Models\User;

class GoogleDriveController extends Controller
{
    public function driveVideosPnte(Request $request)
    {
        try {

            $request->merge([
                'idCarpeta' => '1q0ts3X1eienNhx6W9mgBE1CiaHbhbHu5'
            ]);

            // validación
            $request->validate([
                'video' => 'required|file|mimetypes:video/mp4,video/webm,video/quicktime,video/x-msvideo|max:512000',
                'idCarpeta' => 'required|string',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:500',
            ]);

            // usuario autenticado real
            $user = $request->user();

            if (!$user || !$user->google_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Drive no está conectado.'
                ], 401);
            }

            // cliente google
            $client = app(\App\Http\Controllers\Google\GoogleOAuthController::class)
                ->getAuthorizedClient($user);

            $drive = new \Google_Service_Drive($client);

            // archivo
            $file = $request->file('video');
            $filePath = $file->getRealPath();

            $fileMetadata = new \Google_Service_Drive_DriveFile([
                'name' => $file->getClientOriginalName(),
                'description' => $request->description,
                'parents' => [$request->idCarpeta],
            ]);

            $uploaded = $drive->files->create(
                $fileMetadata,
                [
                    'data' => file_get_contents($filePath),
                    'mimeType' => $file->getMimeType(),
                    'uploadType' => 'multipart',
                    'fields' => 'id, name, mimeType, size, webViewLink, webContentLink'
                ]
            );

            // ============================
            //   GUARDAR EN MYSQL
            // ============================
            $registro = GoogleVideosPnte::create([
                'user_id'         => $user->id,
                'google_file_id'  => $uploaded->id,
                'file_name'       => $uploaded->name,
                'file_type'       => $uploaded->mimeType ?? null,
                'file_size'       => $uploaded->size ?? null,
                'web_view_link'   => $uploaded->webViewLink ?? null,
                'web_content_link' => $uploaded->webContentLink ?? null,
                'title'           => $request->title,
                'description'     => $request->description, // ✔ GUARDADO EN MYSQL
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Video subido correctamente a Google Drive',
                'file' => $uploaded,
                'registro' => $registro
            ]);
        } catch (\Google_Service_Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error en Google Drive',
                'error' => json_decode($e->getMessage(), true)
            ], $e->getCode() ?: 500);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error inesperado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getVideosPnte(Request $request)
    {
        try {

            // Usuario que hace la petición
            $user = $request->user();

            if (!$user || !$user->google_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Drive no está conectado.'
                ], 401);
            }

            // CLIENTE GOOGLE
            $client = app(\App\Http\Controllers\Google\GoogleOAuthController::class)
                ->getAuthorizedClient($user);

            $drive = new \Google_Service_Drive($client);

            // === 1. OBTENER TODOS LOS VIDEOS DE MYSQL ===
            $videos = \App\Models\GoogleVideosPnte::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $respuesta = [];

            foreach ($videos as $video) {

                $videoInfo = [
                    'id'              => $video->id,
                    'google_file_id'  => $video->google_file_id,
                    'title'           => $video->title,
                    'description'     => $video->description,
                    'file_name'       => $video->file_name,
                    'created_at'      => $video->created_at,
                    'updated_at'      => $video->updated_at,
                ];

                try {


                    // === 2. CONSULTAR SI EL VIDEO EXISTE EN GOOGLE DRIVE ===
                    $file = $drive->files->get($video->google_file_id, [
                        'fields' => 'id, name, mimeType, size, webViewLink, webContentLink, thumbnailLink, videoMediaMetadata'
                    ]);

                    $duration = null;

                    // Convertir duración (ms → segundos)
                    if (isset($file->videoMediaMetadata->durationMillis)) {
                        $ms = $file->videoMediaMetadata->durationMillis;
                        $seconds = floor($ms / 1000);

                        // Formato HH:MM:SS
                        $duration = gmdate("H:i:s", $seconds);
                    }

                    // Respuesta final
                    $videoInfo['google'] = [
                        'exists'           => true,
                        'name'             => $file->name,
                        'mimeType'         => $file->mimeType,
                        'size'             => $file->size,
                        'webViewLink'      => $file->webViewLink,
                        'webContentLink'   => $file->webContentLink,

                        // Miniaturas
                        'thumbnail'        => $file->thumbnailLink ?? null,
                        'thumbnail_large'  => $file->thumbnailLink
                            ? $file->thumbnailLink . '=w1000'
                            : null,

                        // Duración del video
                        'duration'         => $duration,

                        // Link de descarga directo
                        'downloadLink'     => "https://www.googleapis.com/drive/v3/files/{$file->id}?alt=media",
                    ];
                } catch (\Google_Service_Exception $e) {

                    // === 3. SI EL ARCHIVO NO ESTÁ EN GOOGLE DRIVE ===
                    if ($e->getCode() === 404) {
                        $videoInfo['google'] = [
                            'exists' => false,
                            'error'  => 'El video fue eliminado, renombrado o movido en Google Drive.'
                        ];
                    } else {
                        throw $e; // otros errores se manejan abajo
                    }
                }

                $respuesta[] = $videoInfo;
            }

            return response()->json([
                'success' => true,
                'message' => 'Lista de videos',
                'data'    => $respuesta
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error inesperado',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
