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
        Schema::create('workshop_details', function (Blueprint $table) {
            $table->id();

            $table->integer('workshop_id');
            $table->string('ruc_mype');
            $table->string('dni_mype', 15);

            $table->integer('te1')->nullable();
            $table->integer('te2')->nullable();
            $table->integer('te3')->nullable();
            $table->integer('te4')->nullable();
            $table->integer('te5')->nullable();
            $table->integer('te_note')->nullable();

            $table->integer('ts1')->nullable();
            $table->integer('ts2')->nullable();
            $table->integer('ts3')->nullable();
            $table->integer('ts4')->nullable();
            $table->integer('ts5')->nullable();
            $table->integer('ts_note')->nullable();

            

            $table->double('average_final', 8, 2)->nullable();

            $table->integer('c1')->nullable();
            $table->integer('c2')->nullable();
            $table->integer('c3')->nullable();
            $table->double('average_satisfaction', 8, 2)->nullable();

            $table->text('suggestions')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_details');
    }
};
