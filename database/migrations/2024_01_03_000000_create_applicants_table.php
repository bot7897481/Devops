<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('confirmation_number', 50)->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('dob');
            $table->string('ssn_last4', 4); // encrypted
            $table->string('address_street');
            $table->string('address_city');
            $table->string('address_state', 2);
            $table->string('address_zip', 10);
            $table->json('mailing_address')->nullable();
            $table->string('phone_primary', 20);
            $table->string('phone_alternate', 20)->nullable();
            $table->string('email');
            $table->string('high_school_name');
            $table->date('hs_graduation_date');
            $table->string('diploma_path');
            $table->string('id_document_front_path');
            $table->string('id_document_back_path')->nullable();
            $table->json('work_experience')->nullable();
            $table->timestamp('application_timestamp');
            $table->timestamp('validation_timestamp')->nullable();
            $table->boolean('is_validated')->default(false);
            $table->foreignId('validation_admin_id')->nullable()->constrained('users');
            $table->string('photo_biometric_path')->nullable();
            $table->string('fingerprint_hash')->nullable(); // encrypted
            $table->enum('status', ['pending', 'validated', 'exam_scheduled', 'exam_completed', 'ranked', 'dispatched'])->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index('confirmation_number');
            $table->index('application_timestamp');
            $table->index('validation_timestamp');
            $table->index('is_validated');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
