<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('convenios_compromiso_avances')) {
            Schema::create('convenios_compromiso_avances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('compromiso_id')
                    ->constrained('convenios_compromisos')
                    ->cascadeOnDelete();
                $table->unsignedInteger('corte')->default(1);
                $table->date('desde')->nullable();
                $table->date('hasta')->nullable();
                $table->string('realizado', 2)->nullable();
                $table->text('actividad')->nullable();
                $table->timestamps();

                $table->unique(['compromiso_id', 'corte'], 'uk_avance_compromiso_corte');
                $table->index('corte', 'idx_avances_corte');
            });
        }

        // Backfill: conserva lo ya avanzado como corte 1.
        if (Schema::hasTable('convenios_compromisos')) {
            $filas = DB::table('convenios_compromisos')
                ->where(function ($q) {
                    $q->whereNotNull('realizado')->orWhereNotNull('actividad');
                })
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('convenios_compromiso_avances')
                        ->whereColumn('convenios_compromiso_avances.compromiso_id', 'convenios_compromisos.id')
                        ->where('convenios_compromiso_avances.corte', 1);
                })
                ->select('id', 'realizado', 'actividad')
                ->get();

            $ahora = now();
            foreach ($filas as $f) {
                DB::table('convenios_compromiso_avances')->insert([
                    'compromiso_id' => $f->id,
                    'corte' => 1,
                    'desde' => null,
                    'hasta' => null,
                    'realizado' => $f->realizado,
                    'actividad' => $f->actividad,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('convenios_compromiso_avances');
    }
};
