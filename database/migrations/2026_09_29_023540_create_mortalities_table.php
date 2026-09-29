<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mortalities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fish_cycle_id')
                ->constrained('fish_cycles')
                ->restrictOnDelete();

            $table->date('mortality_date');

            $table->unsignedInteger('quantity');

            $table->string('cause')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('fish_cycle_id');
            $table->index('mortality_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mortalities');
    }
};
