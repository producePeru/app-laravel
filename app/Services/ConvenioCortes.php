<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Cortes semestrales de seguimiento de un convenio.
 *
 * Regla: los cortes cierran el 30/06 y el 31/12.
 * - 1er corte: desde el inicio hasta el 30/06.
 * - 2do corte: del 01/07 al 31/12.
 * - Se repite hasta cubrir la fecha de fin (el último corte cierra
 *   en el fin de semestre que contiene a la fecha de fin).
 * - Con adenda, la fecha de fin se extiende y los cortes se siguen sumando.
 *
 * Ej.: inicio 10/03/2025, fin 10/03/2026 →
 *   10/03/2025–30/06/2025, 01/07/2025–31/12/2025, 01/01/2026–30/06/2026.
 */
class ConvenioCortes
{
    /**
     * Fecha de fin efectiva: la mayor entre el vencimiento y el
     * vencimiento de la última adenda.
     */
    public static function finEfectivo($convenio): ?Carbon
    {
        $fin = $convenio->vencimiento ? Carbon::parse($convenio->vencimiento)->startOfDay() : null;

        $adendas = $convenio->relationLoaded('adendas')
            ? collect($convenio->adendas)
            : collect($convenio->adendas()->get(['hasta']));

        $maxAdenda = $adendas->map(fn ($a) => $a->hasta ?? null)->filter()->max();

        if ($maxAdenda) {
            $maxAdenda = Carbon::parse($maxAdenda)->startOfDay();
            if (! $fin || $maxAdenda->greaterThan($fin)) {
                $fin = $maxAdenda;
            }
        }

        return $fin;
    }

    /**
     * Cierre del semestre que contiene a la fecha: 30/06 o 31/12.
     */
    public static function cierreSemestre(Carbon $fecha): Carbon
    {
        $fecha = $fecha->copy()->startOfDay();

        return $fecha->month <= 6
            ? $fecha->copy()->setDate($fecha->year, 6, 30)
            : $fecha->copy()->setDate($fecha->year, 12, 31);
    }

    /**
     * Genera los cortes entre el inicio y la fecha de fin.
     * Cada corte: ['numero', 'desde' (Y-m-d), 'hasta' (Y-m-d), 'es_final'].
     */
    public static function cortes(?string $inicio, ?Carbon $fin): array
    {
        if (! $inicio || ! $fin) {
            return [[
                'numero' => 1,
                'desde' => $inicio,
                'hasta' => $fin?->format('Y-m-d'),
                'es_final' => true,
            ]];
        }

        $desde = Carbon::parse($inicio)->startOfDay();
        $limite = self::cierreSemestre($fin);

        $cortes = [];
        $n = 1;
        while ($desde->lessThanOrEqualTo($limite)) {
            $hasta = self::cierreSemestre($desde);
            if ($hasta->greaterThan($limite)) {
                $hasta = $limite->copy();
            }
            $cortes[] = [
                'numero' => $n,
                'desde' => $desde->format('Y-m-d'),
                'hasta' => $hasta->format('Y-m-d'),
                'es_final' => $hasta->equalTo($limite),
            ];
            $desde = $hasta->copy()->addDay()->startOfDay();
            $n++;
        }

        return $cortes !== [] ? $cortes : [[
            'numero' => 1,
            'desde' => Carbon::parse($inicio)->format('Y-m-d'),
            'hasta' => $fin->format('Y-m-d'),
            'es_final' => true,
        ]];
    }

    /**
     * Resumen completo: cortes con estado, corte actual, bloqueo y fin efectivo.
     */
    public static function resumen($convenio): array
    {
        $hoy = Carbon::today();
        $fin = self::finEfectivo($convenio);
        $inicio = $convenio->inicio_vigencia
            ? Carbon::parse($convenio->inicio_vigencia)->startOfDay()
            : null;

        $bloqueado = $fin ? $hoy->greaterThan($fin) : false;

        $cortes = self::cortes(
            $inicio?->format('Y-m-d'),
            $fin
        );

        $corteActual = null;
        foreach ($cortes as &$c) {
            $d = $c['desde'] ? Carbon::parse($c['desde'])->startOfDay() : null;
            $h = $c['hasta'] ? Carbon::parse($c['hasta'])->startOfDay() : null;

            if (! $bloqueado && $d && $h && $hoy->between($d, $h)) {
                $c['estado'] = 'actual';
                $corteActual = $c['numero'];
            } elseif ($h && $hoy->greaterThan($h)) {
                $c['estado'] = 'pasado';
            } elseif ($d && $hoy->lessThan($d)) {
                $c['estado'] = 'futuro';
            } else {
                $c['estado'] = $bloqueado ? 'pasado' : 'actual';
                if (! $bloqueado && $corteActual === null) {
                    $corteActual = $c['numero'];
                }
            }
            $c['es_actual'] = $c['numero'] === $corteActual;
            $c['es_editable'] = ! $bloqueado && $c['es_actual'];
        }
        unset($c);

        // Antes del inicio se permite cargar el primer corte por anticipado.
        if (! $bloqueado && $corteActual === null && $cortes !== []) {
            $cortes[0]['estado'] = 'actual';
            $cortes[0]['es_actual'] = true;
            $cortes[0]['es_editable'] = true;
            $corteActual = $cortes[0]['numero'];
        }

        return [
            'hoy' => $hoy->format('Y-m-d'),
            'fin_efectivo' => $fin?->format('Y-m-d'),
            'bloqueado' => $bloqueado,
            'corte_actual' => $bloqueado ? null : $corteActual,
            'cortes' => $cortes,
        ];
    }

    /**
     * ¿Se puede editar el corte indicado hoy?
     */
    public static function puedeEditar($convenio, int $corte): bool
    {
        $resumen = self::resumen($convenio);

        if ($resumen['bloqueado']) {
            return false;
        }

        foreach ($resumen['cortes'] as $c) {
            if ((int) $c['numero'] === $corte) {
                return (bool) ($c['es_editable'] ?? false);
            }
        }

        return false;
    }
}
