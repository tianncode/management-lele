<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('other_incomes', function (Blueprint $table) {
            $table->id();

            $table->date('income_date');

            $table->string('category');

            $table->string('description');

            $table->decimal('amount', 15, 2);

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('income_date');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_incomes');
    }
};
