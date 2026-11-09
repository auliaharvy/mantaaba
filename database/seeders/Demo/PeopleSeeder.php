<?php

namespace Database\Seeders\Demo;

use App\Models\BoardPeriod;
use App\Models\Division;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Organisasi, akun, anggota, iuran, dan kepengurusan demo. Semua orang fiktif, email di domain example.*. */
class PeopleSeeder extends Seeder
{
    /** Pejabat PD untuk dipakai seeder lain: ketua, sekretaris, diklat, humas (User). */
    public static array $officials = [];

    private array $data;

    private array $usedNames = [];

    private string $password;

    public function run(): void
    {
        $this->data = require __DIR__ . '/data/people.php';
        $this->password = Hash::make('password');

        $branches = $this->branches();
        $this->staff($branches);
        $members = $this->members($branches);
        $this->generalAccounts();
        $this->dues($members);
        $this->boards($branches, $members);
    }

    // ---------------------------------------------------------------- organisasi

    private function branches(): array
    {
        $pd = Organization::active();
        $pd->update(['address' => 'Ruko Graha Wiyata Blok B-7, Jl. Soekarno-Hatta, Kota Bandung', 'phone' => '(022) 8888-1234', 'email' => 'sekretariat@akp2i-jabar.example.com']);

        $branches = [];
        foreach ($this->data['branches'] as $code => [$city, $weight, $area, $streets]) {
            $branches[$code] = Organization::create([
                'parent_id' => $pd->id,
                'type' => 'pc',
                'name' => "PC AKP2I {$city}",
                'code' => "JABAR-{$code}",
                'city' => $city,
                'address' => DemoSeeder::pick($streets) . ' No. ' . mt_rand(3, 220) . ", {$city}",
                'phone' => "({$area}) " . mt_rand(500, 899) . '-' . mt_rand(1000, 9999),
                'email' => 'pc.' . Str::slug(str_replace(['Kota ', 'Kabupaten '], ['', 'kab '], $city), '') . '@akp2i-jabar.example.com',
                'profile' => $this->branchProfile($city),
            ]);
        }

        return $branches;
    }

    private function branchProfile(string $city): string
    {
        $focus = DemoSeeder::pick(['pendampingan UMKM', 'diskusi kasus bulanan', 'kelas persiapan USKP', 'kerja sama dengan perguruan tinggi setempat', 'klinik pajak bagi masyarakat']);

        return "Pengurus Cabang AKP2I {$city} menaungi konsultan pajak dan praktisi perpajakan yang berdomisili atau berpraktik di {$city} dan sekitarnya. "
            . "Cabang menjadi tempat anggota berkoordinasi, berbagi pengalaman praktik, dan mengikuti kegiatan pengembangan kompetensi.\n\n"
            . "Program unggulan cabang saat ini adalah {$focus}, di samping PPL yang diselenggarakan bersama Pengurus Daerah. "
            . "Pertemuan anggota rutin diadakan setiap bulan dan diumumkan melalui grup anggota serta website ini.";
    }

    private function branchGreeting(string $city): string
    {
        return "Assalamu'alaikum warahmatullahi wabarakatuh, salam sejahtera bagi kita semua.\n\n"
            . "Selamat datang di halaman Pengurus Cabang AKP2I {$city}. Kami mengajak seluruh konsultan pajak di {$city} untuk aktif dalam kegiatan cabang, "
            . "saling menguatkan sebagai sesama profesional, dan menjaga etika profesi dalam melayani wajib pajak.\n\n"
            . 'Pintu sekretariat cabang selalu terbuka untuk anggota maupun calon anggota yang ingin berdiskusi. Mari tumbuh bersama.';
    }

    // ---------------------------------------------------------------- akun

    private function staff(array $branches): void
    {
        $this->user('Rizky Ananda Putra', 'superadmin@example.com', 'L', 'super_administrator');
        $this->user('Nina Kartika Sari', 'sekretariat.pd@example.com', 'P', 'admin_pd');
        self::$officials['humas'] = $this->user('Gilang Ramadhan', 'humas@example.com', 'L', 'editor');
    }

