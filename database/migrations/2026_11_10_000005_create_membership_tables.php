<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Penerimaan & anggota (PRD 9.2–9.5, 9.19, 9.20). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // 2027-1
            $table->string('name');
            $table->date('admission_opens_at')->nullable();
            $table->date('admission_closes_at')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->unsignedSmallInteger('quota')->default(6);
            $table->string('status', 20)->default('draft')->index(); // Batch::STATUSES
            $table->text('description')->nullable();
            $table->text('requirements_note')->nullable();
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('member_number', 20)->unique(); // MTB-{tahun}{urut 4}
            $table->string('name');
            $table->string('category', 20)->index(); // Member::CATEGORIES
            $table->string('status', 20)->default('aktif')->index();
            $table->string('phase', 20)->nullable()->index();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('applicant_id')->nullable();
            // privat (member-private)
            $table->string('nik', 16)->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 1)->nullable();
            $table->string('address')->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable()->index();
            $table->string('regency')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone', 30)->nullable();
            $table->string('family_status', 20)->nullable();
            $table->string('economic_status', 20)->nullable();
            $table->json('documents')->nullable(); // disk local
            // umum
            $table->unsignedBigInteger('room_id')->nullable(); // FK ditambahkan di M4
            $table->date('joined_at')->nullable();
            $table->date('left_at')->nullable();
            $table->string('left_reason', 500)->nullable();
            $table->timestamp('integrity_pact_signed_at')->nullable();
            $table->string('integrity_pact_file')->nullable();
            $table->boolean('show_on_site')->default(false);
            $table->string('photo')->nullable(); // disk public
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->string('registration_number', 30)->unique(); // PMB-{kode}-{urut 3}
            $table->string('name');
            $table->string('nik', 16);
            $table->string('birth_place');
            $table->date('birth_date');
            $table->string('gender', 1);
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('address');
            $table->string('village');
            $table->string('district')->index();
            $table->string('regency');
            $table->string('education', 30);
            $table->string('school_name')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('parent_job')->nullable();
            $table->string('guardian_phone', 30);
            $table->string('family_status', 20);
            $table->string('economic_status', 20);
            $table->unsignedTinyInteger('siblings')->nullable();
            $table->text('motivation');
            $table->string('info_source', 50);
            $table->text('health_history')->nullable();
            $table->json('documents'); // disk local
            $table->boolean('is_priority')->default(false)->index();
            $table->string('status', 20)->default('baru')->index(); // Applicant::STATUSES
            $table->json('scores')->nullable();
            $table->json('history')->nullable(); // [{status, note, by, at}]
            $table->dateTime('interview_at')->nullable();
            $table->text('interview_note')->nullable();
            $table->dateTime('home_visit_at')->nullable();
            $table->text('home_visit_note')->nullable();
            $table->text('decision_note')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable()->index();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['batch_id', 'nik']); // AC 9.3: NIK sama di angkatan sama ditolak
        });

        Schema::create('member_journeys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // MemberJourney::TYPES
            $table->string('title');
            $table->foreignId('business_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role_level', 20)->nullable();
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->json('meta')->nullable(); // NIM, semester, IPK UT
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['member_id', 'type', 'ended_at']);
        });

        Schema::create('health_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->date('date')->index();
            $table->string('complaint');
            $table->string('action', 20); // HealthRecord::ACTIONS
            $table->string('facility')->nullable();
            $table->decimal('cost', 18, 2)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('coaching_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete(); // null = sistem
            $table->date('date')->index();
            $table->string('type', 30); // CoachingNote::TYPES
            $table->string('dimension', 30);
            $table->text('body');
            $table->string('visibility', 10)->default('privat'); // privat | anggota
            $table->text('follow_up')->nullable();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('audience', 20)->default('semua'); // Announcement::AUDIENCES
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['announcements', 'coaching_notes', 'health_records', 'member_journeys', 'applicants', 'members', 'batches', 'notifications'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
