<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('employee_code')->nullable();
            $table->string('position_code')->nullable();
            $table->string('designation')->nullable();
            $table->string('hq_name')->nullable();
            $table->string('hq_code')->nullable();
            $table->enum('type', ['doctor', 'employee', 'admin'])->default('doctor');
            $table->string('mobile')->nullable();
            $table->string('speciality')->nullable();
            $table->string('speciality_code')->nullable();
            $table->string('hospital_name')->nullable();
            $table->string('address')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('language')->nullable();
            $table->string('msl_number')->nullable();
            $table->string('city')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('users')->onDelete('set null');

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
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
