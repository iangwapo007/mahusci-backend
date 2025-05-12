<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssessmentsTable extends Migration
{
    public function up()
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers');
            $table->string('title');
            $table->enum('type', [
                'multiple_choice', 
                'true_false', 
                'short_answer', 
                'essay', 
                'matching', 
                'fill_blank', 
                '4pics1word', 
                'comparison_table', 
                'mind_mapping'
            ]);
            $table->integer('total_points');
            $table->integer('max_points');
            $table->integer('time_limit')->nullable();
            $table->text('instructions')->nullable();
            $table->json('questions')->comment('JSON structure of all questions');
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('assessments');
    }
}
