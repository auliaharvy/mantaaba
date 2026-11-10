<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Anggaran, rekonsiliasi kas/bank, donasi (PRD 9.13.4–9.15). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->string('budget_warning', 1000)->nullable()->after('reject_reason'); // soft-stop anggaran yang dikonfirmasi
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->foreignId('coa_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cost_center_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->json('monthly')->nullable(); // 12 nilai, opsional
            $table->timestamps();
            $table->unique(['year', 'coa_account_id', 'cost_center_id']);
        });

        Schema::create('cash_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coa_account_id')->constrained()->cascadeOnDelete();
            $table->decimal('book_balance', 18, 2);
            $table->decimal('actual_balance', 18, 2); // hitung fisik / rekening koran
            $table->string('note', 500)->nullable(); // wajib bila ada selisih
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['fiscal_period_id', 'coa_account_id']);
        });

        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 30)->nullable()->unique(); // DON-{YYYY}-{0001} saat verifikasi
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->string('donor_name');
            $table->boolean('is_anonymous')->default(false);
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('kind', 10)->default('transfer'); // transfer | tunai | barang
            $table->decimal('amount', 18, 2);
            $table->date('transferred_at');
            $table->foreignId('coa_bank_account_id')->nullable()->constrained('coa_accounts')->nullOnDelete(); // rekening/kas tujuan
            $table->string('purpose', 20)->default('umum'); // Donation::PURPOSES
            $table->string('item_description')->nullable(); // donasi barang
            $table->string('proof')->nullable(); // disk local
            $table->text('message')->nullable();
            $table->string('status', 15)->default('menunggu')->index(); // menunggu | terverifikasi | ditolak
            $table->string('reject_reason', 500)->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
        Schema::dropIfExists('cash_reconciliations');
        Schema::dropIfExists('budgets');
        Schema::table('journals', fn (Blueprint $table) => $table->dropColumn('budget_warning'));
    }
};
