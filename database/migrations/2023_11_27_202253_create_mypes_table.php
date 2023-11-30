<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mypes', function (Blueprint $table) {
            $table->id();

            $table->string('ruc', 20)->unique();
            $table->string('social_reason');
            $table->string('category');
            $table->string('type', 100);
            $table->string('department', 100);
            $table->string('district', 100);
            $table->string('name_complete');
            $table->string('dni_number', 8);
            $table->string('sex', 12);
            $table->string('phone', 12);
            $table->string('email', 100);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mypes');
    }
};
