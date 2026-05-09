<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerformanceIndexes extends Migration
{
    public function up(): void
    {
        // Properties — landlord queries always filter by user_id
        Schema::table('properties', function (Blueprint $table) {
            $table->index('user_id', 'idx_properties_user_id');
        });

        // Units — almost always filtered by property
        Schema::table('units', function (Blueprint $table) {
            $table->index('property_id', 'idx_units_property_id');
            $table->index('status', 'idx_units_status');
            $table->index(['property_id', 'status'], 'idx_units_property_status');
        });

        // Leases — filtered by unit, status, and dates constantly
        Schema::table('leases', function (Blueprint $table) {
            $table->index('unit_id', 'idx_leases_unit_id');
            $table->index('tenant_id', 'idx_leases_tenant_id');
            $table->index('status', 'idx_leases_status');
            $table->index('end_date', 'idx_leases_end_date');
            $table->index(['status', 'end_date'], 'idx_leases_status_end_date');
        });

        // Transactions — filtered by lease, type, and paid_at constantly
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('lease_id', 'idx_transactions_lease_id');
            $table->index('type', 'idx_transactions_type');
            $table->index('paid_at', 'idx_transactions_paid_at');
            $table->index(['lease_id', 'type'], 'idx_transactions_lease_type');
            $table->index(['type', 'paid_at'], 'idx_transactions_type_paid_at');
        });

        // Tenants — searched by name and phone
        Schema::table('tenants', function (Blueprint $table) {
            $table->index('full_name', 'idx_tenants_full_name');
            $table->index('phone', 'idx_tenants_phone');
        });
    }

    public function down(): void
    {
        Schema::table('properties', fn($t) => $t->dropIndex('idx_properties_user_id'));
        Schema::table('units', function ($t) {
            $t->dropIndex('idx_units_property_id');
            $t->dropIndex('idx_units_status');
            $t->dropIndex('idx_units_property_status');
        });
        Schema::table('leases', function ($t) {
            $t->dropIndex('idx_leases_unit_id');
            $t->dropIndex('idx_leases_tenant_id');
            $t->dropIndex('idx_leases_status');
            $t->dropIndex('idx_leases_end_date');
            $t->dropIndex('idx_leases_status_end_date');
        });
        Schema::table('transactions', function ($t) {
            $t->dropIndex('idx_transactions_lease_id');
            $t->dropIndex('idx_transactions_type');
            $t->dropIndex('idx_transactions_paid_at');
            $t->dropIndex('idx_transactions_lease_type');
            $t->dropIndex('idx_transactions_type_paid_at');
        });
        Schema::table('tenants', function ($t) {
            $t->dropIndex('idx_tenants_full_name');
            $t->dropIndex('idx_tenants_phone');
        });
    }
}