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

            $filters = [
                'name'      => $request->input('name'),
                'year'      => $request->input('year'),
                // 'asesor'    => $request->input('asesor'),
                'dateStart' => $request->input('dateStart'),
                'dateEnd'   => $request->input('dateEnd'),
                'offices'   => $request->input('offices'),
                'type'      => $request->input('type')
            ];

            $userRole = getUserRole();
            $roleIds  = $userRole['role_id'];
            $userId   = $userRole['user_id'];

            $query = Event::query();

            $query->withAdvisoryRangeDate($filters);

            ini_set('memory_limit', '2G');
            set_time_limit(300);

            $events = [];
            $globalIndex = 1;

            $query->chunk(1000, function ($rows) use (&$events, &$globalIndex) {
                foreach ($rows as $event) {
                    $events[] = [
                        'index'             => $globalIndex++,
                        'office'            => $event->officePnte->office,
                        'area'              => $event->officePnte->name,
                        'title'             => $event->title,
                        'city'              => $event->region->name ?? null,
                        'place'             => $event->place ? strip_tags($event->place) : '-',
                        'date'              => $event->dateStart != $event->dateEnd ? 'Desde: ' . Carbon::parse($event->dateStart)->format('d/m/Y') . " " . 'Hasta: ' . Carbon::parse($event->dateEnd)->format('d/m/Y') : Carbon::parse($event->dateStart)->format('d/m/Y'),
                        'hours'             => $event->start ? Carbon::parse($event->start)->format('H:i') . ' - ' . Carbon::parse($event->end)->format('H:i') : 'Todo el día',
                        'description'       => strip_tags($event->description),
                        'resultado'         => $event->resultado ? strip_tags($event->resultado) : '-',
                        'nameUser'          => $event->nameUser ? strip_tags($event->nameUser) : '-',
                        // 'link'              => $event->link,
                    ];
                }
            });

            // return $events;

            return Excel::download(new EventExport($events), 'eventos-pnte.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrio un error al generar el reporte.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
