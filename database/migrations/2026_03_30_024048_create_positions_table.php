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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('company_profiles')->cascadeOnDelete();
            $table->string('title');
            $table->string('department');
            $table->string('field_required');
            $table->integer('slots_total');
            $table->integer('slots_filled')->default(0);
            $table->string('duration', ['3_months', '6_months']);
            $table->string('work_days');
            $table->string('work_type', ['onsite', 'hybrid', 'remote']);
            $table->string('work_style', ['hands_on', 'desk', 'field', 'mixed']);
            $table->boolean('mentor_available')->default(false);
            $table->boolean('allowance_available')->default(false);
            $table->decimal('allowance_amount', 10, 2)->nullable();
            $table->text('requirements')->nullable();
            $table->json('next_steps')->nullable();
            $table->date('application_deadline')->nullable();
            $table->integer('acceptance_window_days')->default(3);
            $table->string('status', ['open', 'closed'])->default('open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
