<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiendas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->string('ruc', 20)->unique();
            $table->foreignId('envio_id')->nullable()->constrained('envios')->nullOnDelete();
            $table->string('celular', 20)->nullable();
            $table->string('correo', 255)->nullable();
            $table->foreignId('image_id')->nullable()->constrained('images')->nullOnDelete();
            $table->json('socials')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiendas');
    }
};
