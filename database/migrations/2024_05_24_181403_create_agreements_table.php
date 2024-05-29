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
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->string('denomination', 100)->nullable();
            $table->string('alliedEntity', 100)->nullable();
            $table->string('homeOperations', 100)->nullable();
            $table->string('address', 100);
            $table->string('reference', 100)->nullable();
            $table->string('resolution', 150)->nullable();
            $table->string('initials', 40)->nullable();
            $table->dateTime('startDate')->nullable();
            $table->dateTime('endDate')->nullable();

            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('province_id')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->unsignedBigInteger('operationalstatus_id')->nullable();
            $table->unsignedBigInteger('agreementstatus_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('city_id')->references('id')->on('cities');
            $table->foreign('province_id')->references('id')->on('provinces');
            $table->foreign('district_id')->references('id')->on('districts');
            $table->foreign('operationalstatus_id')->references('id')->on('operationalstatus');
            $table->foreign('agreementstatus_id')->references('id')->on('agreementstatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
