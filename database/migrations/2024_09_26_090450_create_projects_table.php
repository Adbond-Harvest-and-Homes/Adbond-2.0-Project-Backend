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
        Schema::create('projects', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name');
            $table->foreignId('project_type_id')->references("id")->on("project_types");
            $table->text('description')->nullable();
            $table->string("state");
            $table->string("state_id");
            $table->boolean('active')->default(true);
            $table->dateTime('deactivated_at')->nullable();
            $table->double('reverted_interest_rate', 3,1)->default(10.0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
