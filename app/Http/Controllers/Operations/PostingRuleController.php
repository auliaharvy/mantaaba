<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\CoaAccount;
use App\Models\Setting;
use App\Support\PostingRules;
use Illuminate\Http\Request;

/** Setting Akuntansi (PRD 9.16): pilih akun per aturan posting otomatis. Hanya Super Admin. */
class PostingRuleController extends Controller
{
    public function edit(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $rules = collect(config('mantaaba.posting_rule_labels'))->map(fn ($label, $key) => ['label' => $label, 'code' => PostingRules::code($key), 'default' => config("mantaaba.posting_rules.{$key}")]);

        return view('dashboard.setting.posting', ['rules' => $rules, 'accounts' => CoaAccount::postable()->get()]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $keys = array_keys(config('mantaaba.posting_rule_labels'));
        $codes = CoaAccount::postable()->pluck('code')->all();
        $data = $request->validate(['rules' => 'required|array', 'rules.*' => ['required', 'in:' . implode(',', $codes)]]);
        // kunci form memakai "__" karena titik dibaca validator sebagai bertingkat
        $overrides = collect($data['rules'])->mapWithKeys(fn ($code, $key) => [str_replace('__', '.', $key) => $code])->only($keys)->filter(fn ($code, $key) => $code !== config("mantaaba.posting_rules.{$key}"))->all();
        Setting::current()->update(['posting_accounts' => $overrides ?: null]);

        return back()->with('success', 'Aturan posting disimpan.');
    }
}
