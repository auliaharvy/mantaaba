<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Agenda & peserta (PRD 9.8). batch_id & skema_id ditambahkan di migrasi anggota/pembelajaran. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 300);
            $table->string('slug', 300)->unique();
            $table->string('type', 30)->default('kegiatan_umum')->index(); // Event::TYPES
            $table->string('mode', 10)->default('offline'); // offline | online | hybrid
            $table->longText('body')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('location')->nullable();
            $table->string('meeting_url')->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->dateTime('registration_opens_at')->nullable();
            $table->dateTime('registration_closes_at')->nullable();
            $table->unsignedInteger('quota')->nullable();
            $table->string('audience', 10)->default('publik'); // publik | anggota | angkatan
            $table->string('status', 10)->default('draft')->index(); // draft | open | closed | done
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); // null = tamu publik
            $table->string('guest_name')->nullable();
            $table->string('guest_phone', 30)->nullable();
            $table->string('guest_email')->nullable();
            $table->string('status', 10)->default('terdaftar')->index(); // terdaftar | hadir | batal
            $table->string('ticket_code', 16)->unique();
            $table->timestamp('attended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
        Schema::dropIfExists('events');
    }
};
