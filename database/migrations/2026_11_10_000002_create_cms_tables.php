<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD 8.5: CMS website
return new class extends Migration
{
    /** Halaman slug tetap (tidak bisa dihapus). Q10: isi awal placeholder bertanda jelas. */
    public const PAGES = [
        'profil' => 'Profil PD AKP2I Jawa Barat',
        'sambutan' => 'Sambutan Ketua PD',
        'tentang-keanggotaan' => 'Tentang Keanggotaan',
        'layanan-keanggotaan' => 'Layanan Keanggotaan',
        'kebijakan-privasi' => 'Kebijakan Privasi',
        'syarat-ketentuan' => 'Syarat & Ketentuan',
    ];

    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('cover')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->boolean('is_published')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('image');
            $table->string('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('galleries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover')->nullable();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_id')->constrained('galleries')->cascadeOnDelete();
            $table->string('image');
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('category', ['AD/ART', 'Peraturan Organisasi', 'SK', 'Lainnya']);
            $table->string('file');
            $table->text('description')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->string('city')->nullable();
            $table->enum('subject', ['Keanggotaan', 'Pelatihan', 'Iuran', 'Informasi Cabang', 'Lainnya']);
            $table->text('message');
            $table->string('ip', 45)->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });

        foreach (self::PAGES as $slug => $title) {
            DB::table('pages')->insert([
                'slug' => $slug, 'title' => $title,
                'body' => '<p><strong>[Konten contoh]</strong> Isi halaman ini melalui Dashboard → Halaman.</p>',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('gallery_photos');
        Schema::dropIfExists('galleries');
        Schema::dropIfExists('sliders');
        Schema::dropIfExists('pages');
    }
};
