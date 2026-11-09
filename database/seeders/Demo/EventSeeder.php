<?php

namespace Database\Seeders\Demo;

use App\Jobs\IssueEventCertificates;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Event demo: selesai (dengan kehadiran & sertifikat), mendatang (pendaftar campuran), ditutup, dan draf. */
class EventSeeder extends Seeder
{
    public function run(): void
    {
        $pd = Organization::active();
        $pc = fn (string $code) => Organization::where('code', "JABAR-{$code}")->value('id');
        $template = DemoImage::certificateTemplate('event/certificates');
        $o = PeopleSeeder::$officials;
        $signatories = [
            ['name' => Str::before($o['ketua']->name, ','), 'title' => 'Ketua PD AKP2I Jawa Barat', 'signature' => null],
            ['name' => Str::before($o['diklat']->name, ','), 'title' => 'Ketua Bidang Pendidikan & Pelatihan', 'signature' => null],
        ];

        // [judul, jenis, mode, hari dari sekarang, durasi jam, org, biaya anggota, biaya umum, kuota, jam SKP, audiens, status, jumlah pendaftar, foto, lokasi]
        $events = [
            ['PPL Implementasi Coretax bagi Konsultan Pajak', 'ppl', 'offline', -75, 8, $pd->id, 350000, 750000, 150, 8, 'publik', 'done', 64, 'seminar-room-a', 'Hotel Mitra, Kota Bandung'],
            ['Brevet Pajak A & B Angkatan II', 'brevet_a', 'offline', -95, 8, $pd->id, 3500000, 4500000, 40, null, 'publik', 'done', 34, 'classroom-desks', 'Sekretariat PD, Kota Bandung'],
            ['Seminar Studi Kasus PPh Pasal 21', 'seminar', 'hybrid', -50, 4, $pd->id, 0, 150000, 120, 4, 'publik', 'done', 48, 'hall-chairs', 'Aula Graha Wiyata, Kota Bandung'],
            ['Webinar Rekonsiliasi PPN Setiap Masa Pajak', 'webinar', 'online', -30, 2, $pd->id, 0, 0, null, 2, 'publik', 'done', 72, 'workspace-bright', null],
            ['PPL Cabang: Pendampingan Pemeriksaan & Keberatan', 'ppl', 'offline', -20, 4, $pc('BKS'), 250000, 500000, 60, 4, 'publik', 'done', 30, 'meeting-room-banquet', 'Gedung Serbaguna, Kota Bekasi'],
            ['Webinar Gratis: Persiapan SPT Tahunan Orang Pribadi', 'webinar', 'online', 10, 2, $pd->id, 0, 0, null, 2, 'publik', 'open', 56, 'computer-lab', null],
            ['Diskusi Anggota: Etika Profesi di Era Digital', 'seminar', 'offline', 14, 3, $pc('BDG'), 0, 0, 50, 3, 'anggota', 'open', 21, 'boardroom-white', 'Sekretariat PC, Kota Bandung'],
            ['Brevet Pajak A & B Angkatan III', 'brevet_a', 'offline', 20, 8, $pd->id, 3500000, 4500000, 40, null, 'publik', 'open', 27, 'classroom-hill', 'Sekretariat PD, Kota Bandung'],
            ['PPL Akhir Tahun: Evaluasi Kepatuhan & Persiapan SPT Tahunan', 'ppl', 'offline', 35, 8, $pd->id, 400000, 850000, 120, 8, 'publik', 'open', 41, 'ballroom', 'Hotel Mitra, Kota Bandung'],
            ['Seminar Perpajakan Jawa Barat 2026', 'seminar', 'offline', 5, 6, $pd->id, 150000, 300000, 60, 6, 'publik', 'closed', 60, 'auditorium-curtain', 'Auditorium Kampus Mitra, Kota Bandung'],
            ['PPL Triwulan I 2027', 'ppl', 'offline', 100, 8, $pd->id, 400000, 850000, 120, 8, 'publik', 'draft', 0, 'seminar-room-c', 'Akan diumumkan'],
        ];

        $members = User::role('anggota')->whereHas('member', fn ($q) => $q->whereIn('status', ['aktif', 'skors']))->with('member')->get();
        $general = User::role('umum')->get();
        $adminPd = User::role('admin_pd')->value('id');

        foreach ($events as [$title, $type, $mode, $days, $hours, $orgId, $feeMember, $feePublic, $quota, $skp, $audience, $status, $count, $photo, $location]) {
            $start = now()->addDays($days)->setTime($hours >= 6 ? 8 : 13, 0);
            $event = Event::create([
                'organization_id' => $orgId, 'title' => $title, 'type' => $type, 'mode' => $mode, 'location' => $location,
                'code' => strtoupper(Str::before(str_replace('brevet_a', 'BRV', $type), '_')) . $start->format('ym') . ($codeSeq = ($codeSeq ?? 0) + 1),
                'meeting_url' => $mode === 'offline' ? null : 'https://zoom.us/j/' . mt_rand(80000000000, 89999999999),
                'body' => $this->body($title, $type, $hours, $skp, $o),
                'starts_at' => $start, 'ends_at' => $type === 'brevet_a' ? $start->copy()->addWeeks(6)->setTime(16, 0) : $start->copy()->addHours($hours),
                'registration_opens_at' => $start->copy()->subDays(45), 'registration_closes_at' => $start->copy()->subDay(),
                'quota' => $quota, 'audience' => $audience, 'fee_member' => $feeMember, 'fee_public' => $feePublic, 'skp_hours' => $skp, 'status' => $status,
                'thumbnail' => DemoImage::poster($photo, \App\Models\Event::TYPES[$type], $title,
                    $start->translatedFormat('l, j F Y') . ' · ' . ($location ? Str::before($location, ',') : 'Online') . ($skp ? " · {$skp} Jam SKP" : ''), 'event'),
                'certificate_template' => $template, 'certificate_signatory' => $signatories,
            ]);

            // calon peserta: Brevet & webinar banyak umum; PPL/seminar didominasi anggota
            $publicShare = match ($type) { 'brevet_a' => 75, 'webinar' => 40, default => $audience === 'anggota' ? 0 : 15 };
            $people = $members->shuffle()->take((int) ceil($count * (100 - $publicShare) / 100))
                ->merge($general->shuffle()->take((int) floor($count * $publicShare / 100)))->take($count);
            foreach ($people as $user) {
                $this->participant($event, $user, $status, $adminPd);
            }
        }

        if (DemoSeeder::$withCertificatePdfs) {
            Event::where('status', 'done')->each(fn ($event) => (new IssueEventCertificates($event))->handle());
        }
    }

