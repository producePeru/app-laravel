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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('nick_name')->unique();
            $table->string('password');
            $table->integer('document_type');
            $table->string('document_number', 20);
            $table->string('last_name', 100);
            $table->string('middle_name', 100);
            $table->string('name', 100);
            $table->integer('country_code');
            $table->string('birthdate');
            $table->enum('gender', ['h', 'm']);
            $table->boolean('is_disabled')->default(false);
            $table->string('email')->unique();
            $table->string('phone_number', 20);
            $table->integer('office_code');
            $table->integer('sede_code');
            $table->integer('role');
        
            $table->timestamp('email_verified_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
