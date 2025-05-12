<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('teacher_id')->constrained('teachers')->nullable();
            $table->string('subject');
            $table->string('grades'); // Will store comma-separated grades (7,8,9,etc)
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced']);
            $table->enum('quarter', ['1', '2', '3', '4']);
            $table->text('objectives'); // JSON encoded array of objectives
            $table->boolean('is_draft')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lessons');
    }
};