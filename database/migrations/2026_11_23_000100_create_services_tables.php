<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('icon', 40)->default('wrench');
            $table->string('summary', 400);
            $table->json('bullets');
            $table->longText('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('reference')->unique();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service_title');
            $table->string('status')->index();

            $table->string('contact_name');
            $table->string('company_name')->nullable();
            $table->string('phone', 30);
            $table->string('email');
            $table->string('city', 100);
            $table->string('intervention', 20);
            $table->string('urgency', 20);
            $table->text('message');
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('services');
    }
};
