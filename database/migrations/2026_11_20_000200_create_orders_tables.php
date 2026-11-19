<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status')->index();
            $table->string('payment_method');

            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone');

            $table->string('company_name')->nullable();
            $table->string('ice', 30)->nullable();
            $table->string('if_number', 30)->nullable();
            $table->string('rc_number', 30)->nullable();

            $table->string('shipping_city');
            $table->text('shipping_address');
            $table->string('shipping_postal_code', 20)->nullable();

            $table->string('carrier_mode')->default('mitek');
            $table->text('own_carrier_note')->nullable();
            $table->foreignId('shipping_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shipping_zone_name')->nullable();

            $table->unsignedInteger('total_weight_grams')->default(0);
            $table->unsignedBigInteger('subtotal_ht')->default(0);
            $table->unsignedBigInteger('shipping_ht')->default(0);
            $table->unsignedBigInteger('vat_total')->default(0);
            $table->unsignedBigInteger('total_ttc')->default(0);

            $table->text('customer_note')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_carrier')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            $table->string('reference');
            $table->string('name');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_ht');
            $table->unsignedTinyInteger('vat_rate');
            $table->unsignedBigInteger('line_ht');
            $table->unsignedBigInteger('line_vat');
            $table->unsignedBigInteger('line_ttc');
            $table->unsignedInteger('weight_grams')->default(0);
            $table->timestamps();
        });

        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('method');
            $table->unsignedBigInteger('amount');
            $table->string('status')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
