<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('time_slot', ['morning', 'afternoon', 'evening']);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location');
            $table->integer('capacity');
            $table->integer('filled_count')->default(0);
            $table->enum('status', ['scheduled', 'in_progress', 'completed'])->default('scheduled');
            $table->timestamps();

            $table->index(['date', 'time_slot']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};
