<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convenios_compromisos_evidencias', function (Blueprint $table) {
            if (! Schema::hasColumn('convenios_compromisos_evidencias', 'avance_id')) {
                $table->foreignId('avance_id')
                    ->nullable()
                    ->after('compromiso_id')
                    ->constrained('convenios_compromiso_avances')
                    ->nullOnDelete();
            }
        });

        // Backfill: los archivos existentes pertenecen al corte 1.
        $pendientes = DB::table('convenios_compromisos_evidencias')
            ->whereNull('avance_id')
            ->select('id', 'compromiso_id')
            ->get()
            ->groupBy('compromiso_id');

        $ahora = now();
        foreach ($pendientes as $compromisoId => $archivos) {
            $avanceId = DB::table('convenios_compromiso_avances')
                ->where('compromiso_id', $compromisoId)
                ->where('corte', 1)
                ->value('id');

            if (! $avanceId) {
                $avanceId = DB::table('convenios_compromiso_avances')->insertGetId([
                    'compromiso_id' => $compromisoId,
                    'corte' => 1,
                    'desde' => null,
                    'hasta' => null,
                    'realizado' => DB::table('convenios_compromisos')->where('id', $compromisoId)->value('realizado'),
                    'actividad' => DB::table('convenios_compromisos')->where('id', $compromisoId)->value('actividad'),
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }

            DB::table('convenios_compromisos_evidencias')
                ->whereIn('id', $archivos->pluck('id')->all())
                ->update(['avance_id' => $avanceId]);
        }
    }

    public function down(): void
    {
        Schema::table('convenios_compromisos_evidencias', function (Blueprint $table) {
            if (Schema::hasColumn('convenios_compromisos_evidencias', 'avance_id')) {
                $table->dropConstrainedForeignId('avance_id');
            }
        });
    }
};
