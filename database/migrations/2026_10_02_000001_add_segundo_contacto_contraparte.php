<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('convenios_contactos')) {
            return;
        }

        DB::statement("ALTER TABLE `convenios_contactos` MODIFY COLUMN `tipo` ENUM('repProduce','repContraparte','repContraparte2','coordProduce','coordContraparte','coordContraparte2') NOT NULL");
    }

    public function down(): void
    {
        if (! Schema::hasTable('convenios_contactos')) {
            return;
        }

        // Solo revierte si no existen registros con los nuevos tipos
        $nuevos = DB::table('convenios_contactos')
            ->whereIn('tipo', ['repContraparte2', 'coordContraparte2'])
            ->count();

        if ($nuevos === 0) {
            DB::statement("ALTER TABLE `convenios_contactos` MODIFY COLUMN `tipo` ENUM('repProduce','repContraparte','coordProduce','coordContraparte') NOT NULL");
        }
    }
};
