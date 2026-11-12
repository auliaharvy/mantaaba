<?php

namespace Tests\Feature\Skema;

use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** Unit kompetensi FND (12 jurus) & SIM dari template JSON; impor/ekspor JSON. */
class SkemaTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_fnd_and_sim_units_match_competency_standard()
    {
        $fnd = Skema::where('code', 'FND')->first()->load('modules');
        $this->assertSame('Fundamental (12 Jurus)', $fnd->name);
        $this->assertCount(12, $fnd->modules);
        $this->assertSame('MTB-F-0001', $fnd->modules->first()->code);
        $this->assertSame('Mendengarkan secara aktif, efektif dan efisien', $fnd->modules->first()->title);
        $this->assertSame(157, $fnd->modules->sum(fn ($m) => $m->criteriaCount()));
        $this->assertSame([20, 25, 30, 25], array_column($fnd->modules->first()->assessment, 'weight'));

        $sim = Skema::where('code', 'SIM')->first()->load('modules');
        $this->assertCount(19, $sim->modules);
        $this->assertSame(304, $sim->modules->sum(fn ($m) => $m->criteriaCount()), '76 elemen × 4 KUK');
        $this->assertSame(76, $sim->modules->sum(fn ($m) => count($m->elements)));
        $this->assertArrayHasKey('Aspek kritis', $sim->modules->first()->context);
        $this->assertCount(10, $sim->standard['final_portfolio']);
    }

    public function test_export_and_reimport_keeps_lessons_and_syncs_units()
    {
        $admin = User::where('email', 'superadmin@mail.com')->first();
        $fnd = Skema::where('code', 'FND')->first();
        $json = $this->actingAs($admin)->get(route('skema.export', $fnd))->assertOk()->assertHeader('content-disposition', 'attachment; filename="skema-FND.json"')->json();
        $this->assertCount(12, $json['units']);

        // ubah judul unit 1, hapus unit 12 (punya materi demo → dipertahankan), tambah unit 13
        $lessons = $fnd->modules()->where('code', 'MTB-F-0001')->first()->lessons()->count();
        $json['units'][0]['title'] = 'Mendengarkan (revisi)';
        array_pop($json['units']);
        $json['units'][] = ['code' => 'MTB-F-0013', 'title' => 'Unit baru', 'elements' => [['no' => '1', 'title' => 'Elemen', 'criteria' => [['no' => '1.1', 'text' => 'Kriteria']]]]];
        $file = UploadedFile::fake()->createWithContent('fnd.json', json_encode($json));
        $this->actingAs($admin)->post(route('skema.import', $fnd), ['file' => $file])->assertSessionHasNoErrors()->assertSessionHas('warning');

        $fnd->refresh();
        $this->assertSame('Mendengarkan (revisi)', $fnd->modules()->where('code', 'MTB-F-0001')->first()->title);
        $this->assertSame($lessons, $fnd->modules()->where('code', 'MTB-F-0001')->first()->lessons()->count(), 'materi tetap');
        $this->assertTrue($fnd->modules()->where('code', 'MTB-F-0013')->exists());
        $this->assertTrue($fnd->modules()->where('code', 'MTB-F-0012')->exists(), 'unit bermateri tidak dihapus');

        // kode skema harus cocok; elemen wajib; anggota tanpa hak → 403
        $json['code'] = 'SIM';
        $this->actingAs($admin)->post(route('skema.import', $fnd), ['file' => UploadedFile::fake()->createWithContent('x.json', json_encode($json))])->assertSessionHasErrors('code');
        $this->actingAs($admin)->post(route('skema.import', $fnd), ['file' => UploadedFile::fake()->createWithContent('x.json', '{bukan json')])->assertSessionHasErrors('file');
        $this->actingAs(User::where('email', 'anggota@mail.com')->first())->post(route('skema.import', $fnd), ['file' => UploadedFile::fake()->createWithContent('x.json', '{}')])->assertForbidden();

        // template kosong & skema baru lewat artisan
        $this->artisan('skema:import', ['file' => database_path('seeders/data/skema/TEMPLATE.json')])->assertSuccessful();
        $this->assertSame(['MTB-SIA-0001'], Skema::where('code', 'SIA')->first()->modules()->where('code', 'like', 'MTB-%')->pluck('code')->all());
    }

    public function test_pages_show_units_and_criteria()
    {
        $admin = User::where('email', 'superadmin@mail.com')->first();
        $sim = Skema::where('code', 'SIM')->first();
        $this->actingAs($admin)->get(route('skema.show', $sim))->assertOk()->assertSee('19 unit · 304 KUK')->assertSee('MTB-SIM-0019')
            ->assertSee('Kelompok calon pelanggan dan masalah nyata diidentifikasi')->assertSee('Paket portofolio akhir');
        $member = User::where('email', 'anggota@mail.com')->first();
        $fnd = Skema::where('code', 'FND')->first();
        $this->actingAs($member)->get(route('learn.skema', $fnd))->assertOk()->assertSee('MTB-F-0001')->assertSee('Elemen & KUK', false);
        $this->get(route('site.curriculum'))->assertOk()->assertSee('Membaca dalam melaksanakan operasional bisnis');
    }
}
