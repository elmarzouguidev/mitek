<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('callback_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('phone', 30);
            $table->string('subject', 30);
            $table->string('time_slot', 20);
            $table->text('note')->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->text('internal_notes')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('callback_requests');
    }
};
