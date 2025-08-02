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
        Schema::table('carts', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['user_id']);
            
            // Add new foreign key constraint to customers table
            $table->foreign('user_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            // Drop the customers foreign key constraint
            $table->dropForeign(['user_id']);
            
            // Restore the original foreign key constraint to users table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
