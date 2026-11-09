<?php

namespace Tests\Feature\M1;

use App\Http\Controllers\MembershipController;
use App\Models\Member;
use App\Notifications\MemberActivation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/** PRD 8.2: import CSV */
class MemberImportTest extends M1TestCase
{
    private function csv(array $rows, string $delimiter = ','): UploadedFile
    {
        $out = fopen('php://temp', 'w+');
        fputcsv($out, MembershipController::EXPORT_COLUMNS, $delimiter);
        foreach ($rows as $row) {
            fputcsv($out, $row, $delimiter);
        }
        rewind($out);

        return UploadedFile::fake()->createWithContent('anggota.csv', stream_get_contents($out));
    }

    private function row(int $i, array $override = []): array
    {
        return array_values(array_merge([
            'nama' => "Anggota {$i}", 'email' => "import{$i}@contoh.id", 'telepon' => '0812', 'nomor_anggota' => sprintf('IMP-%05d', $i),
            'nomor_kip' => '', 'jenis' => 'pratama', 'kode_pc' => $this->ownPc()->code, 'sertifikasi' => '', 'brevet' => 'A,B',
            'npwp' => '', 'tanggal_bergabung' => '15/01/2020',
        ], $override));
    }

    public function test_500_rows_with_3_invalid_imports_497_and_reports_3_with_line_numbers()
    {
        Notification::fake();
        Storage::fake('local');
        $rows = [];
        for ($i = 1; $i <= 500; $i++) {
            $rows[] = match ($i) {
                10 => $this->row($i, ['email' => 'bukan-email']),
                250 => $this->row($i, ['kode_pc' => 'TIDAK-ADA']),
                400 => $this->row($i, ['nomor_anggota' => sprintf('IMP-%05d', 1)]), // duplikat baris 2
                default => $this->row($i),
            };
        }
        $before = Member::count();

        $response = $this->actingAs($this->adminPd())->post(route('membership.import.store'), ['file' => $this->csv($rows)]);

        $response->assertRedirect(route('membership.import.form'));
        $this->assertSame($before + 497, Member::count());
        $this->assertSame(3, session('import_failure_count'));
        $this->assertSame([11, 251, 401], array_column(session('import_failures'), 'line')); // baris 1 = header
        $this->assertStringContainsString('Kode PC', session('import_failures')[1]['reason']);
        $this->assertStringContainsString('duplikat', session('import_failures')[2]['reason']);

        $member = Member::where('member_number', 'IMP-00002')->firstOrFail();
        $this->assertSame(['A', 'B'], $member->brevet);
        $this->assertSame('2020-01-15', $member->joined_at->toDateString());
        Notification::assertSentTo($member->user, MemberActivation::class);

        $this->actingAs($this->adminPd())->get(route('membership.import.failures', session('import_report')))
            ->assertOk()->assertDownload('baris-gagal-import-anggota.csv');
    }

    public function test_semicolon_csv_and_send_later_option()
    {
        Notification::fake();

        $this->actingAs($this->adminPd())->post(route('membership.import.store'), [
            'file' => $this->csv([$this->row(1), $this->row(2)], ';'),
            'send_later' => 1,
        ])->assertSessionHas('success');

        $this->assertSame(2, Member::where('member_number', 'like', 'IMP-%')->count());
        Notification::assertNothingSent();
    }

    public function test_missing_columns_rejected_and_admin_pc_forbidden()
    {
        $bad = UploadedFile::fake()->createWithContent('x.csv', "nama,email\nA,a@b.c\n");

        $this->actingAs($this->adminPd())->post(route('membership.import.store'), ['file' => $bad])->assertSessionHasErrors('file');
        $this->actingAs($this->adminPc())->get(route('membership.import.form'))->assertForbidden();
        $this->actingAs($this->adminPd())->get(route('membership.import.template'))->assertOk();
    }
}
