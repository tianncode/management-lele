<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('samplings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fish_cycle_id')
                ->constrained('fish_cycles')
                ->restrictOnDelete();

            $table->date('sampling_date');

            $table->unsignedInteger('sample_count');

            $table->decimal('average_weight', 10, 2);

            $table->decimal('average_length', 10, 2)->nullable();

            $table->decimal('estimated_biomass', 15, 3)->nullable();

            $table->decimal('estimated_population', 15, 2)->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('fish_cycle_id');
            $table->index('sampling_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samplings');
    }
};
