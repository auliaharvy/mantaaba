<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Akuntansi double-entry ISAK 35 (PRD 9.13). Uang decimal(18,2), dibandingkan dalam sen. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coa_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // 1-1101
            $table->string('name');
            $table->string('type', 15); // aset | liabilitas | aset_neto | pendapatan | beban
            $table->string('normal_balance', 6); // debit | kredit
            $table->foreignId('parent_id')->nullable()->constrained('coa_accounts')->nullOnDelete();
            $table->boolean('is_header')->default(false);
            $table->boolean('is_cash')->default(false);
            $table->boolean('is_bank')->default(false);
            $table->boolean('is_receivable')->default(false);
            $table->boolean('is_payable')->default(false);
            $table->boolean('is_contra')->default(false);
            $table->string('restriction', 20)->nullable(); // tanpa_pembatasan | dengan_pembatasan
            $table->string('cash_flow_group', 10)->nullable(); // operasi | investasi | pendanaan
            $table->string('statement_group', 50)->nullable(); // pos laporan
            $table->json('tax_mapping')->nullable(); // v2
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('type', 15); // Party::TYPES
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('npwp', 30)->nullable();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('type', 15); // program | operasional | unit_bisnis | umum
            $table->foreignId('business_unit_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('status', 10)->default('open'); // open | closed
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reopen_reason', 500)->nullable();
            $table->timestamps();
            $table->unique(['year', 'month']);
        });

        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->nullable()->unique(); // {TYPE}/{YYYY}/{MM}/{0001}, diberikan saat posting
            $table->string('voucher_type', 3)->index(); // BPV BRV CPV CRV RJV PJV MJV AJV
            $table->date('date')->index();
            $table->foreignId('fiscal_period_id')->constrained();
            $table->string('description', 500);
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_no', 100)->nullable();
            $table->json('attachments')->nullable(); // [{path, name}] disk local
            $table->string('status', 12)->default('draft')->index(); // draft | diajukan | ditolak | terposting | dibalik
            $table->boolean('is_system')->default(false);
            $table->nullableMorphs('source'); // transaksi asal (donasi, stok, kesehatan, saldo awal, ...)
            $table->foreignId('reversal_of_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->string('reject_reason', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coa_account_id')->constrained()->restrictOnDelete();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('farm_cycle_id')->nullable(); // FK di M6
            $table->unsignedBigInteger('asset_id')->nullable(); // FK di M7
            $table->string('memo')->nullable();
            $table->timestamps();
            $table->index(['coa_account_id', 'journal_id']);
        });
    }

    public function down(): void
    {
        foreach (['journal_lines', 'journals', 'fiscal_periods', 'cost_centers', 'parties', 'coa_accounts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
