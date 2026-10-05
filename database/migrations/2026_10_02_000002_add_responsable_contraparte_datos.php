<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convenios_gestion', function (Blueprint $table) {
            if (! Schema::hasColumn('convenios_gestion', 'responsable_contraparte_cargo')) {
                $table->string('responsable_contraparte_cargo', 255)->nullable()->after('responsable_contraparte');
            }
            if (! Schema::hasColumn('convenios_gestion', 'responsable_contraparte_correo')) {
                $table->string('responsable_contraparte_correo', 255)->nullable()->after('responsable_contraparte_cargo');
            }
            if (! Schema::hasColumn('convenios_gestion', 'responsable_contraparte_celular')) {
                $table->string('responsable_contraparte_celular', 20)->nullable()->after('responsable_contraparte_correo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('convenios_gestion', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('convenios_gestion', 'responsable_contraparte_celular')) {
                $cols[] = 'responsable_contraparte_celular';
            }
            if (Schema::hasColumn('convenios_gestion', 'responsable_contraparte_correo')) {
                $cols[] = 'responsable_contraparte_correo';
            }
            if (Schema::hasColumn('convenios_gestion', 'responsable_contraparte_cargo')) {
                $cols[] = 'responsable_contraparte_cargo';
            }
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
