<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Html;
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
        $hex = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
        $data = $request->validate([
            'site_name' => 'required|string|max:100',
            'foundation_name' => 'required|string|max:100',
            'tagline' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:300',
            'color_primary' => $hex,
            'color_secondary' => $hex,
            'color_accent' => $hex,
            'color_surface' => $hex,
            'address' => 'nullable|string|max:255',
            'village' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'regency' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'maps_embed' => ['nullable', 'string', 'max:2000', 'regex:#^https://(www\.google\.com/maps/embed\?|maps\.google\.com/maps\?.*output=embed)#'],
            'phone' => 'nullable|string|max:30',
            'whatsapp' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'office_hours' => 'nullable|string|max:255',
            'instagram' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'youtube' => 'nullable|url|max:255',
            'tiktok' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
            'bank_accounts' => 'nullable|array|max:3',
            'bank_accounts.*.bank' => 'nullable|required_with:bank_accounts.*.number|string|max:50',
            'bank_accounts.*.number' => 'nullable|required_with:bank_accounts.*.bank|string|max:50',
            'bank_accounts.*.holder' => 'nullable|required_with:bank_accounts.*.number|string|max:100',
            'donation_note' => 'nullable|string|max:2000',
            'admission_requirements' => 'nullable|string|max:20000',
            'priority_districts' => 'nullable|string|max:2000',
            'assessment_weights' => 'required|array',
            'assessment_weights.*' => 'required|integer|min:0|max:100',
            'default_pass_score' => 'required|integer|min:0|max:100',
            'min_character_score' => 'required|integer|min:0|max:100',
            'meal_budget_per_person_day' => 'nullable|numeric|min:0|max:10000000',
            'default_residents' => 'nullable|integer|min:0|max:1000',
            'accounting_start_date' => 'nullable|date',
            'report_signatories.ketua' => 'nullable|string|max:100',
            'report_signatories.bendahara' => 'nullable|string|max:100',
            'footer_text' => 'nullable|string|max:500',
            'logo' => 'nullable|' . Upload::IMAGE_RULE,
            'logo_footer' => 'nullable|' . Upload::IMAGE_RULE,
            'favicon' => 'nullable|image|mimes:png,webp|max:512',
            'og_image' => 'nullable|' . Upload::IMAGE_RULE,
            'qris_image' => 'nullable|' . Upload::IMAGE_RULE,
            'parent_consent_template' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'integrity_pact_template' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ], [
            'maps_embed.regex' => 'Tempel URL "src" dari Google Maps → Bagikan → Sematkan peta (diawali https://www.google.com/maps/embed?).',
            'regex' => 'Format warna #RRGGBB.',
        ]);

        $weights = array_intersect_key(array_map('intval', $data['assessment_weights']), Setting::DEFAULT_WEIGHTS);
        if (count($weights) !== 4 || array_sum($weights) !== 100) {
            return back()->withInput()->withErrors(['assessment_weights' => 'Bobot Knowledge, Skill, Character, dan Social Cohesion harus berjumlah 100%.']);
        }
        $data['assessment_weights'] = $weights;
        $data['bank_accounts'] = array_values(array_filter($data['bank_accounts'] ?? [], fn ($a) => filled($a['number'] ?? null)));
        $data['priority_districts'] = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($data['priority_districts'] ?? '')))));
        $data['admission_requirements'] = Html::clean($data['admission_requirements'] ?? null);
        $data['color_primary'] = strtoupper($data['color_primary']);

        foreach (Setting::IMAGES + array_fill_keys(Setting::TEMPLATES, null) as $field => $width) {
            unset($data[$field]);
            if ($request->hasFile($field)) {
                $data[$field] = $width
                    ? Upload::store($request->file($field), 'settings', $setting->{$field}, $width)
                    : tap($request->file($field)->store('settings', 'public'), fn () => Upload::delete($setting->{$field}));
            }
        }
        $setting->update($data);

        return redirect()->route('setting.edit', ['tab' => $request->input('_tab')])->with('success', 'Pengaturan telah diubah.');
    }
}
