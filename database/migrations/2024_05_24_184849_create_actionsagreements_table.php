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
        Schema::create('actionsagreements', function (Blueprint $table) {
            $table->id();

            $table->dateTime('date')->nullable();
            $table->string('description', 250)->nullable();

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
        Schema::dropIfExists('actionsagreements');
    }
};
