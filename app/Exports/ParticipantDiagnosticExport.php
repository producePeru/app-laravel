<?php

namespace App\Exports;

use App\Models\MPDiagnostico;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ParticipantDiagnosticExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    protected $questions;

    protected $participants;

    public function __construct($participants)
    {
        $this->participants = $participants;

        $this->questions = MPDiagnostico::with('options') // 👈
            // ->where('status', 1)
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
            'N' => 22,
            'O' => 22,
            'P' => 22,
            'Q' => 15,
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
            'FECHA DEL DIAGNÓSTICO',
            'ASISTENCIA EN CAPACITACIONES',
            'HABILIDADES PERSONALES',
            'GESTIÓN EMPRESARIAL',
            'RESUMEN',
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
            'M' => ['alignment' => ['horizontal' => 'center']],
            'N' => ['alignment' => ['horizontal' => 'center']],
            'O' => ['alignment' => ['horizontal' => 'center']],
            'P' => ['alignment' => ['horizontal' => 'center']],
            'Q' => ['alignment' => ['horizontal' => 'center']],
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

        $genero = (int) $participant->gender_id;
        $habilidades = (int) ($participant->habilidades_personales ?? 0);
        $gestion = (int) ($participant->gestion_empresarial ?? 0);

        if ($genero === 1) {
            $resumen = 'No aplica';
        } elseif ($genero === 2) {
            if ($habilidades > 0 && $gestion > 0) {
                $resumen = 'Finalizado';
            } else {
                $resumen = 'En proceso';
            }
        } else {
            if ($habilidades > 0 && $gestion > 0) {
                $resumen = 'Finalizado';
            } else {
                $resumen = 'En proceso';
            }
        }

        $row = [
            $this->counter,
            $participant->names,
            $participant->last_name.' '.$participant->middle_name,
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
            $participant->shares ?? 0,
            $habilidades,
            $gestion,
            $resumen,
        ];

        foreach ($this->questions as $question) {
            $questionResponses = $responses->get($question->id);

            if (! $questionResponses || $questionResponses->isEmpty()) {
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
                ->map(fn ($r) => $r->option?->name)
                ->filter()
                ->values();

            $row[] = $labels->count() > 1
                ? $labels->map(fn ($label) => "- {$label}")->implode("\n")
                : $labels->first();
        }

        return $row;
    }
}
