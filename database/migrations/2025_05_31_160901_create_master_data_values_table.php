<?php

use App\Models\MasterData;
use App\Models\User;
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
        Schema::create('master_data_values', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MasterData::class)->constrained()->cascadeOnDelete();
            $table->string('master_data_code')->nullable();
            $table->text('value');
            $table->string('type')->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_data_values');
    }
};