<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Stok & gudang, kebun & kolam, dapur (PRD 9.10–9.12). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete(); // pusat biaya pemakaian
            $table->foreignId('pic_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('category', 20)->index(); // config('mantaaba.item_categories')
            $table->string('unit', 20);
            $table->decimal('min_stock', 14, 3)->default(0);
            $table->decimal('avg_cost', 18, 4)->default(0); // rata-rata tertimbang
            $table->foreignId('inventory_account_id')->constrained('coa_accounts');
            $table->foreignId('expense_account_id')->constrained('coa_accounts');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('farm_plots', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10); // lahan | kolam | kandang
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->decimal('size', 10, 2)->nullable();
            $table->string('size_unit', 5)->default('m2');
            $table->string('location_note')->nullable();
            $table->string('photo')->nullable();
            $table->string('status', 10)->default('kosong'); // kosong | aktif | istirahat
            $table->foreignId('pic_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('farm_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plot_id')->constrained('farm_plots')->cascadeOnDelete();
            $table->string('commodity');
            $table->string('variety')->nullable();
            $table->date('started_at');
            $table->date('expected_harvest_at')->nullable();
            $table->decimal('initial_qty', 12, 2)->nullable(); // bibit/benih ditanam atau ekor ditebar
            $table->string('initial_unit', 20)->nullable();
            $table->decimal('target_yield', 12, 2)->nullable();
            $table->string('target_unit', 20)->nullable();
            $table->string('status', 10)->default('berjalan')->index(); // berjalan | panen | selesai | gagal
            $table->timestamp('closed_at')->nullable();
            $table->string('failure_reason', 500)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('journal_lines', function (Blueprint $table) {
            $table->foreign('farm_cycle_id')->references('id')->on('farm_cycles')->nullOnDelete();
        });

        Schema::create('stock_moves', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('type', 20); // StockMove::TYPES
            $table->decimal('qty', 14, 3); // + masuk, − keluar
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0); // bertanda sama dengan qty
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('farm_cycle_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('source'); // pembelian (Journal), log kebun, panen, masak, opname
            $table->foreignId('journal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['item_id', 'warehouse_id']);
        });

        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 10)->default('draft'); // draft | final
            $table->json('lines')->nullable(); // [{item_id, system, physical}]
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
        });

        Schema::create('farm_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('farm_cycles')->cascadeOnDelete();
            $table->date('date');
            $table->string('activity', 20); // FarmLog::ACTIVITIES
            $table->decimal('qty', 12, 3)->nullable();
            $table->string('unit', 20)->nullable();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 500)->nullable();
            $table->string('photo')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('harvests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('farm_cycles')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('qty', 12, 3);
            $table->string('unit', 20);
            $table->string('grade', 20)->nullable();
            $table->foreignId('item_id')->constrained();
            $table->string('destination', 10); // dapur | jual | simpan | sedekah
            $table->foreignId('warehouse_id')->constrained();
            $table->decimal('market_price', 14, 2)->default(0); // per unit, untuk nilai hasil
            $table->decimal('sale_price', 14, 2)->nullable(); // per unit bila dijual
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete(); // pembeli
            $table->string('payment', 10)->nullable(); // tunai | transfer | kredit
            $table->foreignId('journal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kitchen_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 15); // pokok | lauk | sayur | pelengkap | minuman | buah
            $table->json('ingredients')->nullable(); // [{item_id, qty}] per porsi
            $table->string('notes', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('meal_plans', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('meal', 15); // sarapan | makan_siang | makan_malam | snack
            $table->json('menu_ids');
            $table->unsignedSmallInteger('planned_portions');
            $table->string('status', 10)->default('rencana'); // rencana | dimasak
            $table->timestamps();
            $table->unique(['date', 'meal']);
        });

        Schema::create('kitchen_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_plan_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('cooked_at');
            $table->unsignedSmallInteger('actual_portions');
            $table->unsignedSmallInteger('leftover_portions')->default(0);
            $table->decimal('cost_total', 18, 2)->default(0);
            $table->string('note', 500)->nullable();
            $table->string('photo')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['kitchen_logs', 'meal_plans', 'kitchen_menus', 'harvests', 'farm_logs', 'stock_opnames', 'stock_moves'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('journal_lines', fn (Blueprint $table) => $table->dropForeign(['farm_cycle_id']));
        foreach (['farm_cycles', 'farm_plots', 'items', 'warehouses'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
