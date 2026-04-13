<?php

namespace App\Exports;

use App\Models\MPDiagnostico;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles,
    WithColumnWidths
};

class ParticipantDiagnosticExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles
{

    protected $questions;
    protected $participants;

    public function __construct($participants)
    {
        $this->participants = $participants;

        $this->questions = MPDiagnostico::with('options') // 👈
            ->where('status', 1)
            ->where('type', '!=', 'l')
            ->orderBy('position', 'ASC')
            ->get();
    }

    public function collection()
    {
        return $this->participants;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 25,
            'C' => 25,
            'D' => 15,
            'E' => 15,
            'F' => 18,
            'G' => 18,
            'H' => 30,
            'I' => 18,
            'J' => 25,
            'K' => 25,
            'L' => 25,
            'M' => 20,
        ];
    }

    public function headings(): array
    {
        $baseHeaders = [
            'N°',
            'NOMBRES',
            'APELLIDOS',
            'FECHA NACIMIENTO',
            'CELULAR',
            'TIPO DOCUMENTO',
            'N° DOCUMENTO',
            'EMAIL',
            'RUC',
            'ACTIVIDAD',
            'RUBRO',
            'SECTOR ECONÓMICO',
            'FECHA DEL DIAGNÓSTICO'
        ];

        $dynamicHeaders = $this->questions->pluck('label')->toArray();

        return array_merge($baseHeaders, $dynamicHeaders);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A' => ['alignment' => ['horizontal' => 'center']],
            'D' => ['alignment' => ['horizontal' => 'center']],
            'E' => ['alignment' => ['horizontal' => 'center']],
            'F' => ['alignment' => ['horizontal' => 'center']],
            'G' => ['alignment' => ['horizontal' => 'center']],
            'I' => ['alignment' => ['horizontal' => 'center']],
            'M' => ['alignment' => ['horizontal' => 'center']]
        ];
    }

    protected $counter = 0;

    public function map($participant): array
    {
        $this->counter++;

        $lastDiagnosticoAt = $participant->diagnosticoResponses
            ->max('created_at');

        // 👇 groupBy para soportar respuestas múltiples
        $responses = $participant->diagnosticoResponses
            ->groupBy('question_id');

        $row = [
            $this->counter,
            $participant->names,
            $participant->last_name . ' ' . $participant->middle_name,
            Carbon::parse($participant->date_of_birth)->format('d/m/Y'),
            $participant->phone,
            optional($participant->typeDocument)->avr,
            $participant->doc_number,
            $participant->email,
            $participant->ruc,
            optional($participant->comercialActivity)->name,
            optional($participant->rubro)->name,
            optional($participant->economicSector)->name,
            $lastDiagnosticoAt ? Carbon::parse($lastDiagnosticoAt)->format('d/m/Y H:i') : null,
        ];

        foreach ($this->questions as $question) {
            $questionResponses = $responses->get($question->id);

            if (!$questionResponses || $questionResponses->isEmpty()) {
                $row[] = null;
                continue;
            }

            // TEXTO LIBRE
            if ($question->type === 't') {
                $row[] = $questionResponses->first()->answer_text;
                continue;
            }

            // OPCIÓN ÚNICA o MÚLTIPLE
            $labels = $questionResponses
                ->map(fn($r) => $r->option?->name)
                ->filter()
                ->values();

            $row[] = $labels->count() > 1
                ? $labels->map(fn($label) => "- {$label}")->implode("\n")
                : $labels->first();
        }

        return $row;
    }
}
