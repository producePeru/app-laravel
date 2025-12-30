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
        Schema::create('notaries', function (Blueprint $table) {
            $table->id();

            $table->string('name', 120);
            $table->string('address', 200);
            $table->decimal('price', 8, 2);
            $table->string('pricedescription');
            $table->boolean('istestimonio')->default(false);
            $table->string('conditions');
            $table->string('sociointerveniente');
            $table->boolean('biometrico')->default(false);
            $table->string('infocontacto');
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('province_id');
            $table->unsignedBigInteger('district_id');
            $table->unsignedBigInteger('user_id');


            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
            $table->foreign('province_id')->references('id')->on('provinces')->onDelete('cascade');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notaries');
    }
};
