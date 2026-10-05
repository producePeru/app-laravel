<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('convenios_medios') && ! Schema::hasTable('convenios_compromisos_evidencias')) {
            Schema::rename('convenios_medios', 'convenios_compromisos_evidencias');
        }

        if (! Schema::hasTable('convenios_compromisos_evidencias')) {
            Schema::create('convenios_compromisos_evidencias', function ($table) {
                $table->id();
                $table->foreignId('compromiso_id')
                    ->constrained('convenios_compromisos')
                    ->cascadeOnDelete();
                $table->string('nombre_original');
                $table->string('ruta');
                $table->string('mime')->nullable();
                $table->unsignedBigInteger('tamano')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('convenios_compromisos_evidencias') && ! Schema::hasTable('convenios_medios')) {
            Schema::rename('convenios_compromisos_evidencias', 'convenios_medios');
        }
    }
};
