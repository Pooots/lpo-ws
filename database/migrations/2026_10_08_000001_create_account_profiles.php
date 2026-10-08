<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_type', 20)->default('candidate')->after('id')->index();
            $table->string('phone', 50)->nullable()->after('email');
            $table->string('status', 20)->default('active')->after('password');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });

        Schema::create('candidate_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('location');
            $table->string('desired_position');
            $table->string('experience_level', 20);
            $table->string('work_setup', 20);
            $table->json('skills')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamps();
        });

        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('company_website')->nullable();
            $table->string('industry', 100);
            $table->string('company_size', 20);
            $table->string('job_title');
            $table->json('services');
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
        Schema::dropIfExists('candidate_profiles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['account_type']);
            $table->dropColumn(['account_type', 'phone', 'status', 'last_login_at']);
        });
    }
};
