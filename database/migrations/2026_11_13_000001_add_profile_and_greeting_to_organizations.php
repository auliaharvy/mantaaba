<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Profil & sambutan ketua per PC, tampil di /pengurus-cabang/{slug}
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->text('profile')->nullable()->after('email');
            $table->text('greeting')->nullable()->after('profile');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', fn (Blueprint $table) => $table->dropColumn(['profile', 'greeting']));
    }
};
