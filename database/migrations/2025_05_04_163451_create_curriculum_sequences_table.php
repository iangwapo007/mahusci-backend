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
        Schema::create('curriculum_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->text('curriculum_image')->nullable();
            $table->string('is_draft');
            $table->string('grades'); 
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('curriculum_sequence_quarters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_sequence_id')->constrained()->onDelete('cascade');
            $table->integer('quarter');
            $table->timestamps();
        });

        Schema::create('curriculum_sequence_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_sequence_quarter_id')->constrained()->onDelete('cascade');
            $table->foreignId('sequence_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_sequences');
        Schema::dropIfExists('curriculum_sequence_quarters');
        Schema::dropIfExists('curriculum_sequence_items');
    }
};
