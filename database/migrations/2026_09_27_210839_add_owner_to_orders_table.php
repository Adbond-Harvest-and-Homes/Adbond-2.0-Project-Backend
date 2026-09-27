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
        Schema::table('orders', function (Blueprint $table) {
            // null owner_type/owner_id means the order belongs to (and was bought for) the paying client themselves.
            // A non-null owner points at a FamilyMember owned by that client (bought on their behalf).
            $table->unsignedBigInteger('owner_id')->nullable()->after('client_id');
            $table->string('owner_type')->nullable()->after('owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['owner_id', 'owner_type']);
        });
    }
};
