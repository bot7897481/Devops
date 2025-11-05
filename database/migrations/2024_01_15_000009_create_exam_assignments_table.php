<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_session_id')->constrained()->onDelete('cascade');
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('check_in_verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('exam_started_at')->nullable();
            $table->timestamp('exam_completed_at')->nullable();
            $table->enum('status', ['assigned', 'checked_in', 'in_progress', 'completed', 'voided'])->default('assigned');
            $table->timestamps();

            $table->unique(['applicant_id', 'exam_session_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_assignments');
    }
};
