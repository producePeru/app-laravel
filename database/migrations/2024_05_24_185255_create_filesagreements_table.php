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
        Schema::create('filesagreements', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('path');

            $table->unsignedBigInteger('agreements_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('agreements_id')->references('id')->on('agreements');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filesagreements');
    }
};
