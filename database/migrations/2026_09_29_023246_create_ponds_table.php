<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ponds', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)->unique();
            $table->string('name');

            $table->decimal('length', 10, 2);
            $table->decimal('width', 10, 2);
            $table->decimal('depth', 10, 2)->nullable();

            $table->decimal('volume', 15, 3)->nullable();

            $table->enum('status', [
                'available',
                'preparation',
                'cultivation',
                'harvest',
                'maintenance',
                'inactive',
            ])->default('available');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ponds');
    }
};
