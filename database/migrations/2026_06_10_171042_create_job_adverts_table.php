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
        Schema::create('job_adverts', function (Blueprint $table) {
            $table->id();
            $table->string("title");
            $table->foreignId("department_id");
            $table->foreignId("employment_type_id");
            $table->string("location")->nullable();
            $table->integer("slots")->nullable();
            $table->date("deadline")->nullable();
            $table->text("description");
            $table->boolean("is_open")->default(true);
            $table->date("opened_on");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_adverts');
    }
};
