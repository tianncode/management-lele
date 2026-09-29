<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('product_categories')
                ->restrictOnDelete();

            $table->string('code', 50)->unique();
            $table->string('name');

            $table->string('unit', 30);

            $table->decimal('current_stock', 15, 3)->default(0);
            $table->decimal('minimum_stock', 15, 3)->default(0);
            $table->decimal('average_price', 15, 2)->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('category_id');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
