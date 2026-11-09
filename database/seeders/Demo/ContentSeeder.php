<?php

namespace Database\Seeders\Demo;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Comment;
use App\Models\ContactMessage;
use App\Models\Document;
use App\Models\ForumCategory;
use App\Models\Page;
use App\Models\Report;
use App\Models\Research;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\ContentTaxonomySeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Halaman CMS, berita, forum, jurnal, dokumen, dan pesan kontak demo. */
class ContentSeeder extends Seeder
{
    private array $content;

    public function run(): void
    {
        $this->content = require __DIR__ . '/data/content.php';
        $this->call(ContentTaxonomySeeder::class);

        $this->pages();
        $this->blogs();
        $this->forum();
        $this->research();
        $this->documents();
        $this->contactMessages();
    }

    /** Ganti {ketua} dll. dengan nama pengurus demo. */
    private function fill(string $text): string
    {
        $o = PeopleSeeder::$officials;

        return strtr($text, ['{ketua}' => $o['ketua']->name, '{sekretaris}' => $o['sekretaris']->name, '{diklat}' => $o['diklat']->name]);
    }

    private function members()
    {
        return User::role('anggota')->whereHas('member', fn ($q) => $q->where('status', 'aktif'))->get();
    }

    private function pages(): void
    {
        $editor = PeopleSeeder::$officials['humas'];
        foreach ($this->content['pages'] as $slug => [$title, $excerpt, $body]) {
            Page::where('slug', $slug)->update(['title' => $title, 'excerpt' => $excerpt, 'body' => $this->fill($body), 'meta_description' => Str::limit($excerpt, 290), 'updated_by' => $editor->id]);
        }
    }

    private function blogs(): void
    {
        $o = PeopleSeeder::$officials;
        $staffAuthors = [$o['humas'], $o['humas'], $o['sekretaris'], $o['diklat']];
        $members = $this->members();

        foreach ($this->content['blogs'] as $row) {
            [$category, $title, $excerpt, $paragraphs, $photo, $daysAgo, $tags, $status] = $row + [7 => 'published'];
            $author = $status === 'published' ? DemoSeeder::pick($staffAuthors) : $members->random();
            $published = now()->subDays($daysAgo)->setTime(mt_rand(8, 16), mt_rand(0, 59));
            $blog = Blog::create([
                'title' => $title,
                'excerpt' => $excerpt,
                'body' => '<p>' . implode('</p><p>', array_map(fn ($p) => $this->fill($p), $paragraphs)) . '</p>',
                'category_id' => Category::where('name', $category)->value('id'),
                'thumbnail' => DemoImage::poster($photo, $category, $title, $published->translatedFormat('j F Y'), 'blog'),
                'status' => $status,
                'published_at' => $status === 'published' ? $published : null,
                'user_id' => $author->id,
                'organization_id' => $author->member?->organization_id,
            ]);
            $blog->forceFill(['views' => $status === 'published' ? mt_rand(40, 40 + $daysAgo * 9) : 0, 'created_at' => $published, 'updated_at' => $published])->saveQuietly();
            $blog->tags()->sync(collect($tags)->map(fn ($t) => Tag::firstOrCreate(['slug' => Str::slug($t)], ['name' => $t])->id));
        }
    }

    private function forum(): void
    {
        $members = $this->members();
        $moderator = PeopleSeeder::$officials['humas'];

        foreach ($this->content['threads'] as $row) {
            [$category, $title, $body, $replies, $flag] = $row + [4 => null];
            $createdAt = now()->subDays(mt_rand(5, 120))->setTime(mt_rand(7, 21), mt_rand(0, 59));
            $thread = Thread::create([
                'forum_category_id' => ForumCategory::where('name', $category)->value('id'),
                'title' => $title,
                'body' => "<p>{$body}</p>",
                'user_id' => $flag === 'pinned' ? $moderator->id : $members->random()->id,
                'status' => 'published',
                'is_pinned' => $flag === 'pinned',
                'is_locked' => $flag === 'locked',
            ]);
            $at = $createdAt->copy();
            $previous = null;
            foreach ($replies as $reply) {
                $at = $at->copy()->addHours(mt_rand(2, 40));
                $isReply = str_starts_with($reply, '>');
                $comment = Comment::create([
                    'thread_id' => $thread->id,
                    'parent_id' => $isReply && $previous ? ($previous->parent_id ?? $previous->id) : null,
                    'body' => ltrim($reply, '>'),
                    'user_id' => $members->where('id', '!=', $previous?->user_id)->random()->id,
                ]);
                $comment->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
                $previous = $comment;
            }
            $thread->forceFill(['created_at' => $createdAt, 'updated_at' => $at, 'last_activity_at' => $at, 'views' => mt_rand(15, 350)])->saveQuietly();
        }

        // satu laporan terbuka untuk antrean moderator
        $comment = Comment::inRandomOrder()->first();
        Report::create(['reportable_type' => $comment->getMorphClass(), 'reportable_id' => $comment->id,
            'user_id' => $members->where('id', '!=', $comment->user_id)->random()->id, 'reason' => 'Mengandung promosi jasa pribadi.']);
    }

