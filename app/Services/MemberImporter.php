<?php

namespace App\Services;

use App\Http\Controllers\MembershipController;
use App\Http\Requests\MemberRequest;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Import CSV anggota (PRD 8.2): baris valid disimpan, baris gagal dilaporkan dengan nomor baris & alasan.
 * Kolom: nama,email,telepon,nomor_anggota,nomor_kip,jenis,kode_pc,sertifikasi,brevet,npwp,tanggal_bergabung
 */
class MemberImporter
{
    public function __construct(private MemberRegistrar $registrar)
    {
    }

    /** @return array{imported: int, failures: array<int, array{line: int, row: array, reason: string}>} */
    public function import(string $path, bool $sendActivation): array
    {
        $handle = fopen($path, 'r');
        $first = fgets($handle);
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ','; // Excel lokal Indonesia memakai ;
        $header = array_map(fn ($h) => Str::snake(trim(preg_replace('/^\xEF\xBB\xBF/', '', $h))), str_getcsv($first, $delimiter));

        $missing = array_diff(MembershipController::EXPORT_COLUMNS, $header);
        if ($missing) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'Kolom wajib tidak ada: ' . implode(', ', $missing) . '. Gunakan template.']);
        }

        $pcs = Organization::pc()->inActiveRegion()->whereNotNull('code')->pluck('id', 'code')->mapWithKeys(fn ($id, $code) => [strtoupper($code) => $id]);
        $passwordHash = Hash::make(Str::random(40)); // acak & tak diketahui siapa pun; anggota set password lewat aktivasi
        $seen = [];
        $imported = 0;
        $failures = [];
        $line = 1;

        while (($values = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if (count(array_filter($values, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // baris kosong
            }
            $row = array_combine($header, array_pad(array_slice($values, 0, count($header)), count($header), null));
            $data = $this->map($row, $pcs);

            $validator = Validator::make($data, MemberRequest::memberRules());
            $validator->after(function ($v) use ($row, $data, $pcs, &$seen) {
                if (filled($row['kode_pc']) && ! $data['organization_id']) {
                    $v->errors()->add('kode_pc', "Kode PC \"{$row['kode_pc']}\" tidak dikenal.");
                }
                if (isset($seen[$data['member_number']])) {
                    $v->errors()->add('member_number', "Nomor anggota duplikat dengan baris {$seen[$data['member_number']]}.");
                }
            });

            try {
                $valid = $validator->validate();
                $this->registrar->register(
                    array_intersect_key($valid, array_flip(MemberRequest::userFields())),
                    array_diff_key($valid, array_flip(MemberRequest::userFields())),
                    $sendActivation,
                    $passwordHash,
                );
                $seen[$data['member_number']] = $line;
                $imported++;
            } catch (ValidationException $e) {
                $failures[] = ['line' => $line, 'row' => $row, 'reason' => implode(' ', $e->validator->errors()->all())];
            }
        }
        fclose($handle);

        return ['imported' => $imported, 'failures' => $failures];
    }

    private function map(array $row, $pcs): array
    {
        $date = trim((string) $row['tanggal_bergabung']);

        return [
            'name' => trim((string) $row['nama']),
            'email' => strtolower(trim((string) $row['email'])),
            'phone_number' => trim((string) $row['telepon']) ?: null,
            'organization_id' => $pcs[strtoupper(trim((string) $row['kode_pc']))] ?? null,
            'member_number' => trim((string) $row['nomor_anggota']),
            'kip_number' => trim((string) $row['nomor_kip']) ?: null,
            'membership_type' => strtolower(trim((string) $row['jenis'])),
            'certification' => strtoupper(trim((string) $row['sertifikasi'])) ?: null,
            'brevet' => array_values(array_filter(preg_split('/[\s,\/]+/', strtoupper((string) $row['brevet'])))) ?: null,
            'npwp' => trim((string) $row['npwp']) ?: null,
            'joined_at' => $date === '' ? null : $this->parseDate($date),
            'status' => 'aktif',
        ];
    }

    /** Terima YYYY-MM-DD atau DD/MM/YYYY; selain itu biarkan validator menolaknya. */
    private function parseDate(string $date): string
    {
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat("!{$format}", $date);
            } catch (\Throwable) {
                continue;
            }
            if ($parsed->format($format) === $date) {
                return $parsed->toDateString();
            }
        }

        return $date;
    }
}
