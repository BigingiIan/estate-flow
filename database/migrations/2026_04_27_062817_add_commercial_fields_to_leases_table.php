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
        Schema::table('leases', function (Blueprint $table) {
            $table->decimal('service_charge', 10, 2)->default(0)->after('deposit_amount');
            $table->decimal('escalation_rate', 5, 2)->default(0)->after('service_charge'); // annual % increase
            $table->string('lease_type')->default('standard')->after('escalation_rate'); // standard, short_term, periodic
            $table->string('business_name')->nullable()->after('lease_type'); // for commercial tenants
        });
    }
    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropColumn(['service_charge', 'escalation_rate', 'lease_type', 'business_name']);
        });
    }
};
