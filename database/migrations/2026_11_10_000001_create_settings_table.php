<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Setting situs, satu baris (PRD 9.1.1). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            // Identitas
            $table->string('site_name')->default('Rumah Mantaaba Sigaling');
            $table->string('foundation_name')->default('Yayasan Mantaaba');
            $table->string('tagline')->nullable();
            $table->string('logo')->nullable();
            $table->string('logo_footer')->nullable();
            $table->string('favicon')->nullable();
            $table->string('og_image')->nullable();
            $table->string('meta_description', 300)->nullable();
            // Tampilan (PRD 7.1)
            $table->string('color_primary', 7)->default('#5B3A1F');
            $table->string('color_secondary', 7)->default('#C9A04A');
            $table->string('color_accent', 7)->default('#8A2D1E');
            $table->string('color_surface', 7)->default('#F7F1E6');
            // Kontak
            $table->string('address')->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->string('regency')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('maps_embed')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('office_hours')->nullable();
            // Media sosial
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('youtube')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('linkedin')->nullable();
            // Donasi
            $table->json('bank_accounts')->nullable();
            $table->string('qris_image')->nullable();
            $table->text('donation_note')->nullable();
            // Pendaftaran
            $table->longText('admission_requirements')->nullable();
            $table->string('parent_consent_template')->nullable();
            $table->string('integrity_pact_template')->nullable();
            $table->json('priority_districts')->nullable();
            // Akademik
            $table->json('assessment_weights')->nullable();
            $table->unsignedTinyInteger('default_pass_score')->default(70);
            $table->unsignedTinyInteger('min_character_score')->default(70);
            // Operasional
            $table->decimal('meal_budget_per_person_day', 18, 2)->nullable();
            $table->unsignedSmallInteger('default_residents')->nullable();
            // Akuntansi
            $table->date('accounting_start_date')->nullable();
            $table->json('report_signatories')->nullable();
            // Lainnya
            $table->text('footer_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
