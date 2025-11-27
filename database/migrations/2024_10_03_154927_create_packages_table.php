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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references("id")->on("users");
            $table->string('name');
            $table->foreignId("country_id");
            $table->foreignId('state_id');
            $table->string('address')->nullable();
            $table->foreignId('project_id')->references("id")->on("projects");
            $table->double("size")->nullable();
            $table->double("amount");
            $table->integer("units");
            $table->integer("available_units")->nullable();
            $table->double("discount")->default(0);
            $table->double("min_price")->default(0);
            $table->integer("installment_duration")->default(12);
            $table->double("infrastructure_fee")->default(0);
            $table->text('description')->nullable();
            $table->json('benefits')->nullable();
            $table->foreignId('package_brochure_file_id')->nullable();
            $table->boolean("installment_option")->default(true);
            $table->string("vr_url")->nullable();
            $table->boolean('active')->default(true);
            $table->dateTime('deactivated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
