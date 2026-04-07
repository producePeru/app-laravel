<?php

namespace App\Http\Controllers\Word;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WordActividadesUgoController extends Controller
{
    public function wordUgoScheduledActivities()
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // Ejemplo contenido
        $section->addText("Reporte UGO");

        return response()->streamDownload(function () use ($phpWord) {
            $phpWord->save('php://output', 'Word2007');
        }, 'Eventos-UGO.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