    private function user(string $name, string $email, string $gender, string $role, array $extra = []): User
    {
        $this->usedNames[$name] = true;
        $faker = DemoSeeder::$faker;

        return User::create($extra + [
            'name' => $name,
            'email' => $email,
            'gender' => $gender,
            'birthplace' => DemoSeeder::pick(['Bandung', 'Bogor', 'Bekasi', 'Cirebon', 'Tasikmalaya', 'Garut', 'Jakarta', 'Medan', 'Padang', 'Yogyakarta']),
            'date_of_birth' => $faker->dateTimeBetween('-62 years', '-24 years')->format('Y-m-d'),
            'phone_number' => '08' . DemoSeeder::pick(['11', '12', '13', '21', '22', '52', '57', '77', '81', '95']) . '-' . mt_rand(1000, 9999) . '-' . mt_rand(1000, 9999),
            'password' => $this->password,
            'email_verified_at' => now(),
        ])->assignRole($role);
    }

    /** Nama unik realistis + email example.* yang sesuai nama. */
    private function person(): array
    {
        do {
            [, $male, $female, $surnames] = $this->data['name_groups'][$this->weighted(array_map(fn ($g) => $g[0], $this->data['name_groups']))];
            $gender = DemoSeeder::chance(55) ? 'L' : 'P';
            $first = DemoSeeder::pick($gender === 'L' ? $male : $female);
            $last = DemoSeeder::pick($surnames);
            $name = "{$first} {$last}";
        } while (isset($this->usedNames[$name]));
        $this->usedNames[$name] = true;
        $email = Str::slug("{$first}.{$last}", '.') . (DemoSeeder::chance(30) ? mt_rand(7, 99) : '') . '@' . DemoSeeder::pick(['example.com', 'example.org', 'example.net']);

        return [$name, $email, $gender, $last];
    }

    private function members(array $branches): array
    {
        $faker = DemoSeeder::$faker;
        $pool = [];
        foreach ($this->data['branches'] as $code => [$city, $weight]) {
            $pool = array_merge($pool, array_fill(0, $weight, $code));
        }
        // admin PC adalah anggota cabangnya
        $adminPc = ['BDG' => 'adminpc.bandung@example.com', 'BKS' => 'adminpc.bekasi@example.com', 'BGR' => 'adminpc.bogor@example.com'];

        $members = [];
        $number = 1000;
        for ($i = 0; $i < 150; $i++) {
            $code = $i < 3 ? array_keys($adminPc)[$i] : ($i === 3 ? 'BDG' : DemoSeeder::pick($pool));
            [$name, $email, $gender, $last] = $this->person();
            $role = 'anggota';
            if ($i < 3) {
                $email = $adminPc[$code];
            } elseif ($i === 3) {
                $email = 'anggota@example.com'; // akun demo anggota
            }

            $type = $i < 4 ? 'utama' : $this->weighted(['utama' => 45, 'madya' => 30, 'pratama' => 22, 'kehormatan' => 3]);
            $title = DemoSeeder::pick($this->data[$type === 'pratama' ? 'degrees_pratama' : 'degrees']) . ($type === 'utama' && DemoSeeder::chance(60) ? ', BKP' : '');
            $user = $this->user("{$name}, {$title}", $email, $gender, $role, [
                'address' => DemoSeeder::pick($this->data['branches'][$code][3]) . ' No. ' . mt_rand(1, 250) . ', ' . $this->data['branches'][$code][0],
                'organization_id' => $i < 3 ? $branches[$code]->id : null,
            ]);
            if ($i < 3) {
                $user->assignRole('admin_pc');
            }

            $joined = $faker->dateTimeBetween('-14 years', $i < 4 ? '-3 years' : '-2 months');
            $status = $i < 4 ? 'aktif' : $this->weighted(['aktif' => 93, 'skors' => 5, 'berhenti' => 2]);
            $members[] = Member::create([
                'user_id' => $user->id,
                'organization_id' => $branches[$code]->id,
                'member_number' => 'AKP2I-32.' . ($number += mt_rand(3, 41)),
                'kip_number' => $type === 'utama' ? 'KIP-' . mt_rand(1000, 9999) . '/' . DemoSeeder::pick(['A', 'B', 'C']) . '/' . $joined->format('Y') : null,
                'membership_type' => $type,
                'certification' => $type === 'utama' ? DemoSeeder::pick(['A', 'B', 'B', 'C']) : null,
                'brevet' => match ($type) { 'pratama' => ['A', 'B'], 'madya' => DemoSeeder::chance(60) ? ['A', 'B', 'C'] : ['A', 'B'], default => DemoSeeder::chance(50) ? ['A', 'B', 'C'] : null },
                'npwp' => sprintf('%02d.%03d.%03d.%d-%03d.000', mt_rand(1, 99), mt_rand(0, 999), mt_rand(0, 999), mt_rand(0, 9), mt_rand(400, 450)),
                'nik' => '32' . mt_rand(10, 79) . sprintf('%012d', mt_rand(0, 999999999999)),
                'office_name' => $type === 'pratama' && DemoSeeder::chance(50) ? null : sprintf(DemoSeeder::pick($this->data['office_patterns']), $last),
                'joined_at' => $joined->format('Y-m-d'),
                'sk_number' => sprintf('SK-%04d/AKP2I/%s', mt_rand(1, 9999), $joined->format('Y')),
                'status' => $status,
            ]);
            if ($status === 'berhenti') {
                end($members)->syncUserRole(); // berhenti -> role umum (hook model hanya jalan saat update)
            }
        }

        return $members;
    }

