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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('scope_id')->constrained('scopes')->cascadeOnDelete();
            $table->foreignId('vulnerability_type_id')->constrained('vulnerability_types')->cascadeOnDelete();

            $table->string('title');
            $table->string('endpoint');
            $table->string('parameter')->nullable();

            $table->string('cvss_vector_claimed');
            $table->decimal('cvss_score_claimed', 3, 1);
            $table->string('severity_claimed');

            $table->string('cvss_vector_final')->nullable();
            $table->decimal('cvss_score_final', 3, 1)->nullable();
            $table->string('severity_final')->nullable();

            // Kolom payload yang wajib terenkripsi
            $table->longText('description');
            $table->longText('steps_to_reproduce');
            $table->longText('impact');
            $table->longText('remediation')->nullable();

            $table->enum('status', [
                'submitted',
                'in_review',
                'needs_info',
                'valid',
                'invalid',
                'duplicate',
                'rewarded',
            ])->default('submitted');

            $table->decimal('quality_factor', 3, 2)->nullable();
            $table->integer('points_awarded')->default(0);
            $table->text('points_override_note')->nullable();

            $table->foreignId('duplicate_of')->nullable()->constrained('reports')->nullOnDelete();
            $table->unsignedInteger('revision_no')->default(1);

            $table->timestamp('submitted_at')->useCurrent();
            $table->foreignId('triaged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('triaged_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            // Indeks performa pencarian dan kuota
            $table->index(['team_id', 'status']);
            $table->index(['scope_id', 'vulnerability_type_id']);
            $table->index(['status', 'submitted_at']);
        });

        Schema::create('report_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->unsignedInteger('revision_no');
            $table->longText('snapshot'); // JSON terenkripsi
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('report_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name'); // UUID acak
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64);
            $table->string('disk_path');
            $table->timestamps();
        });

        Schema::create('report_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });

        Schema::create('report_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_status_histories');
        Schema::dropIfExists('report_comments');
        Schema::dropIfExists('report_attachments');
        Schema::dropIfExists('report_versions');
        Schema::dropIfExists('reports');
    }
};
