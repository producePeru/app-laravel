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
        Schema::create('people', function (Blueprint $table) {
            $table->id();

            $table->string('documentnumber', 20);
            $table->string('lastname');
            $table->string('middlename');
            $table->string('name');
            $table->string('phone', 9)->nullable();
            $table->string('email')->nullable();
            $table->date('birthday')->nullable();
            $table->enum('sick', ['yes', 'no'])->default('no');
            $table->string('facebook')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('instagram')->nullable();
            $table->string('tiktok')->nullable();

            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('province_id');
            $table->unsignedBigInteger('district_id');
            $table->unsignedBigInteger('typedocument_id');
            $table->unsignedBigInteger('gender_id');

            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
            $table->foreign('province_id')->references('id')->on('provinces')->onDelete('cascade');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('cascade');
            $table->foreign('typedocument_id')->references('id')->on('typedocuments')->onDelete('cascade');
            $table->foreign('gender_id')->references('id')->on('genders')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