    private function research(): void
    {
        $members = User::role('anggota')->whereHas('member', fn ($q) => $q->where('membership_type', 'utama'))->get();
        $uploader = PeopleSeeder::$officials['humas'];

        foreach ($this->content['research'] as [$title, $category, $abstract, $keywords]) {
            $authors = $members->random(mt_rand(1, 3))->map(fn ($u) => Str::before($u->name, ','))->implode(', ');
            $date = now()->subDays(mt_rand(30, 900));
            $file = 'research/' . Str::slug($title) . '.pdf';
            Storage::disk('local')->put($file, Pdf::loadHTML(
                "<h2 style='font-family:sans-serif'>{$title}</h2><p><em>{$authors}</em></p><h4>Abstrak</h4><p>{$abstract}</p><p><strong>Kata kunci:</strong> {$keywords}</p>"
                . '<p style="color:#888">Dokumen contoh untuk demo aplikasi PD AKP2I Jawa Barat.</p>'
            )->output());

            $journal = Research::create(['title' => $title, 'authors' => $authors, 'abstract' => $abstract, 'keywords' => $keywords, 'category' => $category,
                'date_of_publish' => $date, 'file' => $file, 'status' => 'published', 'uploaded_by' => $uploader->id]);
            $journal->forceFill(['downloads' => mt_rand(5, 180)])->saveQuietly();
        }
    }

    private function documents(): void
    {
        foreach ($this->content['documents'] as $i => [$title, $category, $description]) {
            $file = 'documents/' . Str::slug($title) . '.pdf';
            Storage::disk('public')->put($file, Pdf::loadHTML(
                "<div style='font-family:sans-serif'><h2>{$title}</h2><p>{$description}</p><p>Pasal 1 — Ketentuan umum.</p><p>Pasal 2 — Ruang lingkup.</p>"
                . '<p style="color:#888">Dokumen contoh untuk demo; isi resmi diunggah sekretariat saat go-live.</p></div>'
            )->output());
            Document::create(['title' => $title, 'category' => $category, 'file' => $file, 'description' => $description,
                'published_at' => now()->subDays(mt_rand(60, 600)), 'sort_order' => $i]);
        }
    }

    private function contactMessages(): void
    {
        foreach ($this->content['contact_messages'] as [$subject, $message, $read]) {
            [$first, $last] = [DemoSeeder::pick(['Dimas', 'Laras', 'Rahmat', 'Sinta', 'Fahmi', 'Wulan', 'Arya', 'Putri']), DemoSeeder::pick(['Prakoso', 'Anggraini', 'Hidayat', 'Maharani', 'Saputra'])];
            $at = now()->subDays(mt_rand(1, 40))->setTime(mt_rand(8, 21), mt_rand(0, 59));
            $msg = ContactMessage::create([
                'name' => "{$first} {$last}", 'email' => Str::lower("{$first}.{$last}@example.com"), 'phone' => '0813-' . mt_rand(1000, 9999) . '-' . mt_rand(1000, 9999),
                'city' => DemoSeeder::pick(['Bandung', 'Bekasi', 'Bogor', 'Garut', 'Cirebon']), 'subject' => $subject, 'message' => $message,
                'read_at' => $read ? $at->copy()->addHours(5) : null,
            ]);
            $msg->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
        }
    }
}
