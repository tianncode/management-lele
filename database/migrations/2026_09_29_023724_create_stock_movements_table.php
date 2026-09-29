<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->enum('type', [
                'in',
                'out',
                'adjustment',
            ]);

            $table->decimal('quantity', 15, 3);

            $table->decimal('unit_price', 15, 2)->nullable();

            $table->decimal('total_value', 15, 2)->nullable();

            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->date('movement_date');

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('product_id');
            $table->index('type');
            $table->index('movement_date');

            $table->index([
                'reference_type',
                'reference_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
