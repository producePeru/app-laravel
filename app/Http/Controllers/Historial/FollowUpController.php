<?php

namespace App\Http\Controllers\Historial;

use App\Http\Controllers\Controller;
use App\Models\People;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;

class FollowUpController extends Controller
{
    public function registrationDay($dni)
    {
        try {
            $person = People::with(['user.cde', 'userUpdated.cde'])
                ->where('documentnumber', $dni)
                ->first();

            if (!$person) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró a la persona con ese DNI',
                    'status' => 404
                ]);
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => [
                    'registeredby' => $person->user
                        ? trim($person->user->name . ' ' . $person->user->lastname . ' ' . $person->user->middlename)
                        : 'Formulario',

                    'updatedby' => $person->userUpdated
                        ? trim($person->userUpdated->name . ' ' . $person->userUpdated->lastname . ' ' . $person->userUpdated->middlename)
                        : null,

                    'cde' => $person->user && $person->user->cde
                        ? $person->user->cde->name
                        : null,

                    'cdeUpdate' => $person->userUpdated && $person->userUpdated->cde
                        ? $person->userUpdated->cde->name
                        : null,

                    'date' => Carbon::parse($person->created_at)
                        ->locale('es')
                        ->timezone('America/Lima')
                        ->translatedFormat('d \\d\\e F \\d\\e Y H:i'),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener la información',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
