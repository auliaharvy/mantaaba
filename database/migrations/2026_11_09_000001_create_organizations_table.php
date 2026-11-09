<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD 8.1: hierarki pusat -> PD -> PC
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->enum('type', ['pusat', 'pd', 'pc']);
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code', 20)->nullable()->unique();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // data dasar (bukan contoh): dibutuhkan sebelum seeder, mis. migrasi jabatan lama
        $now = now();
        $pusat = DB::table('organizations')->insertGetId([
            'type' => 'pusat', 'name' => 'AKP2I Pusat', 'slug' => 'akp2i-pusat', 'code' => 'PUSAT',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('organizations')->insert([
            'parent_id' => $pusat, 'type' => 'pd', 'name' => 'PD AKP2I Jawa Barat', 'slug' => 'pd-akp2i-jawa-barat',
            'code' => 'JABAR', 'city' => 'Bandung', 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
