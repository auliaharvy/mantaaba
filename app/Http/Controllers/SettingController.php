<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Upload;
use Illuminate\Http\Request;

/** Setting situs (PRD 9.1.1), satu baris. */
class SettingController extends Controller
{
    public function edit()
    {
        $setting = Setting::current();
        $this->authorize('update', $setting);

        return view('dashboard.setting.edit', compact('setting'));
    }

    public function update(Request $request)
    {
        $setting = Setting::first() ?? Setting::current();
        $this->authorize('update', $setting);
        $data = $request->validate([
            'site_name' => 'required|string|max:100',
            'tagline' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'whatsapp' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'maps_embed' => ['nullable', 'string', 'max:2000', 'regex:#^https://(www\.google\.com/maps/embed\?|maps\.google\.com/maps\?.*output=embed)#'],
            'facebook' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'youtube' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
            'bank_account_info' => 'nullable|string|max:1000',
            'logo' => 'nullable|' . Upload::IMAGE_RULE,
            'logo_footer' => 'nullable|' . Upload::IMAGE_RULE,
            'favicon' => 'nullable|image|mimes:png,ico,webp|max:512',
        ], ['maps_embed.regex' => 'Tempel URL "src" dari Google Maps → Bagikan → Sematkan peta (diawali https://www.google.com/maps/embed?).']);

        foreach (Setting::IMAGES as $field) {
            unset($data[$field]);
            if ($request->hasFile($field)) {
                $data[$field] = Upload::store($request->file($field), 'settings', $setting->{$field}, 600);
            }
        }
        $setting->update($data);

        return redirect()->route('setting.edit')->with('success', 'Pengaturan telah diubah.');
    }
}
