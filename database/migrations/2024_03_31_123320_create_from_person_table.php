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
        Schema::create('from_person', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('from_id');
            $table->unsignedBigInteger('people_id');

            $table->foreign('people_id')->references('id')->on('people')->onDelete('cascade');
            $table->foreign('from_id')->references('id')->on('froms')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('from_person');
    }
};
