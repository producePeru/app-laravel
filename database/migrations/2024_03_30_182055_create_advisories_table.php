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
        Schema::create('advisories', function (Blueprint $table) {
            $table->id();

            $table->text('observations')->nullable();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('people_id');
            $table->unsignedBigInteger('component_id');
            $table->unsignedBigInteger('theme_id');
            $table->unsignedBigInteger('modality_id');
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('province_id');
            $table->unsignedBigInteger('district_id');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('people_id')->references('id')->on('people')->onDelete('cascade');
            $table->foreign('component_id')->references('id')->on('components')->onDelete('cascade');
            $table->foreign('theme_id')->references('id')->on('themecomponents')->onDelete('cascade');
            $table->foreign('modality_id')->references('id')->on('modalities')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
            $table->foreign('province_id')->references('id')->on('provinces')->onDelete('cascade');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advisories');
    }
};
