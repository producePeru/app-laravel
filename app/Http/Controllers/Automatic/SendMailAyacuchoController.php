<?php

namespace App\Http\Controllers\Automatic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\EnviarCorreosAyacuchoJob;

class SendMailAyacuchoController extends Controller
{
    public function sendEmailsAyacucho(Request $request)
    {
        $correos = $request->input('correos');

        foreach ($correos as $correo) {
            EnviarCorreosAyacuchoJob::dispatch($correo);
        }

        return response()->json(['message' => 'Correos enviados con éxito'], 200);
        // return "jajajaja";
    }
}


// {
//     "correos": [
//         {"empresa": "Los ositos EIRL",  "email": "jloo6778@gmail.com" },
//         {"empresa": "Gatitos SAC", "email": "jackytamaris@gmail.com" }
//     ]
// }
