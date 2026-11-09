<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// PRD 8.9: event dengan pendaftaran mandiri, verifikasi, kehadiran, sertifikat. Data lama dikonversi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained('organizations')->nullOnDelete();
            $table->enum('type', ['ppl', 'brevet_a', 'brevet_b', 'brevet_c', 'seminar', 'webinar', 'lainnya'])->default('lainnya')->after('slug');
            $table->string('code', 20)->nullable()->unique()->after('type');
            $table->enum('mode', ['offline', 'online', 'hybrid'])->default('offline')->after('code');
            $table->string('meeting_url')->nullable()->after('location');
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
            $table->string('thumbnail')->nullable()->change();
            $table->string('location')->nullable()->change();
            $table->longText('body')->change();
            $table->softDeletes();
        });

        $pd = DB::table('organizations')->where('type', 'pd')->orderBy('id')->value('id');
        foreach (DB::table('events')->get() as $event) {
            DB::table('events')->where('id', $event->id)->update([
                'organization_id' => $pd,
                'code' => 'EV' . str_pad($event->id, 4, '0', STR_PAD_LEFT),
                'starts_at' => $event->date_start . ' 08:00:00',
                'ends_at' => $event->date_end . ' 17:00:00',
                'status' => $event->event_status ? 'open' : 'done', // lama: 0 = terlaksana
                'thumbnail' => $event->thumbnail && ! str_contains($event->thumbnail, '/') ? "event/{$event->thumbnail}" : $event->thumbnail,
            ]);
        }
        Schema::table('events', fn (Blueprint $table) => $table->dropColumn(['date_start', 'date_end', 'event_status']));

        Schema::table('participants', function (Blueprint $table) {
            $table->enum('status', ['pending', 'verified', 'rejected', 'cancelled'])->default('pending')->after('user_id')->index();
            $table->decimal('amount', 12, 2)->default(0)->after('status');
            $table->string('payment_proof')->nullable()->after('amount');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('attended_at')->nullable()->index();
            $table->string('ticket_code', 16)->nullable()->unique();
            $table->text('notes')->nullable();
            $table->text('reason')->nullable()->change();
            $table->string('file')->nullable()->change();
        });
        foreach (DB::table('participants')->get() as $p) {
            DB::table('participants')->where('id', $p->id)->update([
                'status' => $p->data_status ? 'verified' : 'pending',
                'ticket_code' => strtoupper(Str::random(10)),
                'file' => $p->file && ! str_contains($p->file, '/') ? "participant/file/{$p->file}" : $p->file,
                'certificate' => $p->certificate && ! str_contains($p->certificate, '/') ? "participant/certificate/{$p->certificate}" : $p->certificate,
            ]);
        }
        Schema::table('participants', fn (Blueprint $table) => $table->dropColumn('data_status'));

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->unique()->constrained('participants')->cascadeOnDelete();
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

        Schema::table('participants', fn (Blueprint $table) => $table->boolean('data_status')->default(false));
        DB::table('participants')->update(['data_status' => DB::raw("status = 'verified'")]);
        Schema::table('participants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropUnique(['ticket_code']);
            $table->dropColumn(['status', 'amount', 'payment_proof', 'verified_at', 'attended_at', 'ticket_code', 'notes']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->boolean('event_status')->default(true);
        });
        DB::table('events')->update([
            'date_start' => DB::raw('DATE(starts_at)'),
            'date_end' => DB::raw('DATE(ends_at)'),
            'event_status' => DB::raw("status <> 'done'"),
        ]);
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropUnique(['code']);
            $table->dropColumn(['type', 'code', 'mode', 'meeting_url', 'starts_at', 'ends_at', 'registration_opens_at', 'registration_closes_at',
                'quota', 'audience', 'fee_member', 'fee_public', 'skp_hours', 'status', 'certificate_template', 'certificate_signatory', 'deleted_at']);
        });
    }
};
