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
        Schema::create('exponents', function (Blueprint $table) {
            $table->id();

            $table->enum('document_type', ['dni', 'ce', 'pas', 'ptp'])->nullable(false);
            $table->string('document_number', 20)->nullable(false);
            $table->string('first_name')->nullable(false);
            $table->string('last_name')->nullable(false);
            $table->string('middle_name')->nullable(false);
            $table->enum('gender', ['h', 'm'])->nullable(false);
            $table->string('email')->nullable(false);
            $table->string('ruc_number', 100)->nullable();
            $table->string('phone_number', 25)->nullable();
            $table->string('specialty')->nullable();
            $table->string('profession')->nullable();
            $table->string('cv_link')->nullable();

            $table->integer('user_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exponents');
    }
};
