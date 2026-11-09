<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Setting;
use App\Support\Upload;
use Illuminate\Http\Request;

/** Pengaturan situs (PRD 8.5), satu baris. */
class SettingController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Setting::class, 'setting');
    }

    public function edit(Setting $setting)
    {
        return view('dashboard.setting.edit', compact('setting'));
    }

    public function update(Request $request, Setting $setting)
    {
        $data = $request->validate([
            'site_name' => 'required|string|max:100',
            'tagline' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'whatsapp' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'maps_embed' => ['nullable', 'string', 'max:2000', 'regex:#^https://(www\.google\.com/maps/embed\?|maps\.google\.com/maps\?.*output=embed)#'],
            'facebook' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'youtube' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
            'url_registration' => 'required|url|max:255',
            'url_member_service' => 'required|url|max:255',
            'url_ejournal' => 'nullable|url|max:255',
            'bank_account_info' => 'nullable|string|max:1000',
            'logo' => 'nullable|' . Upload::IMAGE_RULE,
            'logo_footer' => 'nullable|' . Upload::IMAGE_RULE,
            'favicon' => 'nullable|image|mimes:png,ico,webp|max:512',
            'dues_amounts' => 'nullable|array',
            'dues_amounts.*' => 'nullable|numeric|min:0',
        ], ['maps_embed.regex' => 'Tempel URL "src" dari Google Maps → Bagikan → Sematkan peta (diawali https://www.google.com/maps/embed?).']);

        foreach (Setting::IMAGES as $field) {
            unset($data[$field]);
            if ($request->hasFile($field)) {
                $data[$field] = Upload::store($request->file($field), 'settings', $setting->{$field}, 600);
            }
        }
        $data['dues_amounts'] = collect(Member::TYPES)->keys()
            ->mapWithKeys(fn ($type) => [$type => filled($request->input("dues_amounts.{$type}")) ? (int) $request->input("dues_amounts.{$type}") : null])->all();

        $setting->update($data);

        return redirect()->route('setting.edit', $setting)->with('success', 'Pengaturan telah diubah.');
    }
}
