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
        Schema::table('client_packages', function (Blueprint $table) {
            // null owner_type/owner_id means this property is held for the client themselves.
            // A non-null owner points at a FamilyMember it was assigned/bought for.
            $table->unsignedBigInteger('owner_id')->nullable()->after('client_id');
            $table->string('owner_type')->nullable()->after('owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_packages', function (Blueprint $table) {
            $table->dropColumn(['owner_id', 'owner_type']);
        });
    }
};
