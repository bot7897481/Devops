<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chief_user_id')->constrained('users')->onDelete('cascade');
            $table->string('company_name');
            $table->string('job_location_address');
            $table->string('job_location_city');
            $table->string('job_location_state', 2);
            $table->string('job_type');
            $table->date('start_date');
            $table->integer('positions_needed')->default(1);
            $table->text('job_description');
            $table->text('special_requirements')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'filled', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index('chief_user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_requests');
    }
};
