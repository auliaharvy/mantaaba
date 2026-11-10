<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Aset & kendaraan (PRD 9.17). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // AST-{KAT}-{001}
            $table->string('name');
            $table->string('category', 20)->index(); // config('mantaaba.asset_categories')
            $table->string('location')->nullable();
            $table->string('condition', 15)->default('baik'); // baik | rusak_ringan | rusak_berat | hilang | dilepas
            $table->foreignId('pic_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('acquired_at');
            $table->decimal('acquisition_value', 18, 2);
            $table->string('source', 10)->default('beli'); // beli | hibah | wakaf
            $table->unsignedSmallInteger('useful_life_months')->nullable(); // null/0 = tidak disusutkan (tanah)
            $table->decimal('residual_value', 18, 2)->default(0);
            $table->date('depreciation_start')->nullable();
            $table->decimal('opening_accumulated', 18, 2)->default(0); // akumulasi penyusutan sebelum tanggal mulai pembukuan
            $table->foreignId('asset_account_id')->constrained('coa_accounts');
            $table->foreignId('accumulated_account_id')->nullable()->constrained('coa_accounts');
            $table->foreignId('expense_account_id')->nullable()->constrained('coa_accounts');
            $table->foreignId('journal_id')->nullable()->constrained()->nullOnDelete(); // voucher perolehan
            $table->string('photo')->nullable();
            $table->json('documents')->nullable(); // [{path, name}] disk local (BPKB/STNK privat)
            $table->text('notes')->nullable();
            $table->date('disposed_at')->nullable();
            $table->string('disposal_note', 500)->nullable();
            $table->foreignId('disposal_journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('journal_lines', function (Blueprint $table) {
            $table->foreign('asset_id')->references('id')->on('assets')->nullOnDelete();
        });

        Schema::create('asset_vehicles', function (Blueprint $table) {
            $table->foreignId('asset_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('plate_number', 20)->nullable();
            $table->string('brand_type')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color', 30)->nullable();
            $table->date('stnk_due_at')->nullable();
            $table->date('tax_due_at')->nullable();
            $table->date('kir_due_at')->nullable();
            $table->unsignedInteger('last_odometer')->default(0);
            $table->unsignedInteger('service_interval_km')->nullable();
            $table->unsignedSmallInteger('service_interval_days')->nullable();
            $table->date('last_service_at')->nullable();
            $table->unsignedInteger('last_service_km')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose');
            $table->string('destination')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('odometer_start')->nullable();
            $table->unsignedInteger('odometer_end')->nullable();
            $table->decimal('fuel_cost', 14, 2)->nullable();
            $table->decimal('fuel_liters', 8, 2)->nullable();
            $table->string('receipt')->nullable(); // disk local
            $table->string('note', 500)->nullable();
            $table->string('status', 10)->default('dipakai'); // dipakai | kembali
            $table->foreignId('journal_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('asset_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('type', 15); // servis | perbaikan | pajak | stnk | kir | lainnya
            $table->string('description');
            $table->decimal('cost', 14, 2)->default(0);
            $table->foreignId('vendor_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->unsignedInteger('odometer')->nullable();
            $table->date('next_due_at')->nullable();
            $table->string('attachment')->nullable(); // disk local
            $table->foreignId('journal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenances');
        Schema::dropIfExists('asset_usages');
        Schema::dropIfExists('asset_vehicles');
        Schema::table('journal_lines', fn (Blueprint $table) => $table->dropForeign(['asset_id']));
        Schema::dropIfExists('assets');
    }
};
