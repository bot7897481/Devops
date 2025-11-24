<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->unique()->constrained()->onDelete('cascade'); // only one attempt
            $table->foreignId('exam_session_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('total_score', 5, 2)->nullable();
            $table->decimal('section_1_score', 5, 2)->nullable();
            $table->decimal('section_2_score', 5, 2)->nullable();
            $table->decimal('section_3_score', 5, 2)->nullable();
            $table->decimal('section_4_score', 5, 2)->nullable();
            $table->decimal('section_5_score', 5, 2)->nullable();
            $table->integer('section_1_time')->nullable(); // seconds
            $table->integer('section_2_time')->nullable();
            $table->integer('section_3_time')->nullable();
            $table->integer('section_4_time')->nullable();
            $table->integer('section_5_time')->nullable();
            $table->boolean('flagged')->default(false);
            $table->timestamps();

            $table->index('applicant_id');
            $table->index('total_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
