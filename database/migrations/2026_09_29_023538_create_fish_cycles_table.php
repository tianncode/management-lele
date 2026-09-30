<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fish_cycles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pond_id')
                ->constrained('ponds')
                ->restrictOnDelete();

            $table->foreignId('seed_product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            $table->string('code', 50)->unique();

            $table->date('start_date');
            $table->date('target_harvest_date')->nullable();

            $table->unsignedInteger('initial_fish_count');

            $table->decimal('seed_size', 8, 2)->nullable();
            $table->decimal('seed_unit_price', 15, 2)->nullable();

            $table->enum('status', [
                'planned',
                'active',
                'harvest',
                'completed',
                'cancelled',
            ])->default('planned');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('pond_id');
            $table->index('seed_product_id');
            $table->index('status');
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fish_cycles');
    }
};
