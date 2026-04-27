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
        Schema::create('client_bond_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId("client_bond_id");
            $table->foreignId("client_id");
            $table->double("payout_amount");
            $table->double("interest");
            $table->string("interest_measurement");
            $table->double("capital");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_bond_payouts');
    }
};
