<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('applicant_id')->constrained()->onDelete('cascade');
            $table->timestamp('offered_at');
            $table->timestamp('response_deadline');
            $table->timestamp('response_at')->nullable();
            $table->enum('response_status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
            $table->text('decline_reason')->nullable();
            $table->timestamps();

            $table->index('dispatch_request_id');
            $table->index('applicant_id');
            $table->index('response_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_offers');
    }
};
