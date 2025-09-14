<?php

namespace App\Http\Controllers\Google;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use App\Models\User;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;

class GoogleDriveServiceAccountController extends Controller
{
    private $contenedorId = '17Ex9ZWzl3r9mFicamIz542JG0eaAEipK'; // <-- cyberwow noviembre 2025

    private function getClient()
    {
        $client = new \Google_Client();
        $client->setAuthConfig(storage_path('app/google/pnte-cyber-wow-2025.json'));
        $client->addScope(\Google_Service_Drive::DRIVE);
        $client->useApplicationDefaultCredentials();
        return $client;
    }

    public function crearCarpeta(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'idCarpeta' => 'required|string', // ID de "cyberwow-noviembre-2025"
        ]);

        // Obtener usuario (ajusta si usas auth()->id())
        $user = User::first();

        // Cliente autorizado con tu OAuth
        $client = app(\App\Http\Controllers\Google\GoogleOAuthController::class)
            ->getAuthorizedClient($user);

        $drive = new Google_Service_Drive($client);

        // Metadata de la carpeta
        $folderMetadata = new Google_Service_Drive_DriveFile([
            'name' => $request->nombre,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$request->idCarpeta], // 📌 se crea dentro de cyberwow-noviembre-2025
        ]);

        // Crear carpeta
        $folder = $drive->files->create($folderMetadata, [
            'fields' => 'id, name, webViewLink'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Carpeta creada dentro de cyberwow-noviembre-2025',
            'id' => $folder->id,
            'name' => $folder->name,
            'webViewLink' => $folder->webViewLink,
        ]);
    }

    public function listarSubcarpetas(Request $request)
    {
        $request->validate([
            'idCarpeta' => 'required|string', // ID de "cyberwow-noviembre-2025"
        ]);

        // Usuario autenticado (ajusta si usas auth()->id())
        $user = User::first();

        // Cliente autorizado con OAuth
        $client = app(\App\Http\Controllers\Google\GoogleOAuthController::class)
            ->getAuthorizedClient($user);

        $drive = new Google_Service_Drive($client);

        // Consulta: solo carpetas dentro de la carpeta padre
        $query = sprintf(
            "'%s' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
            $request->idCarpeta
        );

        $results = $drive->files->listFiles([
            'q' => $query,
            'fields' => 'files(id, name, webViewLink)',
            'pageSize' => 100
        ]);

        $folders = [];
        foreach ($results->getFiles() as $folder) {
            $folders[] = [
                'id' => $folder->id,
                'name' => $folder->name,
                'webViewLink' => $folder->webViewLink,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Carpetas encontradas',
            'folders' => $folders,
        ]);
    }




    // SHEETS
    public function registrarEnSheet(Request $request)
    {
        $request->validate([
            'nombre'   => 'required|string',
            'apellido' => 'required|string',
            'dni'      => 'required',
            'ruc'      => 'required',
            'idCarpeta' => 'required|string',
        ]);

        // Usuario (ajusta si usas auth()->id())
        $user = User::first();

        // Cliente autorizado
        $client = app(\App\Http\Controllers\Google\GoogleOAuthController::class)
            ->getAuthorizedClient($user);

        $driveService = new Google_Service_Drive($client);
        $sheetsService = new Google_Service_Sheets($client);

        // 1. Buscar si ya existe el Sheet "mi-reporte" en la carpeta
        $query = sprintf(
            "'%s' in parents and name = 'mi-reporte' and mimeType = 'application/vnd.google-apps.spreadsheet' and trashed = false",
            $request->idCarpeta
        );

        $results = $driveService->files->listFiles([
            'q' => $query,
            'fields' => 'files(id, name, webViewLink)',
            'pageSize' => 1
        ]);

        if (count($results->getFiles()) === 0) {
            // 2. Si no existe, crear el Sheet dentro de la carpeta
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => 'mi-reporte',
                'mimeType' => 'application/vnd.google-apps.spreadsheet',
                'parents' => [$request->idCarpeta],
            ]);

            $file = $driveService->files->create($fileMetadata, [
                'fields' => 'id, name, webViewLink'
            ]);

            $spreadsheetId = $file->id;

            // Escribir encabezados
            $headers = ['#', 'Nombre', 'Apellido', 'DNI', 'RUC'];
            $sheetsService->spreadsheets_values->update(
                $spreadsheetId,
                'A1:E1',
                new Google_Service_Sheets_ValueRange(['values' => [$headers]]),
                ['valueInputOption' => 'RAW']
            );

            $rowIndex = 2; // primera fila para datos
        } else {
            // 3. Si ya existe, obtener el ID
            $file = $results->getFiles()[0];
            $spreadsheetId = $file->id;

            // Contar cuántas filas ya tiene
            $existing = $sheetsService->spreadsheets_values->get($spreadsheetId, 'A:A');
            $rowIndex = count($existing->getValues()) + 1;
        }

        // 4. Insertar nueva fila con los datos
        $rowData = [
            [$rowIndex - 1, $request->nombre, $request->apellido, $request->dni, $request->ruc]
        ];

        $sheetsService->spreadsheets_values->update(
            $spreadsheetId,
            "A{$rowIndex}:E{$rowIndex}",
            new Google_Service_Sheets_ValueRange(['values' => $rowData]),
            ['valueInputOption' => 'RAW']
        );

        return response()->json([
            'success' => true,
            'message' => 'Registro agregado en mi-reporte',
            'spreadsheetId' => $spreadsheetId,
            'webViewLink' => "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/edit"
        ]);
    }
}
