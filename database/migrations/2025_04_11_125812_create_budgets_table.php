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
    Schema::create('budgets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained();
        $table->foreignId('category_id')->nullable()->constrained(); // Make category_id nullable
        $table->decimal('amount_limit', 10, 2); // This must NOT be nullable
        $table->string('month', 7); // Format: YYYY-MM
        $table->timestamps();
        
        $table->unique(['user_id', 'category_id', 'month']);
    });
}

    


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
