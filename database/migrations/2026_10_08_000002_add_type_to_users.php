<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces users.account_type (candidate|business) with users.type (admin|talent|client).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('type', 20)->default('talent')->after('id')->index();
        });

        DB::table('users')->where('account_type', 'business')->update(['type' => 'client']);
        DB::table('users')->where('account_type', 'candidate')->update(['type' => 'talent']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['account_type']);
            $table->dropColumn('account_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_type', 20)->default('candidate')->after('id')->index();
        });

        DB::table('users')->where('type', 'client')->update(['account_type' => 'business']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
