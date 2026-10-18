<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->string('timezone', 64);
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->timestamp('clock_in_at')->nullable();
            $table->timestamp('clock_out_at')->nullable();
            $table->string('status', 32)->index();
            $table->boolean('clock_in_outside_schedule')->default(false);
            $table->boolean('clock_out_outside_schedule')->default(false);
            $table->string('source', 32)->default('employee');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['employee_profile_id', 'work_date']);
            $table->index(['work_date', 'status']);
        });

        Schema::create('attendance_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_record_id')->nullable()->constrained()->nullOnDelete();
            $table->date('work_date');
            $table->timestamp('proposed_clock_in_at')->nullable();
            $table->timestamp('proposed_clock_out_at')->nullable();
            $table->text('reason');
            $table->string('status', 32)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('final_clock_in_at')->nullable();
            $table->timestamp('final_clock_out_at')->nullable();
            $table->text('adjustment_explanation')->nullable();
            $table->timestamps();
            $table->index(['employee_profile_id', 'work_date'], 'correction_employee_date_idx');
        });

        Schema::create('attendance_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_correction_request_id')->nullable()->constrained()->nullOnDelete();
            $table->json('before_values')->nullable();
            $table->json('after_values');
            $table->text('reason');
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_revisions');
        Schema::dropIfExists('attendance_correction_requests');
        Schema::dropIfExists('attendance_records');
    }
};
