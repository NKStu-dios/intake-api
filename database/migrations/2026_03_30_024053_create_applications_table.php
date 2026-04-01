<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->string('siwes_duration', ['3_months', '6_months']);
            $table->string('status', [
                'pending',
                'potential',
                'waitlist',
                'accepted',
                'confirmed',
                'declined',
                'expired',
                'withdrawn'
            ])->default('pending');
            $table->json('submitted_documents')->nullable();
            $table->timestamp('acceptance_deadline')->nullable();
            $table->timestamp('accepted_by_student_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
