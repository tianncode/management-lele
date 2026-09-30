<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('paid_amount', 15, 2)
                ->default(0)
                ->after('total');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('paid_amount', 15, 2)
                ->default(0)
                ->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('paid_amount');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('paid_amount');
        });
    }
};
