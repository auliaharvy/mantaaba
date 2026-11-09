<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Agenda, peserta, sertifikat (disederhanakan lebih lanjut di M3/M4). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 300);
            $table->string('slug', 300)->unique();
            $table->enum('type', ['ppl', 'brevet_a', 'brevet_b', 'brevet_c', 'seminar', 'webinar', 'lainnya'])->default('lainnya');
            $table->string('code', 20)->nullable()->unique();
            $table->enum('mode', ['offline', 'online', 'hybrid'])->default('offline');
            $table->longText('body');
            $table->string('thumbnail')->nullable();
            $table->string('location')->nullable();
            $table->string('meeting_url')->nullable();
            $table->dateTime('starts_at')->nullable()->index();
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('registration_opens_at')->nullable();
            $table->dateTime('registration_closes_at')->nullable();
            $table->unsignedInteger('quota')->nullable();
            $table->enum('audience', ['anggota', 'publik'])->default('publik');
            $table->decimal('fee_member', 12, 2)->default(0);
            $table->decimal('fee_public', 12, 2)->default(0);
            $table->decimal('skp_hours', 5, 2)->nullable();
            $table->enum('status', ['draft', 'open', 'closed', 'done'])->default('draft')->index();
            $table->string('certificate_template')->nullable();
            $table->json('certificate_signatory')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('status', ['pending', 'verified', 'rejected', 'cancelled'])->default('pending')->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_proof')->nullable();
            $table->text('reason')->nullable();
            $table->string('file')->nullable();
            $table->string('certificate')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('attended_at')->nullable()->index();
            $table->string('ticket_code', 16)->nullable()->unique();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'user_id']);
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->string('verification_code', 12)->unique();
            $table->string('file')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('events');
    }
};
