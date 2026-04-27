<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->enum('unit_type', ['apartment', 'office', 'retail', 'warehouse', 'studio'])->default('apartment')->after('unit_number');
            $table->decimal('size_sqft', 10, 2)->nullable()->after('base_rent');
            $table->decimal('rate_per_sqft', 8, 2)->nullable()->after('size_sqft');
            $table->string('floor')->nullable()->after('rate_per_sqft'); // e.g. "3rd Floor", "Ground"
            $table->boolean('is_furnished')->default(false)->after('floor');
            $table->string('service_charge')->nullable()->after('is_furnished'); // monthly service charge
        });
    }
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['unit_type', 'size_sqft', 'rate_per_sqft', 'floor', 'is_furnished', 'service_charge']);
        });
    }
};
