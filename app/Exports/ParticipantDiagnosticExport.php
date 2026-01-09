<?php

namespace App\Exports;

use App\Models\MPDiagnostico;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    WithMapping
};

class ParticipantDiagnosticExport implements FromCollection, WithHeadings, WithMapping
{

    protected $questions;
    protected $participants;

    public function __construct($participants)
    {
        $this->participants = $participants;

        $this->questions = MPDiagnostico::where('status', 1)
            ->where('type', '!=', 'l')    // excluir tipo 'l'
            ->orderBy('position', 'ASC')  // ordenar por position
            ->get();

    }

    public function collection()
    {
        return $this->participants;
    }

    public function headings(): array
    {
        $baseHeaders = [
            'ID',
            'Nombres',
            'Apellidos',
            'Fecha Nacimiento',
            'Celular',
            'Tipo Documento',
            'N° Documento',
            'Email',
            'RUC',
            'Actividad',
            'Rubro',
            'Sector Económico',
        ];

        $dynamicHeaders = $this->questions->pluck('label')->toArray();

        return array_merge($baseHeaders, $dynamicHeaders);
    }

    public function map($participant): array
    {
        $responses = $participant->diagnosticoResponses
            ->keyBy('question_id');

        $row = [
            $participant->id,
            $participant->names,
            $participant->last_name . ' ' . $participant->middle_name,
            optional($participant->birth_date)->format('d/m/Y'),
            $participant->phone,
            optional($participant->typeDocument)->avr,
            $participant->doc_number,
            $participant->email,
            $participant->ruc,
            optional($participant->comercialActivity)->name,
            optional($participant->rubro)->name,
            optional($participant->economicSector)->name,
        ];

        foreach ($this->questions as $question) {
            $response = $responses->get($question->id);

            $row[] = $response
                ? ($response->answer_text ?? $response->option?->name)
                : null;
        }

        return $row;
    }
}
