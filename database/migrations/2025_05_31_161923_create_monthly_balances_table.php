<?php

use App\Models\Shop;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monthly_balances', function (Blueprint $table) {
            $table->id();
            $table->date('month'); // E.g., 2024-04-01
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('credits', 15, 2)->default(0);
            $table->decimal('debits', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);
            $table->foreignIdFor(Shop::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_balances');
    }
};
