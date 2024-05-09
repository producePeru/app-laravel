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
        Schema::create('drive_users', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('drive_id');
            $table->json('user_ids');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('drive_id')->references('id')->on('drives');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drive_users');
    }
};
