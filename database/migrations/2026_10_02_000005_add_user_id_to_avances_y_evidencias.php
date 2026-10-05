<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quién registró/actualizó la actividad del avance en cada corte.
        Schema::table('convenios_compromiso_avances', function (Blueprint $table) {
            if (! Schema::hasColumn('convenios_compromiso_avances', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('actividad')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        // Quién subió cada archivo de verificación.
        Schema::table('convenios_compromisos_evidencias', function (Blueprint $table) {
            if (! Schema::hasColumn('convenios_compromisos_evidencias', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('avance_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('convenios_compromisos_evidencias', function (Blueprint $table) {
            if (Schema::hasColumn('convenios_compromisos_evidencias', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });

        Schema::table('convenios_compromiso_avances', function (Blueprint $table) {
            if (Schema::hasColumn('convenios_compromiso_avances', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
