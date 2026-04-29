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
    private function getUnifiedEvents()
    {
        // ✅ Helper: expande un item por cada fecha del array dates
        $expandByDates = function (array $item, array $dates): \Illuminate\Support\Collection {
            if (empty($dates)) {
                return collect([array_merge($item, ['date' => null])]); // ✅ siempre tiene key 'date'
            }

            return collect($dates)->map(function ($date) use ($item) {
                return array_merge($item, ['date' => $date]);
            });
        };
        // 🔥 ATTENDANCE (UGO)
        $attendances = \App\Models\Attendance::select(
            'id',
            'title',
            'dates',
            'city_id',
            'province_id',
            'district_id',
            'address',
            'eventsoffice_id',
            'user_id',
            'visible',
            'resultados',
            'cancelado',
            'reprogramado',
            'unidad',
            'theme',
            'entidad_aliada',
            'entidad',
            'asesorId',
            'beneficiarios',
            'modality',
            'pasaje',
            'monto',
            'slug',
            'created_at'
        )
            ->with([
                'region:id,name',
                'provincia:id,name',
                'distrito:id,name',
                'pnte:id,name',
                'registrador:id,name,lastname,middlename',
                'asesor:id,name,lastname,middlename',
            ])
            ->withCount('attendanceList')
            ->get()
            ->flatMap(function ($item) use ($expandByDates) {
                $base = [
                    'id' => 'att-' . $item->id,
                    'tabla' => 'attendancelist',
                    'row_id' => $item->id,
                    'unidad' => $item->unidad ?? 'UGO',
                    'visible' => $item->visible ?? 0,
                    'estado' => null,
                    'tipo' => 'UGO',
                    'titulo' => $item->title ? mb_strtoupper($item->title, 'UTF-8') : null,
                    'tipoActividad' => $item->pnte->name ?? null,
                    'activityTheme' => $item->theme ?? null,
                    'entidad' => $item->entidad ?? null,
                    'entidad_aliada' => $item->entidad_aliada ?? null,
                    'resultados' => $item->resultados ?? null,
                    'cancelado' => $item->cancelado ?? null,
                    'reprogramado' => $item->reprogramado ?? null,
                    'city_id' => $item->city_id ?? null,
                    'region' => $item->region->name ?? null,
                    'provincia' => $item->provincia->name ?? null,
                    'distrito' => $item->distrito->name ?? null,
                    'direccion' => mb_strtoupper($item->address ?? '', 'UTF-8'),
                    'beneficiarios' => $item->beneficiarios ?? null,
                    'attendance_list_count' => $item->attendance_list_count,
                    'modalidad' => $item->modality ?? null,
                    'pasaje' => $item->pasaje ?? null,
                    'monto' => $item->monto ?? null,
                    'slug' => $item->slug ?? null,
                    'created_at' => $item->created_at
                        ? $item->created_at->format('d/m/Y H:i A')
                        : null,
                    'registrador' => $item->registrador
                        ? mb_strtoupper(
                            $item->registrador->name . ' ' .
                                $item->registrador->lastname . ' ' .
                                $item->registrador->middlename,
                            'UTF-8'
                        )
                        : null,
                    'asesor' => $item->asesor
                        ? mb_strtoupper(
                            $item->asesor->name . ' ' .
                                $item->asesor->lastname . ' ' .
                                $item->asesor->middlename,
                            'UTF-8'
                        )
                        : null,

                ];

                return $expandByDates($base, $item->dates ?? []);
            });

        // 🔥 UGSE
        $ugse = \App\Models\MPEvent::select(
            'id',
            'title',
            'dates',
            'city_id',
            'province_id',
            'district_id',
            'place',
            'user_id',
            'visible',
            'resultados',
            'cancelado',
            'reprogramado',
            'unidad',
            'modality_id',
            'component',
            'capacitador_id',
            'slug',
            'created_at'
        )
            ->with([
                'city:id,name',
                'province:id,name',
                'district:id,name',
                'modality:id,name',
                'capacitador:id,name'
            ])
            ->get()
            ->flatMap(function ($item) use ($expandByDates) {
                $base = [
                    'id' => 'ugse-' . $item->id,
                    'tabla' => 'mp_eventos',
                    'row_id' => $item->id,
                    'unidad' => $item->unidad ?? 'UGSE',
                    'visible' => $item->visible ?? 0,
                    'estado' => null,
                    'tipo' => 'UGSE',
                    'titulo' => $item->title ? mb_strtoupper($item->title, 'UTF-8') : null,
                    'tipoActividad' => null,
                    'resultados' => $item->resultados ?? null,
                    'cancelado' => $item->cancelado ?? null,
                    'reprogramado' => $item->reprogramado ?? null,

                    'component' => $item->component,
                    'capacitador' => $item->capacitador ? mb_strtoupper($item->capacitador->name, 'UTF-8')
                        : null,

                    'modalidad' => $item->modality->name ?? null,
                    'city_id' => $item->city_id ?? null,
                    'region' => $item->city->name ?? null,
                    'provincia' => $item->province->name ?? null,
                    'distrito' => $item->district->name ?? null,
                    'direccion' => mb_strtoupper($item->place ?? '', 'UTF-8'),
                    'registrador' => null,
                    'slug' => $item->slug ?? null,
                    'created_at' => $item->created_at
                        ? $item->created_at->format('d/m/Y H:i A')
                        : null,
                ];

                return $expandByDates($base, $item->dates ?? []);
            });

        // 🔥 FAIR
        $fairs = \App\Models\Fair::select(
            'id',
            'title',
            'dates',
            'city_id',
            'province_id',
            'district_id',
            'place',
            'user_id',
            'visible',
            'resultados',
            'cancelado',
            'reprogramado',
            'unidad',
            'fairtype_id',
            'hours',
            'cooperativa',
            'slug',
            'created_at'
        )
            ->with([
                'region:id,name',
                'provincia:id,name',
                'distrito:id,name',
                'fairType:id,name',
                'profile:id,user_id,name,lastname,middlename',
            ])
            ->get()
            ->flatMap(function ($item) use ($expandByDates) {
                $base = [
                    'id' => 'fair-' . $item->id,
                    'tabla' => 'fairs',
                    'row_id' => $item->id,
                    'unidad' => $item->unidad ?? 'UGO',
                    'visible' => $item->visible ?? 0,
                    'estado' => null,
                    'tipo' => 'FAIR',
                    'titulo' => $item->title ? mb_strtoupper($item->title, 'UTF-8') : null,
                    'tipoActividad' => $item->fairType->name ?? null,
                    'resultados' => $item->resultados ?? null,
                    'cancelado' => $item->cancelado ?? null,
                    'reprogramado' => $item->reprogramado ?? null,
                    'hours' => $item->hours ?? null,
                    'city_id' => $item->city_id ?? null,
                    'region' => $item->region->name ?? null,
                    'provincia' => $item->provincia->name ?? null,
                    'distrito' => $item->distrito->name ?? null,
                    'direccion' => mb_strtoupper($item->place ?? '', 'UTF-8'),
                    'cooperativa' => $item->cooperativa ?? null,
                    'slug' => $item->slug ?? null,

                    'registrador' => $item->profile
                        ? mb_strtoupper(
                            $item->profile->name . ' ' .
                                $item->profile->lastname . ' ' .
                                $item->profile->middlename,
                            'UTF-8'
                        )
                        : null,

                    'created_at' => $item->created_at
                        ? $item->created_at->format('d/m/Y H:i A')
                        : null,
                ];

                return $expandByDates($base, $item->dates ?? []);
            });

        return $attendances->merge($ugse)->merge($fairs);
    }

    public function exportEvents(Request $request)
    {
        $year      = $request->input('year');
        $date      = $request->input('date');
        $range     = $request->input('rangeDate');
        $city      = $request->input('city');
        $pnte      = $request->input('pnte');

        $collection = $this->getUnifiedEvents();

        // Aplicar los mismos filtros del index
        if ($city) {
            $collection = $collection->filter(
                fn($item) => isset($item['city_id']) && $item['city_id'] == $city
            );
        }

        if ($year) {
            $collection = $collection->filter(
                fn($item) => isset($item['date']) && substr($item['date'], 0, 4) == $year
            );
        }

        if ($date) {
            $collection = $collection->filter(
                fn($item) => isset($item['date']) && $item['date'] === $date
            );
        }

        if ($range && count($range) === 2) {
            [$start, $end] = $range;
            $collection = $collection->filter(function ($item) use ($start, $end) {
                if (!isset($item['date'])) return false;
                return $item['date'] >= $start && $item['date'] <= $end;
            });
        }

        if ($pnte) {
            $pnte = strtoupper($pnte);
            $collection = $collection->filter(function ($item) use ($pnte) {
                return isset($item['unidad']) && strtoupper($item['unidad']) === $pnte;
            });
        }

        // Agrupar igual que en index
        $grouped = $collection
            ->groupBy(fn($item) => $item['tabla'] . '-' . $item['row_id'])
            ->map(function ($group) {
                $first = $group->first();
                $dates = $group->pluck('date')->filter()->unique()->sort()->values()->toArray();
                return array_merge($first, [
                    'date'  => $dates[0] ?? null,
                    'dates' => $dates,
                ]);
            })
            ->sortByDesc('date')
            ->values();

        $filename = 'eventos_' . now()->format('Ymd_His') . '.xlsx';

        return (new EventExport($grouped))->download($filename);
    }
}
