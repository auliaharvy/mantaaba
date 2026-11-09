<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD 8.5: settings diperluas untuk header, footer, CTA, event berbayar (tetap 1 baris)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('site_name')->default('PD AKP2I Jawa Barat')->after('id');
            $table->string('tagline')->nullable()->after('site_name');
            $table->string('logo')->nullable();
            $table->string('logo_footer')->nullable();
            $table->string('favicon')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('facebook')->nullable();
            $table->string('youtube')->nullable();
            $table->string('linkedin')->nullable();
            $table->text('maps_embed')->nullable();
            $table->string('url_registration')->default('https://akp2i.id/pendaftaran-anggota/');
            $table->string('url_member_service')->default('https://akp2i.id/pelayanan-anggota/');
            $table->string('url_ejournal')->nullable();
            $table->text('bank_account_info')->nullable();
            $table->string('instagram')->nullable()->change();
            $table->string('phone')->nullable()->change();
            $table->string('address')->nullable()->change();
        });

        if (DB::table('settings')->doesntExist()) {
            DB::table('settings')->insert(['email' => 'sekretariat@akp2i-jabar.id', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'site_name', 'tagline', 'logo', 'logo_footer', 'favicon', 'whatsapp', 'facebook', 'youtube', 'linkedin',
                'maps_embed', 'url_registration', 'url_member_service', 'url_ejournal', 'bank_account_info',
            ]);
        });
    }
};
