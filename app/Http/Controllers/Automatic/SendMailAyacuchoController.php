<?php

namespace App\Http\Controllers\Automatic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\EnviarCorreosAyacuchoJob;
use App\Jobs\SendEmailArrayJob;

class SendMailAyacuchoController extends Controller
{
    public function sendEmailsAyacucho(Request $request)
    {
        $correos = $request->input('correos');

        foreach ($correos as $correo) {
            EnviarCorreosAyacuchoJob::dispatch($correo);
        }

        return response()->json(['message' => 'Correos enviados con éxito'], 200);

    }



    public function sendEmailsAyacuchoArray(Request $request)
    {
        $correos = $request->input('correos');
        $data = ['mensaje' => 'Este es un mensaje de prueba'];

        foreach ($correos as $email) {
            SendEmailArrayJob::dispatch($email, $data);
        }

        return response()->json(['message' => 'Correos enviados...']);
    }


}


// {
//     "correos": [
//         {"empresa": "Los ositos EIRL",  "email": "jloo6778@gmail.com" },
//         {"empresa": "Gatitos SAC", "email": "jackytamaris@gmail.com" }
//     ]
// }  php artisan queue:work


// 2
// {
//     "correos": [
//         "jackytamaris@gmail.com", "jloo6778@gmail.com", "ozambrano@produce.gob.pe", "tuempresa_temp265@produce.gob.pe"
//         ]
//   }
