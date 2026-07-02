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
        Schema::create('assessment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId("assessment_id");
            $table->string("firstname");
            $table->string("surname");
            $table->string("email");
            $table->string("phone_number")->nullable();
            $table->string("address")->nullable();
            $table->string("gender")->nullable();
            $table->string("occupation")->nullable();
            $table->foreignId("category_id");
            $table->string("referral_code")->nullable();
            $table->double("score")->nullable();
            $table->double("cut_off_mark")->nullable();
            $table->boolean("passed")->nullable();
            $table->integer("correct")->nullable();
            $table->integer("total_questions");
            $table->dateTime("started_at");
            $table->integer("time_used")->nullable();
            $table->boolean("cancelled")->default(false);
            $table->boolean("disqualified")->default(false);
            $table->boolean("approved")->nullable();
            $table->foreignId("treated_by")->nullable();
            $table->string("review_note")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_attempts');
    }
};
