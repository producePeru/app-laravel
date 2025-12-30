<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ResumenUsuariosExport;
use App\Models\CyberwowLeader;
use App\Models\CyberwowParticipant;
use App\Models\Fair;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

class DownloadLiderAsesorController extends Controller
{
    public function resumenPorUsuarios($slug)
    {
        $fair = Fair::where('slug', $slug)->firstOrFail();

        // 1) Sacamos todos los líderes vinculados a este fair
        $leaders = CyberwowLeader::where('wow_id', $fair->id)
            ->pluck('user_id');

        // 2) Obtenemos la info de cada usuario líder ordenados por lastname DESC
        $usuarios = User::whereIn('id', $leaders)
            ->select('id', 'name', 'lastname', 'middlename')
            ->orderBy('lastname', 'desc')
            ->get();

        $resultados = [];
        $i = 0;

        // Mapeo de supervisores
        $supervisores = [
            'cmf' => 'CYNTHIA MARISOL MOLERO FLORES',
            'eco' => 'ERIKA LISBETH CHOY ORTIZ',
            'hqp' => 'HANNAH MARIA QWISTGAARD PANICCIA',
            'kps' => 'KATHIA IRIS PINEDO SAONA',
        ];

        foreach ($usuarios as $user) {
            // 3) Buscar todos los participantes asignados a este líder en este evento
            $query = CyberwowParticipant::where('event_id', $fair->id)
                ->where('user_id', $user->id);

            $total = $query->count() ?: 0; // Asignar 0 si no hay valor
            $completados = (clone $query)->whereNotNull('paso3')->count() ?: 0; // Asignar 0 si no hay valor
            $pendientes = (clone $query)->whereNull('paso3')->count() ?: 0; // Asignar 0 si no hay valor

            // 4️⃣ Buscar supervisor del líder actual
            $leaderRecord = CyberwowLeader::where('wow_id', $fair->id)
                ->where('user_id', $user->id)
                ->select('supervisor')
                ->first();

            $supervisor = $leaderRecord->supervisor ?? null; // Valor por defecto si no existe
            $nombreSupervisor = isset($supervisores[$supervisor]) ? $supervisores[$supervisor] : null; // Asignar nombre del supervisor

            $resultados[] = [
                'idx' => $i + 1, // Asignar índice de la fila (1-based)
                'nombre' => mb_strtoupper(trim("{$user->name} {$user->lastname} {$user->middlename}")),
                'asignadas' => $total,
                'completadas' => $completados,
                'pendientes' => $pendientes,
                'productividad' => $this->calcularProductividad($total, $completados), // Llamar a una función para calcular productividad
                'supervisor' => $nombreSupervisor ?? '-',
            ];

            $i++;
        }

        // Ordenar resultados por 'supervisor' de manera descendente
        $resultados = collect($resultados)->sortBy(function ($item) {
            return $item['supervisor'] === null ? PHP_INT_MAX : $item['supervisor'];
        })->values()->all();

        // Reasignar índices para mantener la secuencia 1, 2, 3, ...
        foreach ($resultados as $index => &$resultado) {
            $resultado['idx'] = $index + 1; // Asignar índice nuevo (1-based)
        }

        // Generar y descargar el archivo Excel
        return Excel::download(new ResumenUsuariosExport($resultados), 'resumen_usuarios.xlsx');
    }


    private function calcularProductividad($total, $completados)
    {
        if ($total == 0) return 'Baja'; // Si no hay asignadas, consideramos baja productividad
        $tasa = round(($completados / $total) * 100, 1);
        if ($tasa >= 70) {
            return 'Alta';
        } elseif ($tasa >= 40) {
            return 'Media';
        } else {
            return 'Baja';
        }
    }
}
