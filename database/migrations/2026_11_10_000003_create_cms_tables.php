<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** CMS & konten situs (PRD 9.1). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->string('template', 10)->default('blocks'); // blocks | rich
            $table->json('blocks')->nullable(); // [{type, hidden, data}] (PRD 9.1.3)
            $table->longText('body')->nullable(); // template rich
            $table->string('cover')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('is_system')->default(false); // tidak bisa dihapus
            $table->boolean('show_in_sitemap')->default(true);
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
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_id')->constrained()->cascadeOnDelete();
            $table->string('image');
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category', 50); // Document::CATEGORIES
            $table->string('file'); // disk local, diunduh lewat route ber-policy
            $table->string('visibility', 10)->default('publik'); // publik | anggota
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
            $table->string('subject', 50); // ContactMessage::SUBJECTS
            $table->text('message');
            $table->string('ip', 45)->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 300);
            $table->string('slug', 300)->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('thumbnail')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('blog_tag', function (Blueprint $table) {
            $table->foreignId('blog_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['blog_id', 'tag_id']);
        });

        // PRD 9.1.2: header 2 level, footer 1 level
        Schema::create('nav_menus', function (Blueprint $table) {
            $table->id();
            $table->string('location', 10)->index(); // header | footer
            $table->foreignId('parent_id')->nullable()->constrained('nav_menus')->cascadeOnDelete();
            $table->string('label', 100);
            $table->string('type', 10); // page | route | url
            $table->foreignId('page_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('route_name', 100)->nullable();
            $table->string('url', 500)->nullable();
            $table->boolean('open_new_tab')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // PRD 9.1.5; cost_center_id ditambahkan di M5a
        Schema::create('business_units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type', 20)->default('unit_bisnis'); // unit_bisnis | laboratorium
            $table->string('field')->nullable(); // bidang usaha
            $table->string('summary', 500)->nullable();
            $table->longText('body')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover')->nullable();
            $table->foreignId('gallery_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contact_name')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['business_units', 'nav_menus', 'blog_tag', 'blogs', 'tags', 'categories', 'contact_messages', 'documents', 'gallery_photos', 'galleries', 'sliders', 'pages'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
