<?php

namespace App\Http\Requests;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi anggota (PRD 8.2); aturan yang sama dipakai import CSV. */
class MemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi lewat MemberPolicy (authorizeResource)
    }

    public function rules(): array
    {
        return self::memberRules($this->route('membership'));
    }

    public static function memberRules(?Member $member = null): array
    {
        return [
            'name' => 'required|string|max:255',
            // anggota baru boleh memakai email akun umum yang sudah ada (dinaikkan, PRD 8.2; dicek MemberRegistrar)
            'email' => array_filter(['required', 'email', 'max:255', $member ? Rule::unique('users', 'email')->ignore($member->user_id) : null]),
            'phone_number' => 'nullable|string|max:30',
            'gender' => 'nullable|in:L,P',
            'birthplace' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string|max:1000',
            'organization_id' => ['required', Rule::exists('organizations', 'id')->where('type', 'pc')->whereNull('deleted_at')],
            'member_number' => ['required', 'string', 'max:50', Rule::unique('members', 'member_number')->ignore($member)],
            'kip_number' => 'nullable|string|max:50',
            'membership_type' => ['required', Rule::in(array_keys(Member::TYPES))],
            'certification' => ['nullable', Rule::in(array_keys(Member::CERTIFICATIONS))],
            'brevet' => 'nullable|array',
            'brevet.*' => 'in:A,B,C',
            'npwp' => 'nullable|string|max:30',
            'nik' => 'nullable|digits:16',
            'office_name' => 'nullable|string|max:255',
            'joined_at' => 'nullable|date',
            'sk_number' => 'nullable|string|max:100',
            'status' => ['required', Rule::in(array_keys(Member::STATUSES))],
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public static function userFields(): array
    {
        return ['name', 'email', 'phone_number', 'gender', 'birthplace', 'date_of_birth', 'address'];
    }
}
