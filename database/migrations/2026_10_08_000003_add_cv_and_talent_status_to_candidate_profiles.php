<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Talent pipeline: CV upload plus the for_assessment -> for_evaluation -> active status flow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->string('cv_path')->nullable()->after('linkedin_url');
            $table->string('cv_original_name')->nullable()->after('cv_path');
            $table->unsignedInteger('cv_size')->nullable()->after('cv_original_name');
            $table->timestamp('cv_uploaded_at')->nullable()->after('cv_size');
            $table->timestamp('status_updated_at')->nullable()->after('status');
        });

        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->string('status', 20)->default('for_assessment')->index()->change();
        });

        DB::table('candidate_profiles')
            ->whereNotIn('status', ['for_assessment', 'for_evaluation', 'active'])
            ->update(['status' => 'for_assessment']);
    }

    public function down(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->string('status', 20)->default('new')->change();
            $table->dropColumn(['cv_path', 'cv_original_name', 'cv_size', 'cv_uploaded_at', 'status_updated_at']);
        });
    }
};
