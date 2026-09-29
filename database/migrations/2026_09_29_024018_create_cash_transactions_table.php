<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();

            $table->date('transaction_date');

            $table->enum('type', [
                'in',
                'out',
            ]);

            $table->string('category', 100);

            $table->string('reference_type', 100)->nullable();

            $table->unsignedBigInteger('reference_id')->nullable();

            $table->string('description');

            $table->decimal('amount', 15, 2);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('transaction_date');
            $table->index('type');
            $table->index('category');

            $table->index([
                'reference_type',
                'reference_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
