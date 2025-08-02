<?php

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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable(); // For guest users
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // For authenticated users
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->integer('total_items')->default(0);
            $table->timestamp('expires_at')->nullable(); // For session-based carts
            $table->timestamps();
            
            $table->index(['session_id', 'shop_id']);
            $table->index(['user_id', 'shop_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
