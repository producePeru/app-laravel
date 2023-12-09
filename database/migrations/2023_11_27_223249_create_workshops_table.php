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
        Schema::create('workshops', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('slug')->unique();
            $table->integer('exponent_id');
            $table->string('workshop_date');
            $table->integer('type_intervention');
            $table->string('link')->nullable();
            $table->integer('user_id');

            $table->integer('testin_id')->nullable();
            $table->integer('testout_id')->nullable();
            $table->integer('invitation_id')->nullable();
            $table->integer('status')->nullable();                 //1 finalizado - 2 cancelado - 3 proceso
            $table->integer('registered')->nullable();
            $table->integer('rrss')->nullable();
            $table->integer('sms')->nullable();
            $table->integer('correo')->nullable();
            $table->integer('otro')->nullable();

            $table->string('comment_canceled')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshops');
    }
};
