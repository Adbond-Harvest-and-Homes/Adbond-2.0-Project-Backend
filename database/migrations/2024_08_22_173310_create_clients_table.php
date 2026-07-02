<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use app\EnumClass;
use app\Enums\KYCStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('title')->nullable();
            $table->string('firstname');
            $table->string('lastname')->nullable();
            $table->string('othernames')->nullable();
            $table->string('email');
            $table->string('password')->nullable();
            $table->string('phone_number')->nullable();
            $table->foreignId('photo_id')->nullable();
            $table->enum('gender', EnumClass::genders())->nullable();
            $table->date('dob')->nullable();
            $table->decimal('provider_id', 65, 0)->nullable();
            $table->string('provider_name')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->foreignId('country_id')->nullable();
            $table->foreignId('state_id')->nullable();
            $table->foreignId('age_group_id')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->enum('marital_status', EnumClass::maritalStatus())->nullable();
            $table->enum('employment_status', EnumClass::employmentStatuses())->nullable();
            $table->string('occupation')->nullable();
            $table->tinyInteger('activated')->default(true);
            $table->enum('kyc_status', EnumClass::kycStatus())->default(KYCStatus::NOTSTARTED->value);
            $table->foreignId('referer_id')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
