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
        Schema::create('testouts', function (Blueprint $table) {
            $table->id();

            $table->string('date_end');

            $table->string('question1'); 
            $table->string('question1_opt1'); 
            $table->string('question1_opt2'); 
            $table->string('question1_opt3'); 
            $table->integer('question1_resp'); 

            $table->string('question2'); 
            $table->string('question2_opt1'); 
            $table->string('question2_opt2'); 
            $table->string('question2_opt3'); 
            $table->integer('question2_resp'); 

            $table->string('question3'); 
            $table->string('question3_opt1'); 
            $table->string('question3_opt2'); 
            $table->string('question3_opt3'); 
            $table->integer('question3_resp'); 

            $table->string('question4'); 
            $table->string('question4_opt1'); 
            $table->string('question4_opt2'); 
            $table->string('question4_opt3'); 
            $table->integer('question4_resp'); 

            $table->string('question5'); 
            $table->string('question5_opt1'); 
            $table->string('question5_opt2'); 
            $table->string('question5_opt3'); 
            $table->integer('question5_resp'); 

            $table->string('satistaction1'); 
            $table->string('satistaction2'); 
            $table->string('satistaction3'); 

            $table->string('comments')->nullable();; 
            $table->boolean('is_comments')->default(false);

            $table->integer('workshop_id');
            $table->integer('user_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testouts');
    }
};
