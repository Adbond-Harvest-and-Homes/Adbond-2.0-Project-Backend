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
        Schema::create('client_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId("client_id")->references("id")->on("clients");
            $table->foreignId("package_id")->references("id")->on("packages");
            $table->foreignId("contract_file_id")->nullable()->references("id")->on("files");
            $table->foreignId("happiness_letter_file_id")->nullable()->references("id")->on("files");
            $table->foreignId("doa_file_id")->nullable()->references("id")->on("files");
            $table->boolean("sold")->default(false);
            $table->boolean("upgraded")->default(false);
            $table->string("origin");
            $table->foreignId("purchase_id");
            $table->date("purchase_completed_at")->nullable();
            $table->boolean("docs_uploaded")->default(false);
            $table->string("purchase_type");

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_packages');
    }
};
