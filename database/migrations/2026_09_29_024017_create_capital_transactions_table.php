<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capital_transactions', function (Blueprint $table) {
            $table->id();

            $table->date('transaction_date');

            $table->enum('type', [
                'in',
                'withdrawal',
            ]);

            $table->decimal('amount', 15, 2);

            $table->string('description');

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('transaction_date');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capital_transactions');
    }
};
