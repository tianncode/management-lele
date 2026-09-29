<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fish_cycle_id')
                ->constrained('fish_cycles')
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->date('feeding_date');

            $table->decimal('quantity', 15, 3);

            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();

            $table->string('feeding_time', 20)->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('fish_cycle_id');
            $table->index('product_id');
            $table->index('feeding_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedings');
    }
};
