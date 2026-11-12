<?php

namespace Tests\Feature\Skema;

use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** Skema → unit kompetensi → modul (elemen + KUK) → materi, dari template JSON FND (12 jurus) & SIM; impor/ekspor JSON. */
class SkemaTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_units_become_competencies_and_elements_become_modules()
    {
        $fnd = Skema::where('code', 'FND')->first()->load('units', 'modules');
        $this->assertSame('Fundamental (12 Jurus)', $fnd->name);
        $this->assertCount(12, $fnd->units, '12 unit kompetensi');
        $this->assertSame('MTB-F-0001', $fnd->units->first()->code);
        $this->assertSame('Mendengarkan secara aktif, efektif dan efisien', $fnd->units->first()->title);
        $this->assertSame([20, 25, 30, 25], array_column($fnd->units->first()->assessment, 'weight'));
        $this->assertCount(48, $fnd->modules, 'modul = elemen');
        $first = $fnd->modules->first();
        $this->assertSame(['MTB-F-0001.1', 'Mempersiapkan proses penerimaan informasi lisan (audio / audio visual)'], [$first->code, $first->title]);
        $this->assertSame($fnd->units->first()->id, $first->competency_unit_id);
        $this->assertSame('1.1', $first->criteria[0]['no']);
        $this->assertSame(157, $fnd->criteriaCount());

        $sim = Skema::where('code', 'SIM')->first()->load('units', 'modules');
        $this->assertCount(19, $sim->units);
        $this->assertCount(76, $sim->modules);
        $this->assertSame(304, $sim->criteriaCount());
        $this->assertArrayHasKey('Aspek kritis', $sim->units->first()->context);
        $this->assertCount(10, $sim->standard['final_portfolio']);
    }

    public function test_export_and_reimport_keeps_lessons_and_syncs_units()
    {
        $admin = User::where('email', 'superadmin@mail.com')->first();
        $fnd = Skema::where('code', 'FND')->first();
        $json = $this->actingAs($admin)->get(route('skema.export', $fnd))->assertOk()->assertHeader('content-disposition', 'attachment; filename="skema-FND.json"')->json();
        $this->assertCount(12, $json['units']);
        $this->assertCount(4, $json['units'][0]['elements']);
        $template = json_decode(file_get_contents(database_path('seeders/data/skema/FND.json')), true);
        $this->assertSame($template['units'][0]['elements'], $json['units'][0]['elements'], 'ekspor = template');
        $this->assertEquals($template['units'][0]['character'], $json['units'][0]['character'], 'urutan kunci JSON diatur MySQL');
        $this->assertSame($template['description'], $json['description']);

        // ubah judul elemen 1 unit 1 (bermateri), hapus elemen 4 unit 1 (bermateri → dipertahankan), hapus unit 12, tambah unit 13
        $module = $fnd->modules()->where('code', 'MTB-F-0001.1')->first();
        $lessons = $module->lessons()->count();
        $this->assertGreaterThan(0, $lessons);
        $json['units'][0]['elements'][0]['title'] = 'Mempersiapkan komunikasi (revisi)';
        array_pop($json['units'][0]['elements']);
        array_pop($json['units']);
        $json['units'][] = ['code' => 'MTB-F-0013', 'title' => 'Unit baru', 'elements' => [['no' => '1', 'title' => 'Elemen', 'criteria' => [['no' => '1.1', 'text' => 'Kriteria']]]]];
        $file = UploadedFile::fake()->createWithContent('fnd.json', json_encode($json));
        $this->actingAs($admin)->post(route('skema.import', $fnd), ['file' => $file])->assertSessionHasNoErrors()->assertSessionHas('warning');

        $fnd->refresh();
        $this->assertSame('Mempersiapkan komunikasi (revisi)', $module->fresh()->title);
        $this->assertSame($lessons, $module->fresh()->lessons()->count(), 'materi tetap di modulnya');
        $this->assertTrue($fnd->modules()->where('code', 'MTB-F-0001.4')->exists(), 'modul bermateri tidak dihapus');
        $this->assertFalse($fnd->units()->where('code', 'MTB-F-0012')->exists(), 'unit dihapus');
        $this->assertFalse($fnd->modules()->where('code', 'like', 'MTB-F-0012.%')->exists(), 'modul tanpa materi ikut dihapus');
        $this->assertSame(1, $fnd->units()->where('code', 'MTB-F-0013')->first()->modules()->count());

        // kode skema harus cocok; JSON rusak; anggota tanpa hak → 403
        $json['code'] = 'SIM';
        $this->actingAs($admin)->post(route('skema.import', $fnd), ['file' => UploadedFile::fake()->createWithContent('x.json', json_encode($json))])->assertSessionHasErrors('code');
        $this->actingAs($admin)->post(route('skema.import', $fnd), ['file' => UploadedFile::fake()->createWithContent('x.json', '{bukan json')])->assertSessionHasErrors('file');
        $this->actingAs(User::where('email', 'anggota@mail.com')->first())->post(route('skema.import', $fnd), ['file' => UploadedFile::fake()->createWithContent('x.json', '{}')])->assertForbidden();

        // template kosong lewat artisan: skema SIA mendapat unit + modul elemen
        $this->artisan('skema:import', ['file' => database_path('seeders/data/skema/TEMPLATE.json')])->assertSuccessful();
        $sia = Skema::where('code', 'SIA')->first();
        $this->assertSame(['MTB-SIA-0001'], $sia->units()->pluck('code')->all());
        $this->assertTrue($sia->modules()->where('code', 'MTB-SIA-0001.1')->exists());
    }

    public function test_pages_show_units_and_criteria()
    {
        $admin = User::where('email', 'superadmin@mail.com')->first();
        $sim = Skema::where('code', 'SIM')->first();
        $this->actingAs($admin)->get(route('skema.show', $sim))->assertOk()->assertSee('19 unit · 76 modul · 304 KUK')->assertSee('MTB-SIM-0019 · ')->assertSee('MTB-SIM-0001.1 · Mengidentifikasi masalah dan peluang pelanggan')
            ->assertSee('Kelompok calon pelanggan dan masalah nyata diidentifikasi')->assertSee('Paket portofolio akhir');
        $member = User::where('email', 'anggota@mail.com')->first();
        $fnd = Skema::where('code', 'FND')->first();
        $this->actingAs($member)->get(route('learn.skema', $fnd))->assertOk()->assertSee('MTB-F-0001')->assertSee('Tentang unit ini')->assertSee('Kriteria unjuk kerja');
        // Kurikulum publik: deskripsi = tujuan utama skema; modul (elemen) menaut ke baris kompetensi (unit) beserta 'Asabiyah & Fastabiqul Khairat
        $sim->load('units');
        $this->assertStringStartsWith('Mampu memahami, merancang, menjalankan', $sim->description);
        $this->assertCount(19, $sim->units->filter(fn ($u) => ! empty($u->character['asabiyah']) && ! empty($u->character['fastabiqul']) && ! empty($u->character['technical'])));
        $this->get(route('site.curriculum'))->assertOk()
            ->assertSee('Mampu memahami, merancang, menjalankan')
            ->assertSee('Mengidentifikasi masalah dan peluang pelanggan')
            ->assertSee('href="#kompetensi-mtb-sim-0001"', false)->assertSee('id="kompetensi-mtb-sim-0001"', false)
            ->assertSee('Membaca dalam melaksanakan operasional bisnis')
            ->assertSee('Berlomba mendesain dan menerapkan workflow yang paling minim kesalahan');
    }

    /** Pemetaan: Tujuan Utama → Fungsi Kunci → Fungsi Utama → Fungsi Dasar (unit); PDF format dokumen skema. */
    public function test_function_mapping_and_pdf_download()
    {
        $sim = Skema::where('code', 'SIM')->first()->load('units', 'modules');
        $first = $sim->units->first();
        $this->assertSame(['Merumuskan arah usaha yang relevan dengan pelanggan dan manfaat sosial.', 'Fondasi dan Arah Perusahaan'], [$first->key_function, $first->main_function]);
        $groups = $sim->functionGroups();
        $this->assertCount(5, $groups, '5 fungsi kunci SIM');
        $this->assertSame([3, 3, 4, 6, 3], $groups->map(fn ($g) => $g[1]->count())->all());
        $this->assertSame('Mendengar', Skema::where('code', 'FND')->first()->units()->first()->main_function);

        $admin = User::where('email', 'superadmin@mail.com')->first();
        $pdf = $this->actingAs($admin)->get(route('skema.pdf', $sim))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->assertStringContainsString('Skema Kompetensi SIM.pdf', $pdf->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $this->get(route('site.curriculum.pdf', 'SIM'))->assertOk()->getContent());
        $sim->update(['is_public' => false]);
        $this->get(route('site.curriculum.pdf', 'SIM'))->assertNotFound();
        $this->actingAs($admin)->get(route('skema.show', $sim))->assertOk()->assertSee('Pemetaan standar kompetensi')->assertSee('Fondasi dan Arah Perusahaan');

        // ubah fungsi kunci/utama unit; anggota tanpa hak → 403
        $this->actingAs($admin)->patch(route('skema.unit-update', $first), ['title' => $first->title, 'key_function' => 'Fungsi kunci baru', 'main_function' => 'Fungsi utama baru'])->assertSessionHasNoErrors();
        $this->assertSame(['Fungsi kunci baru', 'Fungsi utama baru'], [$first->fresh()->key_function, $first->fresh()->main_function]);
        $this->actingAs(User::where('email', 'anggota@mail.com')->first())->patch(route('skema.unit-update', $first), ['title' => 'x'])->assertForbidden();
    }

    public function test_curriculum_page_shows_competencies_first_then_collapsible_modules()
    {
        $html = $this->get(route('site.curriculum'))->assertOk()->getContent();
        $sim = substr($html, strpos($html, 'id="sim"'));
        $this->assertLessThan(strpos($sim, 'Modul (76 elemen unit kompetensi)'), strpos($sim, 'id="kompetensi-mtb-sim-0001"'), 'tabel kompetensi sebelum modul');
        $this->assertStringContainsString('<details', $sim);
        $this->assertStringContainsString('Lihat selengkapnya (19 kompetensi)', $sim);
        // baris ke-11 dst disembunyikan sampai "lihat selengkapnya"
        $this->assertMatchesRegularExpression('/id="kompetensi-mtb-sim-0011"\s+x-show="all"/', $sim);
        $this->assertDoesNotMatchRegularExpression('/id="kompetensi-mtb-sim-0010"\s+x-show/', $sim);
        $this->assertStringContainsString(route('site.curriculum.pdf', 'SIM'), $sim);
    }
}
