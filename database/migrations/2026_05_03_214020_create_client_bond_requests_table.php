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
        Schema::create('client_bond_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId("client_bond_id");
            $table->string("type");
            $table->boolean("approved")->nullable();
            $table->text("rejected_reason")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_bond_requests');
    }
};