    private function participant(Event $event, User $user, string $eventStatus, int $adminPd): void
    {
        $amount = $event->feeFor($user);
        $paid = (float) $amount > 0;
        $done = $eventStatus === 'done';
        // event selesai/ditutup: hampir semua terverifikasi; event dibuka: berbayar banyak yang masih pending
        $status = $done || $eventStatus === 'closed' || ! $paid
            ? (DemoSeeder::chance(4) ? 'cancelled' : 'verified')
            : DemoSeeder::pick(['pending', 'pending', 'verified', 'verified', 'verified', 'rejected']);
        $registered = $event->registration_opens_at->copy()->addDays(mt_rand(0, 40))->min($event->starts_at->copy()->subDay());

        $participant = Participant::create([
            'event_id' => $event->id, 'user_id' => $user->id, 'status' => $status, 'amount' => $amount,
            'payment_proof' => $paid && $status !== 'cancelled' && DemoSeeder::chance(90)
                ? DemoImage::receipt(Str::before($user->name, ','), (int) $amount, $registered->translatedFormat('j F Y'), 'payment-proofs') : null,
            'verified_by' => $status === 'verified' ? $adminPd : null,
            'verified_at' => $status === 'verified' ? $registered->copy()->addDay() : null,
            'attended_at' => $done && $status === 'verified' && DemoSeeder::chance(86) ? $event->starts_at->copy()->addMinutes(mt_rand(-30, 45)) : null,
            'notes' => $status === 'rejected' ? 'Bukti transfer tidak terbaca, silakan unggah ulang.' : null,
        ]);
        $participant->forceFill(['created_at' => $registered, 'updated_at' => $registered])->saveQuietly();
    }

    private function body(string $title, string $type, int $hours, $skp, array $o): string
    {
        $speaker = Str::before($o['diklat']->name, ',');
        $topics = match ($type) {
            'brevet_a' => ['Ketentuan Umum dan Tata Cara Perpajakan', 'PPh Orang Pribadi dan Badan', 'PPN dan PPnBM', 'Praktik pengisian SPT', 'Simulasi kasus dan ujian akhir'],
            'webinar' => ['Pembaruan ketentuan terkini', 'Contoh kasus', 'Tanya jawab interaktif'],
            default => ['Pemaparan materi oleh narasumber praktisi', 'Diskusi studi kasus', 'Tanya jawab dan berbagi pengalaman praktik'],
        };

        return '<p>' . e($title) . ' diselenggarakan oleh PD AKP2I Jawa Barat sebagai bagian dari program pengembangan kompetensi anggota dan masyarakat.</p>'
            . '<h3>Materi</h3><ul><li>' . implode('</li><li>', $topics) . '</li></ul>'
            . '<h3>Narasumber</h3><p>' . e($speaker) . ' dan praktisi perpajakan anggota AKP2I.</p>'
            . '<h3>Fasilitas</h3><ul><li>Materi digital</li>' . ($skp ? "<li>Sertifikat {$skp} jam SKP</li>" : '<li>Sertifikat kelulusan</li>')
            . ($type === 'webinar' ? '' : '<li>Konsumsi</li>') . '</ul>';
    }
}
