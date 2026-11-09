<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD 8.4: kepengurusan per periode; jabatan lama di users dipindah ke periode awal PD lalu dihapus
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('sk_number')->nullable();
            $table->string('sk_file')->nullable();
            $table->timestamps();
        });

        Schema::create('board_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_period_id')->constrained('board_periods')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['board_period_id', 'user_id', 'position_id']);
        });

        $holders = DB::table('users')->whereNotNull('position_id')->get(['id', 'division_id', 'position_id']);
        if ($holders->isNotEmpty()) {
            $pd = DB::table('organizations')->where('type', 'pd')->orderBy('id')->value('id');
            $period = DB::table('board_periods')->insertGetId([
                'organization_id' => $pd, 'name' => 'Periode awal (migrasi data lama)', 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($holders as $user) {
                DB::table('board_members')->insert([
                    'board_period_id' => $period, 'user_id' => $user->id, 'division_id' => $user->division_id,
                    'position_id' => $user->position_id, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('division_id');
            $table->dropConstrainedForeignId('position_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
        });
        $first = DB::table('board_periods')->orderBy('id')->value('id');
        foreach (DB::table('board_members')->where('board_period_id', $first)->get() as $row) {
            DB::table('users')->where('id', $row->user_id)->update(['division_id' => $row->division_id, 'position_id' => $row->position_id]);
        }
        Schema::dropIfExists('board_members');
        Schema::dropIfExists('board_periods');
    }
};
