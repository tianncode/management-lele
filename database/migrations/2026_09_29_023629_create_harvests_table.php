<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harvests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fish_cycle_id')
                ->constrained('fish_cycles')
                ->restrictOnDelete();

            $table->date('harvest_date');

            $table->unsignedInteger('total_fish')->nullable();

            $table->decimal('total_weight', 15, 3);

            $table->decimal('average_weight', 10, 2)->nullable();

            $table->decimal('selling_price_per_kg', 15, 2)->nullable();

            $table->decimal('estimated_revenue', 15, 2)->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('fish_cycle_id');
            $table->index('harvest_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvests');
    }
};