    private function generalAccounts(): void
    {
        for ($i = 0; $i < 25; $i++) {
            [$name, $email, $gender] = $this->person();
            $this->user($name, $i === 0 ? 'umum@example.com' : $email, $gender, 'umum', ['address' => DemoSeeder::pick(array_column($this->data['branches'], 0))]);
        }
    }

    // ---------------------------------------------------------------- iuran

    private function dues(array $members): void
    {
        $year = now()->year;
        $amounts = ['utama' => 1200000, 'madya' => 900000, 'pratama' => 600000, 'kehormatan' => 0];
        $recorder = User::role('admin_pd')->value('id');

        foreach ($members as $i => $member) {
            if ($member->status === 'berhenti') {
                continue;
            }
            $joinedYear = (int) $member->joined_at->format('Y');
            $profile = $i < 4 ? 'lunas' : $this->weighted(['lunas' => 79, 'belum' => 15, 'menunggak' => 6]);
            $years = match ($profile) {
                'lunas' => [$year - 1, $year],
                'belum' => [$year - 1],
                default => [$year - 2],
            };
            foreach (array_merge([$year - 3, $year - 2], $years) as $y) {
                if ($y < $joinedYear || $member->dues()->where('year', $y)->exists()) {
                    continue;
                }
                $paid = sprintf('%d-%02d-%02d', $y, mt_rand(1, 4), mt_rand(1, 27));
                $member->dues()->create([
                    'year' => $y,
                    'amount' => $amounts[$member->membership_type],
                    'paid_at' => $paid,
                    'recorded_by' => $recorder,
                    'notes' => $member->membership_type === 'kehormatan' ? 'Dibebaskan (anggota kehormatan)' : null,
                    'proof_file' => $y === $year && DemoSeeder::chance(25)
                        ? DemoImage::receipt(Str::before($member->user->name, ','), $amounts[$member->membership_type], \Carbon\Carbon::parse($paid)->translatedFormat('j F Y'), 'dues')
                        : null,
                ]);
            }
        }
    }

    // ---------------------------------------------------------------- kepengurusan

