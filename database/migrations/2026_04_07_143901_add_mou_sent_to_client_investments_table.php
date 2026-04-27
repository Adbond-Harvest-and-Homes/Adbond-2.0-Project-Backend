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
        Schema::table('client_investments', function (Blueprint $table) {
            $table->boolean("mou_sent")->default(true)->after("docs_uploaded");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_investments', function (Blueprint $table) {
            //
        });
    }
};
