<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('convenios_archivos')) {
            Schema::create('convenios_archivos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('convenio_id')
                    ->constrained('convenios')
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
        Schema::dropIfExists('convenios_archivos');
    }
};
