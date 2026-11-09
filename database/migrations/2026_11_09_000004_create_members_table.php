<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PRD 8.2 (members) & 8.3 (member_dues)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->string('member_number')->unique();
            $table->string('kip_number')->nullable();
            $table->enum('membership_type', ['utama', 'madya', 'pratama', 'kehormatan']);
            $table->enum('certification', ['A', 'B', 'C'])->nullable();
            $table->json('brevet')->nullable();
            $table->string('npwp')->nullable();
            $table->string('nik')->nullable();
            $table->string('office_name')->nullable();
            $table->date('joined_at')->nullable();
            $table->string('sk_number')->nullable();
            $table->enum('status', ['aktif', 'skors', 'berhenti'])->default('aktif')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('member_dues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->smallInteger('year');
            $table->decimal('amount', 12, 2);
            $table->date('paid_at');
            $table->string('proof_file')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['member_id', 'year']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->json('dues_amounts')->nullable(); // Q4: nominal iuran per jenis anggota
        });
    }

    public function down(): void
    {
        Schema::table('settings', fn (Blueprint $table) => $table->dropColumn('dues_amounts'));
        Schema::dropIfExists('member_dues');
        Schema::dropIfExists('members');
    }
};
