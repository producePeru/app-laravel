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

            $table->string('ruc', 20)->nullable();
            $table->string('social_reason')->nullable();
            $table->string('category')->nullable();
            $table->string('type')->nullable();
            $table->string('department')->nullable();
            $table->string('district')->nullable();
            $table->string('name_complete')->nullable();
            $table->string('dni_number')->nullable();
            $table->string('sex')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

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
