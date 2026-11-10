<?php

namespace Tests\Feature\M4;

use App\Models\CoachingNote;
use App\Models\Duty;
use App\Models\DutyArea;
use App\Models\Member;
use App\Models\Room;
use App\Models\User;
use App\Services\DutyGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Asrama & jadwal piket (PRD 9.9, AC M4). */
class DormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_room_capacity_and_gender_are_hard_stops()
    {
        $pengurus = $this->user('pengurus@mail.com');
        $room = Room::create(['code' => 'C-1', 'name' => 'Kecil', 'capacity' => 1, 'gender' => 'L']);
        $putri = Room::where('gender', 'P')->firstOrFail();
        [$a, $b] = Member::where('category', 'siswa')->whereNull('room_id')->take(2)->get()->pad(2, null)->all();
        $b ??= Member::where('category', 'siswa')->where('id', '!=', $a->id)->first();

        $this->actingAs($pengurus)->post(route('room.assign', $putri), ['member_id' => $a->id])->assertSessionHasErrors(['member_id' => 'Kamar B-1 untuk putri.']);
        $this->actingAs($pengurus)->post(route('room.assign', $room), ['member_id' => $a->id])->assertSessionHasNoErrors();
        $this->actingAs($pengurus)->post(route('room.assign', $room), ['member_id' => $b->id])->assertSessionHasErrors(['member_id' => 'Kamar C-1 penuh (1 orang).']);
        $this->assertSame($room->id, $a->fresh()->room_id);

        $a->update(['gender' => null, 'room_id' => null]);
        $this->actingAs($pengurus)->post(route('room.assign', $room), ['member_id' => $a->id])->assertSessionHasErrors('member_id');
        $this->actingAs($pengurus)->patch(route('room.update', Room::where('code', 'A-1')->first()), ['code' => 'A-1', 'name' => 'X', 'capacity' => 1, 'gender' => 'L'])->assertSessionHasErrors('capacity');
        $this->actingAs($this->user('anggota@mail.com'))->get(route('room.index'))->assertForbidden();
        $this->actingAs($pengurus)->get(route('room.index'))->assertOk()->assertSee('Kamar Al-Fatih');
    }

    public function test_generator_rotates_and_never_assigns_twice_a_day()
    {
        Duty::query()->delete();
        $members = Member::where('category', 'siswa')->pluck('id')->all(); // 3 siswa
        $kebun = DutyArea::where('name', 'Kebun')->firstOrFail();
        $dapur = DutyArea::where('name', 'Dapur')->firstOrFail();
        $from = Carbon::parse('2026-12-01');

        $result = app(DutyGenerator::class)->generate($dapur, $from, $from->copy()->addDays(2), ['pagi', 'sore'], $members, 1);
        $this->assertSame(6, $result['created']);
        // area kedua di hari yang sama: hanya 1 orang tersisa per hari → slot sore tidak terisi
        $result = app(DutyGenerator::class)->generate($kebun, $from, $from->copy()->addDays(2), ['pagi', 'sore'], $members, 1);
        $this->assertSame(3, $result['created']);
        $this->assertCount(3, $result['short']);

        $perDay = Duty::selectRaw('date, member_id, count(*) as n')->groupBy('date', 'member_id')->get();
        $this->assertTrue($perDay->every(fn ($r) => $r->n === 1), 'tidak ada anggota dua kali sehari');
        $counts = Duty::selectRaw('member_id, count(*) as n')->groupBy('member_id')->pluck('n')->all();
        $this->assertSame([3, 3, 3], array_values($counts), 'rotasi merata');

        // lewat form dashboard + validasi
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('duty.generate'), ['duty_area_id' => $kebun->id, 'from' => '2026-12-10', 'to' => '2026-12-11', 'shifts' => ['pagi'], 'per_shift' => 1, 'member_ids' => []])
            ->assertSessionHasErrors('member_ids');
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('duty.generate'), ['duty_area_id' => $kebun->id, 'from' => '2026-12-10', 'to' => '2026-12-11', 'shifts' => ['pagi'], 'per_shift' => 1, 'member_ids' => $members])
            ->assertSessionHas('success', '2 jadwal piket dibuat.');
    }

    public function test_swap_and_reassign_respect_one_duty_per_day()
    {
        Duty::query()->delete();
        $area = DutyArea::first();
        [$a, $b, $c] = Member::where('category', 'siswa')->take(3)->get()->all();
        $d1 = Duty::create(['duty_area_id' => $area->id, 'date' => '2026-12-01', 'shift' => 'pagi', 'member_id' => $a->id]);
        $d2 = Duty::create(['duty_area_id' => $area->id, 'date' => '2026-12-02', 'shift' => 'pagi', 'member_id' => $b->id]);
        $d3 = Duty::create(['duty_area_id' => $area->id, 'date' => '2026-12-02', 'shift' => 'sore', 'member_id' => $c->id]);
        $pengurus = $this->user('pengurus@mail.com');

        $this->actingAs($pengurus)->post(route('duty.swap'), ['first' => $d1->id, 'second' => $d2->id])->assertSessionHasNoErrors();
        $this->assertSame([$b->id, $a->id], [$d1->fresh()->member_id, $d2->fresh()->member_id]);
        $this->actingAs($pengurus)->patch(route('duty.update', $d3), ['member_id' => $a->id])->assertSessionHasErrors('member_id'); // a sudah piket 2 Des
    }

    public function test_member_completes_checklist_and_pengurus_verifies()
    {
        Storage::fake('local');
        $member = $this->user('anggota@mail.com')->member;
        $area = DutyArea::where('name', 'Dapur')->firstOrFail();
        Duty::where('member_id', $member->id)->whereDate('date', today())->delete();
        $duty = Duty::create(['duty_area_id' => $area->id, 'date' => today(), 'shift' => 'siang', 'member_id' => $member->id]);

        $this->actingAs($member->user)->get(route('overview.show', 'saya'))->assertSee('Piket hari ini')->assertSee('Dapur');
        $this->actingAs($member->user)->post(route('my-duty.complete', $duty), ['checklist' => [$area->checklist[0]]])->assertSessionHasErrors('checklist');
        $this->actingAs($member->user)->post(route('my-duty.complete', $duty), ['checklist' => $area->checklist, 'photo' => UploadedFile::fake()->image('dapur.jpg')])->assertSessionHas('success');
        $this->assertSame('selesai', $duty->fresh()->status);
        Storage::disk('local')->assertExists($duty->fresh()->photo);

        $other = User::factory()->create()->assignRole('anggota');
        $this->actingAs($other)->post(route('my-duty.complete', $duty))->assertForbidden();
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('duty.verify', $duty))->assertSessionHas('success');
        $this->assertSame('diverifikasi', $duty->fresh()->status);
    }

    public function test_missed_duties_become_coaching_notes_and_feed_reports()
    {
        $member = Member::where('name', 'Fajar Nugraha')->firstOrFail();
        Duty::where('member_id', $member->id)->delete();
        $area = DutyArea::where('name', 'Kolam')->firstOrFail();
        $done = Duty::create(['duty_area_id' => $area->id, 'date' => today()->subDay(), 'shift' => 'pagi', 'member_id' => $member->id, 'status' => 'selesai']);
        $missed = Duty::create(['duty_area_id' => $area->id, 'date' => today(), 'shift' => 'pagi', 'member_id' => $member->id]);

        $this->artisan('duties:mark-missed')->assertSuccessful();
        $this->assertSame('terlewat', $missed->fresh()->status);
        $this->assertSame('selesai', $done->fresh()->status);
        $note = CoachingNote::where('member_id', $member->id)->whereNull('author_id')->firstOrFail();
        $this->assertSame(['perhatian', 'tanggung_jawab'], [$note->type, $note->dimension]);
        $this->assertStringContainsString('Kolam', $note->body);

        $this->assertSame(50, Duty::completionRate($member->id, today()->subDays(2), today()));
        $this->actingAs($this->user('pengurus@mail.com'))->get(route('duty.report'))->assertOk()->assertSee('Fajar Nugraha')->assertSee('50%');
        $this->actingAs($this->user('pengurus@mail.com'))->get(route('member.show', [$member, 'tab' => 'piket']))->assertSee('Terlewat');
    }

    public function test_leaving_member_loses_room_and_future_duties()
    {
        $member = Member::whereNotNull('room_id')->firstOrFail();
        $future = Duty::create(['duty_area_id' => DutyArea::first()->id, 'date' => today()->addDays(20), 'shift' => 'malam', 'member_id' => $member->id]);
        $member->changeStatus('mengundurkan_diri', 'Pindah kota');
        $this->assertNull($member->fresh()->room_id);
        $this->assertModelMissing($future);
    }
}
