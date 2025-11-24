<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indentures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_offer_id')->constrained()->onDelete('cascade');
            $table->foreignId('applicant_id')->constrained()->onDelete('cascade');
            $table->string('company_name');
            $table->date('start_date');
            $table->integer('term_length_years')->default(4);
            $table->json('wage_schedule')->nullable();
            $table->string('document_template_path')->nullable();
            $table->string('signed_document_path')->nullable();
            $table->timestamp('apprentice_signed_at')->nullable();
            $table->timestamp('employer_signed_at')->nullable();
            $table->timestamp('union_signed_at')->nullable();
            $table->enum('status', ['draft', 'pending_signatures', 'fully_executed'])->default('draft');
            $table->boolean('entered_in_unionnet')->default(false);
            $table->string('unionnet_id')->nullable();
            $table->timestamps();

            $table->index('applicant_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indentures');
    }
};
