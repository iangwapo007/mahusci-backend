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
        Schema::create('sequence_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('item_id');
            $table->enum('item_type', ['lesson', 'assessment']);
            $table->integer('position');
            $table->string('title');
            $table->timestamps();

            $table->index(['item_id', 'item_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequence_items');
    }
};
