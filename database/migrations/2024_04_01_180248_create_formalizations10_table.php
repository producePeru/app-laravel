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
        Schema::create('formalizations10', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('detailprocedure_id');
            $table->unsignedBigInteger('modality_id');
            $table->unsignedBigInteger('economicsector_id');
            $table->unsignedBigInteger('comercialactivity_id');
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('province_id');
            $table->unsignedBigInteger('district_id');
            $table->unsignedBigInteger('people_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->foreign('detailprocedure_id')->references('id')->on('detailprocedures')->onDelete('cascade');
            $table->foreign('modality_id')->references('id')->on('modalities')->onDelete('cascade');
            $table->foreign('economicsector_id')->references('id')->on('economicsectors')->onDelete('cascade');
            $table->foreign('comercialactivity_id')->references('id')->on('comercialactivities')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
            $table->foreign('province_id')->references('id')->on('provinces')->onDelete('cascade');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('cascade');
            $table->foreign('people_id')->references('id')->on('people')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formalizations10');
    }
};
