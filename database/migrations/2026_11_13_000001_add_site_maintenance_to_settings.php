<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Mode "sedang dibangun" situs publik, diatur dari Setting Situs (dashboard tetap bisa diakses). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('maintenance_mode')->default(false)->after('footer_text');
            $table->string('maintenance_title')->nullable()->after('maintenance_mode');
            $table->text('maintenance_message')->nullable()->after('maintenance_title');
            $table->date('maintenance_until')->nullable()->after('maintenance_message');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['maintenance_mode', 'maintenance_title', 'maintenance_message', 'maintenance_until']);
        });
    }
};
