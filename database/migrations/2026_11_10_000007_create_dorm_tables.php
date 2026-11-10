<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Asrama, jadwal piket (PRD 9.9) + kaitan agenda ke angkatan/skema (PRD 9.8). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('audience')->constrained()->nullOnDelete(); // audience angkatan
            $table->foreignId('skema_id')->nullable()->after('batch_id')->constrained()->nullOnDelete(); // tipe kelas → ringkasan penilaian
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('building')->nullable();
            $table->unsignedTinyInteger('capacity');
            $table->string('gender', 1); // L | P
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('members', function (Blueprint $table) {
            $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
        });

        Schema::create('duty_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('checklist')->nullable(); // daftar tugas/SOP singkat
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('duties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duty_area_id')->constrained()->cascadeOnDelete();
            $table->date('date')->index();
            $table->string('shift', 10); // pagi | siang | sore | malam
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('status', 15)->default('terjadwal')->index(); // terjadwal | selesai | diverifikasi | terlewat
            $table->json('checklist_done')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->string('photo')->nullable(); // disk local
            $table->timestamps();
            $table->unique(['duty_area_id', 'date', 'shift', 'member_id']);
            $table->index(['member_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duties');
        Schema::dropIfExists('duty_areas');
        Schema::table('members', fn (Blueprint $table) => $table->dropForeign(['room_id']));
        Schema::dropIfExists('rooms');
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('skema_id');
            $table->dropConstrainedForeignId('batch_id');
        });
    }
};
