<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// PRD 8.6 (blog), 8.7 (forum), 8.8 (jurnal), 8.11 (preferensi notifikasi). Data lama ikut dikonversi.
return new class extends Migration
{
    public function up(): void
    {
        // ---- Blog
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
        Schema::create('blog_tag', function (Blueprint $table) {
            $table->foreignId('blog_id')->constrained('blogs')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['blog_id', 'tag_id']);
        });
        Schema::table('blogs', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('id')->constrained('categories')->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->after('user_id')->constrained('organizations')->nullOnDelete();
            $table->text('excerpt')->nullable()->after('slug');
            $table->enum('status', ['draft', 'review', 'published'])->default('review')->after('thumbnail')->index();
            $table->timestamp('published_at')->nullable()->after('status')->index();
            $table->unsignedInteger('views')->default(0);
            $table->string('thumbnail')->nullable()->change();
            $table->longText('body')->change();
            $table->softDeletes();
        });
        DB::table('blogs')->where('publication_status', 1)->update(['status' => 'published', 'published_at' => DB::raw('created_at')]);
        DB::table('blogs')->where('publication_status', 0)->update(['status' => 'review']);
        // nama file lama (public/blog/<nama>) -> path relatif disk public
        DB::table('blogs')->whereNotNull('thumbnail')->where('thumbnail', 'not like', '%/%')->update(['thumbnail' => DB::raw("CONCAT('blog/', thumbnail)")]);
        Schema::table('blogs', fn (Blueprint $table) => $table->dropColumn('publication_status'));

        // ---- Forum
        Schema::create('forum_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::table('threads', function (Blueprint $table) {
            $table->foreignId('forum_category_id')->nullable()->after('id')->constrained('forum_categories')->nullOnDelete();
            $table->enum('status', ['published', 'hidden'])->default('published')->after('body')->index();
            $table->boolean('is_pinned')->default(false)->after('status');
            $table->boolean('is_locked')->default(false)->after('is_pinned');
            $table->unsignedInteger('views')->default(0);
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->longText('body')->change();
            $table->softDeletes();
        });
        DB::table('threads')->where('publication_status', 0)->update(['status' => 'hidden']);
        DB::table('threads')->update(['last_activity_at' => DB::raw('updated_at')]);
        Schema::table('threads', fn (Blueprint $table) => $table->dropColumn('publication_status'));

        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('thread_id')->constrained('comments')->cascadeOnDelete();
            $table->softDeletes();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->morphs('reportable');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 500);
            $table->timestamp('resolved_at')->nullable()->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_forum_replies')->default(true)->after('is_active');
        });

        // ---- Jurnal
        Schema::table('research', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
            $table->string('authors')->nullable()->after('slug');
            $table->text('abstract')->nullable();
            $table->string('keywords')->nullable();
            $table->string('category')->nullable()->index();
            $table->string('cover')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'published'])->default('published')->index();
            $table->unsignedInteger('downloads')->default(0);
            $table->softDeletes();
        });
        foreach (DB::table('research')->get(['id', 'title', 'file']) as $row) {
            DB::table('research')->where('id', $row->id)->update([
                'slug' => Str::slug($row->title) . '-' . $row->id,
                'file' => str_contains($row->file, '/') ? $row->file : "research/{$row->file}",
            ]);
        }
        Schema::table('research', fn (Blueprint $table) => $table->unique('slug'));
    }

    public function down(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropConstrainedForeignId('uploaded_by');
            $table->dropColumn(['slug', 'authors', 'abstract', 'keywords', 'category', 'cover', 'status', 'downloads', 'deleted_at']);
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('notify_forum_replies'));
        Schema::dropIfExists('reports');
        Schema::table('comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropSoftDeletes();
        });
        Schema::table('threads', function (Blueprint $table) {
            $table->boolean('publication_status')->default(true);
        });
        DB::table('threads')->update(['publication_status' => DB::raw("status = 'published'")]);
        Schema::table('threads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('forum_category_id');
            $table->dropColumn(['status', 'is_pinned', 'is_locked', 'views', 'last_activity_at', 'deleted_at']);
        });
        Schema::dropIfExists('forum_categories');
        Schema::table('blogs', function (Blueprint $table) {
            $table->boolean('publication_status')->default(false);
        });
        DB::table('blogs')->update(['publication_status' => DB::raw("status = 'published'")]);
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn(['excerpt', 'status', 'published_at', 'views', 'deleted_at']);
        });
        Schema::dropIfExists('blog_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');
    }
};
