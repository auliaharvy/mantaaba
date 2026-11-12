<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Pembelajaran, penilaian 4 dimensi & sertifikat (PRD 9.6–9.7). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skemas', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // FND, SIM, SIA, SIP, IT
            $table->string('name');
            $table->string('title')->nullable(); // judul lengkap skema (dokumen standar kompetensi)
            $table->string('month_label', 50)->nullable(); // "Bulan 2–3"
            $table->text('description')->nullable();
            $table->longText('objectives')->nullable();
            $table->json('character_matrix')->nullable(); // [{competency, technical, asabiyah, fastabiqul}]
            $table->json('standard')->nullable(); // {competency_map, final_portfolio, assessment_guide, notes} dari template JSON skema
            $table->unsignedTinyInteger('pass_score')->nullable(); // null = Setting
            $table->string('certificate_template')->nullable();
            $table->json('certificate_signatories')->nullable(); // [{name, title, signature}] maks. 2
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skema_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('title');
            $table->text('summary')->nullable(); // deskripsi unit kompetensi
            $table->json('elements')->nullable(); // [{no, title, criteria: [{no, text}]}] elemen & KUK
            $table->json('assessment')->nullable(); // [{dimension, label, weight, indicator, evidence}] 4 dimensi
            $table->json('context')->nullable(); // {label: teks} skenario praktik, portofolio, aspek kritis, prasyarat, ...
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type', 20)->default('text'); // text | video | file | assignment
            $table->longText('body')->nullable();
            $table->string('video_url')->nullable();
            $table->string('file')->nullable(); // disk local
            $table->unsignedSmallInteger('due_days')->nullable(); // tugas: tenggat N hari setelah skema dibuka
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('skema_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skema_id')->constrained()->cascadeOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->string('status', 20)->default('terkunci')->index(); // terkunci | berjalan | lulus | belum_lulus
            $table->timestamps();
            $table->unique(['member_id', 'skema_id']);
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->string('submission_file')->nullable(); // disk local
            $table->text('submission_text')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->text('feedback')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->index();
            $table->unsignedTinyInteger('score')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'lesson_id']);
        });

        Schema::create('skema_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->unique()->constrained('skema_enrollments')->cascadeOnDelete();
            $table->decimal('knowledge', 5, 2)->nullable();
            $table->decimal('skill', 5, 2)->nullable();
            $table->decimal('character', 5, 2)->nullable();
            $table->decimal('social_cohesion', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->boolean('passed')->default(false);
            $table->string('predicate', 20)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamp('locked_at')->nullable(); // terisi saat sertifikat terbit
            $table->timestamps();
        });

        // sertifikat skema (skema_result_id) & kelulusan inkubasi (series INK, tanpa result)
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skema_result_id')->nullable()->constrained()->nullOnDelete();
            $table->string('series', 10); // kode skema atau INK
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('sequence');
            $table->string('number')->unique(); // {urut}/{seri}/RMS/{bulan romawi}/{tahun}
            $table->string('verification_code', 12)->unique();
            $table->string('file')->nullable(); // disk local
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoke_reason', 500)->nullable();
            $table->timestamps();
            $table->unique(['series', 'year', 'sequence']); // T1: nomor tidak pernah ganda
        });
    }

    public function down(): void
    {
        foreach (['certificates', 'skema_results', 'lesson_progress', 'skema_enrollments', 'lessons', 'modules', 'skemas'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
