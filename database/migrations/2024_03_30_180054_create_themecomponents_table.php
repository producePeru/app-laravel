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
        Schema::create('themecomponents', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->unsignedBigInteger('component_id');

            $table->foreign('component_id')->references('id')->on('components')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('themecomponents');
    }
};
