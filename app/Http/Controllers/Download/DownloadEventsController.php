<?php

namespace App\Http\Controllers\Download;

use App\Exports\EventExport;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DownloadEventsController extends Controller
{
    public function exportEvents(Request $request)
    {
        try {

            ini_set('memory_limit', '2G');
            set_time_limit(300);

            $filters = [
                'name'       => $request->input('name'),
                'year'       => $request->input('year'),
                'date'       => $request->input('date'),
                'rangeDate'  => $request->input('rangeDate'),
                'city'       => $request->input('city'),
                'province'   => $request->input('province'),
                'district'   => $request->input('district'),
                'pnte'       => $request->input('pnte'),
                'modalidad'  => $request->input('modalidad'),
            ];

            $query = Event::query();

            // 🔥 usamos NUEVO scope con filtros
            $query->withAdvisoryRangeDate($filters);

            $events = [];
            $globalIndex = 1;

            $query->chunk(1000, function ($rows) use (&$events, &$globalIndex) {
                foreach ($rows as $event) {

                    $modalidad = $event->link ? 'PRESENCIAL' : 'VIRTUAL';

                    $events[] = [
                        'index'       => $globalIndex++,
                        'office'      => $event->officePnte->office,
                        'area'        => $event->officePnte->name,
                        'title'       => $event->title,
                        'city'        => $event->region->name ?? null,
                        'province'    => $event->province->name ?? null,
                        'district'    => $event->district->name ?? null,
                        'place'       => $event->place ? strip_tags($event->place) : '-',

                        'date' => $event->dateStart != $event->dateEnd
                            ? 'Desde: ' . Carbon::parse($event->dateStart)->format('d/m/Y')
                            . ' Hasta: ' . Carbon::parse($event->dateEnd)->format('d/m/Y')
                            : Carbon::parse($event->dateStart)->format('d/m/Y'),

                        'hours' => $event->start
                            ? Carbon::parse($event->start)->format('H:i') . ' - ' . Carbon::parse($event->end)->format('H:i')
                            : 'Todo el día',

                        'description' => strip_tags($event->description),
                        'resultado'   => $event->resultado ? strip_tags($event->resultado) : '-',
                        'nameUser'    => $event->nameUser ?? '-',
                        'modalidad'   => $modalidad,
                        'canceled'           => $event->canceled,
                        'rescheduled'        => $event->rescheduled,
                    ];
                }
            });

            return Excel::download(new EventExport($events), 'eventos-pnte.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al generar el reporte.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
