<?php

use App\Models\Shop;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();  // First column: `id`
            $table->unsignedBigInteger('parent_id')->nullable();  // Now second column (no ->after() needed)
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->foreignIdFor(Shop::class)->constrained()->cascadeOnDelete();  // tenant
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
