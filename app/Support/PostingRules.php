<?php

namespace App\Support;

use App\Models\CoaAccount;
use App\Models\Setting;

/** Akun aturan posting (PRD 9.16): pilihan Setting Akuntansi, bila kosong default config('mantaaba.posting_rules'). */
class PostingRules
{
    public static function code(string $key): string
    {
        return Setting::current()->posting_accounts[$key] ?? config("mantaaba.posting_rules.{$key}");
    }

    public static function account(string $key): CoaAccount
    {
        return CoaAccount::byCode(self::code($key));
    }
}
