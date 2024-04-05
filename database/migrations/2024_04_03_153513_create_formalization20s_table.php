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
        Schema::create('formalization20s', function (Blueprint $table) {
            $table->id();

            $table->unsignedTinyInteger('task')->default(0);
            $table->string('codesunarp', 20);
            $table->string('numbernotary', 40);
            $table->string('address')->nullable();;
            $table->unsignedBigInteger('economicsector_id')->nullable();;
            $table->unsignedBigInteger('comercialactivity_id')->nullable();;
            $table->unsignedBigInteger('regime_id')->nullable();;
            $table->unsignedBigInteger('city_id')->nullable();;
            $table->unsignedBigInteger('province_id')->nullable();;
            $table->unsignedBigInteger('district_id')->nullable();;
            $table->unsignedBigInteger('modality_id')->nullable();;
            $table->unsignedBigInteger('notary_id')->nullable();;
            $table->unsignedBigInteger('mype_id')->nullable();;
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('people_id')->nullable();
            $table->unsignedBigInteger('userupdated_id')->nullable()->nullable();;


            $table->foreign('economicsector_id')->references('id')->on('economicsectors')->onDelete('cascade');
            $table->foreign('comercialactivity_id')->references('id')->on('comercialactivities')->onDelete('cascade');
            $table->foreign('regime_id')->references('id')->on('regimes')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
            $table->foreign('province_id')->references('id')->on('provinces')->onDelete('cascade');
            $table->foreign('district_id')->references('id')->on('districts')->onDelete('cascade');
            $table->foreign('modality_id')->references('id')->on('modalities')->onDelete('cascade');
            $table->foreign('notary_id')->references('id')->on('notaries')->onDelete('cascade');
            $table->foreign('mype_id')->references('id')->on('mypes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('people_id')->references('id')->on('people')->onDelete('set null');
            $table->foreign('userupdated_id')->references('id')->on('users')->onDelete('set null');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formalization20s');
    }
};
