<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->unique()->constrained()->onDelete('cascade');
            $table->integer('rank');
            $table->decimal('exam_score', 5, 2);
            $table->timestamp('application_timestamp');
            $table->timestamp('validation_timestamp');
            $table->boolean('is_internal_promotion')->default(false);
            $table->date('employment_start_date')->nullable();
            $table->timestamp('ranked_at');
            $table->timestamps();

            $table->index('rank');
            $table->index('applicant_id');
            $table->index('is_internal_promotion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rankings');
    }
};
