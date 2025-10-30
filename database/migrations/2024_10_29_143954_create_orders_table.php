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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string("type")->default("purchase");
            $table->foreignId("client_id")->references("id")->on("clients");
            $table->foreignId("package_id")->references("id")->on("packages");
            $table->double("units");
            $table->double("amount_payed")->default(0);
            $table->double("amount_payable");
            $table->foreignId("promo_code_id")->nullable()->references("id")->on("promo_codes");
            $table->boolean("is_installment")->default(false);
            $table->integer("installment_count")->nullable();
            $table->double("amount_per_installment")->nullable();
            $table->integer("installments_payed")->nullable();
            $table->double("balance");
            $table->foreignId("payment_status_id");
            table->boolean("completed")->default(false);
            $table->date("order_date");
            $table->date("payment_due_date")->nullable();
            $table->date("grace_period_end_date")->nullable();
            $table->date("penalty_period_end_date")->nullable();
            $table->foreignId("payment_period_status_id");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
