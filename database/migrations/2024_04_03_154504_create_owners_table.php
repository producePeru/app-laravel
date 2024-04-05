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
        Schema::create('owners', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('mype_id');
            $table->unsignedBigInteger('people_id');

            $table->foreign('mype_id')->references('id')->on('mypes')->onDelete('cascade');
            $table->foreign('people_id')->references('id')->on('people')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owners');
    }
};
