<?php

namespace Database\Seeders\Demo;

use App\Models\Event;
use App\Models\Gallery;
use App\Models\Slider;
use Illuminate\Database\Seeder;

/** Galeri (foto stok, lihat demo-assets/photos/CREDITS.md) dan slider beranda. */
class MediaSeeder extends Seeder
{
    public function run(): void
    {
        $content = require __DIR__ . '/data/content.php';

        foreach ($content['galleries'] as [$title, $description, $daysAgo, $eventTitle, $photos]) {
            $gallery = Gallery::create([
                'title' => $title, 'description' => $description, 'published_at' => now()->subDays($daysAgo),
                'event_id' => $eventTitle ? Event::where('title', $eventTitle)->value('id') : null,
            ]);
            foreach ($photos as $i => [$photo, $caption]) {
                $gallery->photos()->create(['image' => DemoImage::storePhoto($photo, "galleries/{$gallery->id}"), 'caption' => $caption, 'sort_order' => $i + 1]);
            }
        }

        foreach ($content['sliders'] as $i => [$photo, $title, $subtitle, $button, $target]) {
            [$kind, $value] = explode(':', $target, 2);
            Slider::create([
                'title' => $title, 'subtitle' => $subtitle, 'button_text' => $button, 'sort_order' => $i + 1, 'is_active' => true,
                'image' => DemoImage::storePhoto($photo, 'sliders'),
                'button_url' => $kind === 'route' ? route($value) : route('site.training.show', Event::where('title', $value)->value('slug')),
            ]);
        }
    }
}
