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
        Schema::table('sms_campaign_recipients', function (Blueprint $table) {
            // Nullable override of the campaign's shared message, used by
            // automated reminders where each recipient gets a personalized
            // amount/due-date instead of one message for everyone.
            $table->text('message')->nullable()->after('phone_number');

            // Set only for automated reminders tied to a specific order
            // (e.g. villa repayment reminders), so cooldown checks can look
            // up "when was this order last reminded".
            $table->foreignId('order_id')->nullable()->after('client_id')->constrained('orders')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_campaign_recipients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
            $table->dropColumn('message');
        });
    }
};
