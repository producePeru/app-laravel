<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('convenios_compromisos')) {
            Schema::table('convenios_compromisos', function (Blueprint $table) {
                if (! Schema::hasColumn('convenios_compromisos', 'realizado')) {
                    $table->string('realizado', 2)->nullable()->after('orden')
                        ->comment('SI/NO según columna SE REALIZÓ de la plantilla');
                }
                if (! Schema::hasColumn('convenios_compromisos', 'actividad')) {
                    $table->text('actividad')->nullable()->after('realizado');
                }
            });
        }

        if (! Schema::hasTable('convenios_medios')) {
            Schema::create('convenios_medios', function (Blueprint $table) {
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
        Schema::dropIfExists('convenios_medios');

        if (Schema::hasTable('convenios_compromisos')) {
            Schema::table('convenios_compromisos', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('convenios_compromisos', 'realizado')) {
                    $cols[] = 'realizado';
                }
                if (Schema::hasColumn('convenios_compromisos', 'actividad')) {
                    $cols[] = 'actividad';
                }
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
