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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId("client_id")->constrained("clients");
            $table->foreignId("order_id")->constrained("orders");
            
            $table->bigInteger("receipt_no")->nullable();
            $table->double("amount");
            $table->foreignId("payment_mode_id")->constrained("payment_modes");
            $table->boolean("confirmed")->nullable();
            $table->foreignId("evidence_file_id")->nullable()->references("id")->on("files");
            $table->foreignId("payment_gateway_id")->nullable();
            $table->string("reference")->nullable();
            $table->boolean("success")->nullable();
            $table->text("failure_message")->nullable();
            $table->boolean("flag")->nullable();
            $table->text("flag_message")->nullable();
            $table->foreignId("bank_account_id")->nullable()->references("id")->on("bank_accounts");
            $table->date("payment_date");
            $table->foreignId("receipt_file_id")->nullable()->references("id")->on("files");
            $table->string("purpose");
            $table->foreignId("user_id")->nullable()->references("id")->on("users");
            $table->boolean("docs_uploaded")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
