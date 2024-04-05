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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('lastname');
            $table->string('middlename');
            $table->date('birthday')->nullable();
            $table->enum('sick', ['yes', 'no'])->default('no');
            $table->string('phone', 9)->nullable();
            $table->unsignedBigInteger('gender_id');
            $table->unsignedBigInteger('cde_id');
            $table->unsignedBigInteger('office_id');
            $table->unsignedBigInteger('user_id');

            $table->foreign('gender_id')->references('id')->on('genders')->onDelete('cascade');
            $table->foreign('cde_id')->references('id')->on('cdes')->onDelete('cascade');
            $table->foreign('office_id')->references('id')->on('offices')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
