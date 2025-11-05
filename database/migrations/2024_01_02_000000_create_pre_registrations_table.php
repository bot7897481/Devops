<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone', 20);
            $table->string('document_path')->nullable();
            $table->string('reference_number', 50)->unique();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('reference_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_registrations');
    }
};
