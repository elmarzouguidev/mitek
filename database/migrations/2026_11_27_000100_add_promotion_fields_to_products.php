<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedBigInteger('compare_at_price_ht')->nullable()->after('price_ht');
            $table->timestamp('promo_ends_at')->nullable()->after('compare_at_price_ht');
            $table->boolean('is_best_seller')->default(false)->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['compare_at_price_ht', 'promo_ends_at', 'is_best_seller']);
        });
    }
};
