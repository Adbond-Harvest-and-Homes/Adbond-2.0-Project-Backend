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
        Schema::create('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('title')->nullable();
            $table->string('firstname');
            $table->string('lastname')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('email_confirmed')->default(1);
            $table->string('password');
            $table->boolean('password_set')->default(false);
            $table->foreignId('role_id')->nullable();
            $table->foreignId('staff_type_id')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('country_id')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('gender')->nullable();
            $table->foreignId('photo_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->foreignId('position_id')->nullable();
            $table->string('referer_code')->nullable();
            $table->string('staff_referer_code')->nullable();
            $table->tinyInteger('activated')->default(1);
            $table->bigInteger('registered_by')->nullable();
            $table->double('commission', 15, 2)->default(0);
            $table->double('commission_balance', 15, 2)->default(0);
            $table->double('commission_before_tax', 15, 2)->default(0);
            $table->foreignId('hybrid_staff_draw_id')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->foreignId('bank_id')->nullable();
            $table->foreignId('virtual_staff_category_id')->nullable();
            $table->date('date_joined')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('email', 191);
            $table->string('token_signature');
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
