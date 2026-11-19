<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_zones', function (Blueprint $table) {
            $table->unsignedTinyInteger('transit_min_days')->default(1)->after('is_active');
            $table->unsignedTinyInteger('transit_max_days')->default(3)->after('transit_min_days');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_zones', function (Blueprint $table) {
            $table->dropColumn(['transit_min_days', 'transit_max_days']);
        });
    }
};
