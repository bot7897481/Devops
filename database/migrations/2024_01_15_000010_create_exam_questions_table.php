<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->enum('section', ['reading', 'math_computation', 'applied_math', 'language', 'aptitude']);
            $table->text('question_text');
            $table->string('question_image_path')->nullable();
            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c');
            $table->text('option_d');
            $table->enum('correct_answer', ['a', 'b', 'c', 'd']);
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
            $table->json('tags')->nullable();
            $table->integer('times_used')->default(0);
            $table->timestamps();

            $table->index(['section', 'difficulty_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