    private function boards(array $branches, array $members): void
    {
        $core = Position::whereNull('division_id')->pluck('id', 'name');
        $pdDivisions = [];
        foreach (['Organisasi & Keanggotaan', 'Pendidikan & Pelatihan', 'Humas & Publikasi', 'Advokasi & Hubungan Antarlembaga', 'Pengembangan Profesi'] as $i => $name) {
            $division = Division::create(['name' => "Bidang {$name}", 'level' => 'pd', 'sort_order' => $i + 1]);
            $pdDivisions[] = [$division, Position::create(['division_id' => $division->id, 'name' => 'Ketua Bidang', 'sort_order' => 1]),
                Position::create(['division_id' => $division->id, 'name' => 'Anggota Bidang', 'sort_order' => 2])];
        }
        $pcDivision = Division::create(['name' => 'Bidang Pendidikan & Pelatihan Cabang', 'level' => 'pc', 'sort_order' => 1]);
        $pcDivisionHead = Position::create(['division_id' => $pcDivision->id, 'name' => 'Ketua Bidang', 'sort_order' => 1]);

        // calon pengurus PD: anggota aktif jenis utama/madya, senior lebih dulu
        $eligible = collect($members)->filter(fn ($m) => $m->status === 'aktif' && in_array($m->membership_type, ['utama', 'madya'], true))
            ->sortBy('joined_at')->values();
        $take = function () use (&$eligible) {
            return $eligible->shift();
        };

        $pd = Organization::active();
        $old = BoardPeriod::create(['organization_id' => $pd->id, 'name' => '2019–2024', 'starts_at' => '2019-03-01', 'ends_at' => '2024-02-29', 'is_active' => false, 'sk_number' => 'SK-PP/017/2019']);
        $current = BoardPeriod::create(['organization_id' => $pd->id, 'name' => '2024–2029', 'starts_at' => '2024-03-01', 'ends_at' => '2029-02-28', 'is_active' => true, 'sk_number' => 'SK-PP/042/2024']);

        $assign = fn (BoardPeriod $period, Member $member, int $positionId, ?int $divisionId = null, int $order = 0) => $period->boardMembers()->create([
            'user_id' => $member->user_id, 'position_id' => $positionId, 'division_id' => $divisionId, 'sort_order' => $order,
        ]);

        foreach (['Ketua', 'Wakil Ketua', 'Sekretaris', 'Wakil Sekretaris', 'Bendahara', 'Wakil Bendahara'] as $title) {
            $member = $take();
            $assign($current, $member, $core[$title]);
            self::$officials[Str::slug($title)] = $member->user;
        }
        foreach ($pdDivisions as [$division, $head, $staffPosition]) {
            $leader = $take();
            $assign($current, $leader, $head->id, $division->id);
            if ($division->name === 'Bidang Pendidikan & Pelatihan') {
                self::$officials['diklat'] = $leader->user;
            }
            for ($k = 1; $k <= 2; $k++) {
                $assign($current, $take(), $staffPosition->id, $division->id, $k);
            }
        }
        // periode lama: sebagian pengurus lama masih menjabat, sisanya anggota lain
        foreach ($current->boardMembers()->with('position')->get()->take(6) as $i => $board) {
            $old->boardMembers()->create($board->only(['user_id', 'position_id', 'division_id', 'sort_order']));
        }

        // setiap PC: Ketua, Sekretaris, Bendahara (+ Ketua Bidang Diklat untuk PC besar); admin PC menjabat Sekretaris
        $adminPcUserIds = User::role('admin_pc')->pluck('id', 'organization_id');
        foreach ($branches as $code => $branch) {
            $period = BoardPeriod::create(['organization_id' => $branch->id, 'name' => '2025–2030', 'starts_at' => '2025-05-01', 'ends_at' => '2030-04-30',
                'is_active' => true, 'sk_number' => sprintf('SK-PD/%03d/%s/2025', mt_rand(10, 99), $code)]);
            $local = collect($members)->filter(fn ($m) => $m->organization_id === $branch->id && $m->status === 'aktif')
                ->reject(fn ($m) => $current->boardMembers()->where('user_id', $m->user_id)->exists())->values();
            $roles = ['Ketua' => $core['Ketua'], 'Sekretaris' => $core['Sekretaris'], 'Bendahara' => $core['Bendahara']];
            foreach ($roles as $title => $positionId) {
                $member = $title === 'Sekretaris' && isset($adminPcUserIds[$branch->id])
                    ? collect($members)->firstWhere('user_id', $adminPcUserIds[$branch->id])
                    : $local->reject(fn ($m) => $m->user_id === ($adminPcUserIds[$branch->id] ?? null))->shift();
                $local = $local->reject(fn ($m) => $m->user_id === $member?->user_id)->values();
                if ($member) {
                    $assign($period, $member, $positionId);
                }
            }
            if ($local->count() >= 6) {
                $assign($period, $local->shift(), $pcDivisionHead->id, $pcDivision->id);
            }
            $branch->update(['greeting' => $this->branchGreeting($branch->city)]);
        }
    }

    private function weighted(array $weights): string
    {
        $roll = mt_rand(1, array_sum($weights));
        foreach ($weights as $key => $weight) {
            if (($roll -= $weight) <= 0) {
                return $key;
            }
        }

        return array_key_first($weights);
    }
}
