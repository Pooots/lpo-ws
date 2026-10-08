<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Client records: location, billing contact, lead source, engagement terms, internal notes,
 * and the new_inquiry -> discovery -> proposal -> active (or inactive) pipeline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->after('company_size');
            $table->string('city', 100)->nullable()->after('country');
            $table->string('billing_email')->nullable()->after('job_title');
            $table->string('lead_source', 20)->nullable()->after('message');
            $table->string('engagement_type', 20)->nullable()->after('lead_source');
            $table->unsignedSmallInteger('team_size')->nullable()->after('engagement_type');
            $table->decimal('monthly_value', 12, 2)->nullable()->after('team_size');
            $table->string('currency', 3)->default('USD')->after('monthly_value');
            $table->date('contract_start')->nullable()->after('currency');
            $table->date('contract_end')->nullable()->after('contract_start');
            $table->string('account_manager')->nullable()->after('contract_end');
            $table->text('notes')->nullable()->after('account_manager');
            $table->timestamp('status_updated_at')->nullable()->after('status');
        });

        Schema::table('business_profiles', function (Blueprint $table) {
            $table->string('status', 20)->default('new_inquiry')->index()->change();
        });

        DB::table('business_profiles')
            ->whereNotIn('status', ['new_inquiry', 'discovery', 'proposal', 'active', 'inactive'])
            ->update(['status' => 'new_inquiry']);

        DB::table('business_profiles')->whereNull('lead_source')->update(['lead_source' => 'website']);
    }

    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('business_profiles', function (Blueprint $table) {
            $table->string('status', 20)->default('new')->change();
            $table->dropColumn([
                'country',
                'city',
                'billing_email',
                'lead_source',
                'engagement_type',
                'team_size',
                'monthly_value',
                'currency',
                'contract_start',
                'contract_end',
                'account_manager',
                'notes',
                'status_updated_at',
            ]);
        });
    }
};
