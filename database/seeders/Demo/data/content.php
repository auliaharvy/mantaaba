<?php

// Konten DEMO. Topik perpajakan ditulis umum (tanpa angka/nomor regulasi) agar tidak menyesatkan.
// {ketua}, {sekretaris}, {diklat} diganti nama pengurus demo saat seeding.
$p = fn (string ...$paragraphs) => '<p>' . implode('</p><p>', $paragraphs) . '</p>';

return [
    'pages' => [
        'profil' => ['Profil PD AKP2I Jawa Barat', 'Mengenal Pengurus Daerah Asosiasi Konsultan Pajak Publik Indonesia Provinsi Jawa Barat.',
            $p('Pengurus Daerah (PD) Asosiasi Konsultan Pajak Publik Indonesia (AKP2I) Jawa Barat adalah perpanjangan organisasi AKP2I di tingkat provinsi. PD menaungi Pengurus Cabang (PC) di kota dan kabupaten se-Jawa Barat serta menjadi rumah bersama bagi konsultan pajak, praktisi, dan calon konsultan pajak di wilayah ini.',
                'Sejak dibentuk, PD AKP2I Jawa Barat berfokus pada tiga hal: meningkatkan kompetensi anggota melalui Pendidikan Profesional Berkelanjutan (PPL) dan Brevet Pajak, membangun jejaring antaranggota lintas cabang, serta menjadi mitra yang konstruktif bagi otoritas pajak dan pemerintah daerah dalam mendorong kepatuhan sukarela wajib pajak.')
            . '<h2>Visi</h2>' . $p('Menjadi wadah konsultan pajak yang profesional, berintegritas, dan berperan aktif dalam membangun kepatuhan pajak di Jawa Barat.')
            . '<h2>Misi</h2><ul><li>Menyelenggarakan pendidikan dan pelatihan perpajakan yang berkualitas dan terjangkau.</li><li>Menjaga etika profesi dan melindungi kepentingan anggota.</li><li>Memperkuat peran Pengurus Cabang sebagai ujung tombak pelayanan anggota.</li><li>Menjalin kemitraan dengan otoritas pajak, perguruan tinggi, dan dunia usaha.</li></ul>'],
        'sambutan' => ['Sambutan Ketua PD', 'Selamat datang di website resmi PD AKP2I Jawa Barat. Website ini kami hadirkan sebagai pusat informasi kegiatan organisasi, layanan keanggotaan, serta jadwal pelatihan bagi anggota dan masyarakat umum. Kami mengajak seluruh anggota untuk terus meningkatkan kompetensi melalui PPL, aktif berdiskusi di forum, dan menjaga integritas profesi.',
            $p('Assalamu\'alaikum warahmatullahi wabarakatuh, salam sejahtera bagi kita semua.',
                'Atas nama Pengurus Daerah AKP2I Jawa Barat, saya mengucapkan selamat datang di website resmi kami. Website ini kami hadirkan sebagai pusat informasi kegiatan organisasi, layanan keanggotaan, serta jadwal pelatihan bagi anggota dan masyarakat umum.',
                'Kami mengajak seluruh anggota untuk terus meningkatkan kompetensi melalui PPL, aktif berdiskusi di forum, dan menjaga integritas profesi. Bersama Pengurus Cabang di seluruh Jawa Barat, kami berkomitmen menghadirkan organisasi yang melayani dan bermanfaat.',
                'Wassalamu\'alaikum warahmatullahi wabarakatuh.', '<strong>{ketua}</strong><br>Ketua PD AKP2I Jawa Barat')],
        'tentang-keanggotaan' => ['Tentang Keanggotaan', 'Jenis anggota, syarat umum, dan kewajiban iuran anggota AKP2I.',
            $p('Keanggotaan AKP2I terbuka bagi konsultan pajak, praktisi perpajakan, dan lulusan Brevet Pajak. Pendaftaran anggota baru serta layanan administrasi keanggotaan (perpanjangan KTA, legalisir SK, dan peningkatan jenis anggota) diproses oleh Pengurus Pusat.')
            . '<h2>Jenis anggota</h2><ul><li><strong>Anggota Utama</strong> — konsultan pajak yang memiliki izin praktik.</li><li><strong>Anggota Madya</strong> — belum memiliki izin praktik, namun memenuhi kriteria seperti lulus USKP/penyetaraan, mantan pegawai DJP, atau memiliki Brevet resmi.</li><li><strong>Anggota Pratama</strong> — termasuk alumni Brevet AKP2I.</li><li><strong>Anggota Kehormatan</strong> — ditetapkan oleh Pengurus Pusat.</li></ul>'
            . '<h2>Iuran tahunan</h2>' . $p('Iuran berlaku per tahun kalender (1 Januari–31 Desember) dan dibayarkan melalui sekretariat PD. Anggota yang menunggak dapat dikenai teguran hingga skors sesuai peraturan organisasi. Status iuran dapat dipantau di menu Profil setelah masuk ke akun anggota.')],
        'layanan-keanggotaan' => ['Layanan Keanggotaan', 'Pendaftaran anggota baru dan layanan anggota diproses Pengurus Pusat AKP2I.',
            $p('Untuk mendaftar sebagai anggota baru, memperpanjang Kartu Tanda Anggota (KTA), mengajukan legalisir SK, atau meningkatkan jenis keanggotaan, silakan gunakan layanan resmi Pengurus Pusat melalui tautan di bawah ini. Setelah terdaftar, data Anda akan tercatat di PD Jawa Barat dan Pengurus Cabang sesuai domisili.')],
        'kebijakan-privasi' => ['Kebijakan Privasi', 'Bagaimana PD AKP2I Jawa Barat mengelola data pribadi.',
            $p('Kami mengumpulkan data pribadi seperlunya untuk keperluan administrasi keanggotaan, pendaftaran pelatihan, dan penerbitan sertifikat. Data seperti NIK, NPWP, nomor telepon, dan alamat tidak pernah ditampilkan di halaman publik.',
                'Halaman Cek Anggota hanya menampilkan nama, jenis anggota, cabang, dan status keanggotaan. Anda dapat meminta perbaikan data melalui sekretariat PD atau menu Hubungi Kami.')],
        'syarat-ketentuan' => ['Syarat & Ketentuan', 'Ketentuan penggunaan website dan layanan PD AKP2I Jawa Barat.',
            $p('Dengan menggunakan website ini, Anda setuju untuk memberikan data yang benar saat mendaftar akun atau pelatihan, menjaga kerahasiaan akun, dan menggunakan forum diskusi secara santun sesuai kode etik profesi.',
                'Biaya pelatihan yang telah diverifikasi tidak dapat dikembalikan, kecuali acara dibatalkan oleh penyelenggara. Sertifikat diterbitkan hanya untuk peserta yang tercatat hadir.')],
    ],

    'blogs' => [
        // [kategori, judul, ringkasan, [paragraf], foto latar poster, hari lalu, tag]
        ['Berita Organisasi', 'PD AKP2I Jawa Barat Gelar Rapat Kerja Daerah 2026', 'Rakerda menetapkan program kerja PD dan PC untuk satu tahun ke depan, dengan fokus pada PPL dan penguatan cabang.',
            ['Pengurus Daerah AKP2I Jawa Barat menyelenggarakan Rapat Kerja Daerah (Rakerda) yang diikuti pengurus PD serta perwakilan seluruh Pengurus Cabang di Jawa Barat.', 'Dalam sambutannya, {ketua} menekankan pentingnya pelatihan berkelanjutan bagi anggota serta penguatan peran cabang dalam melayani anggota di daerah.', 'Rakerda menghasilkan sejumlah program prioritas, antara lain jadwal PPL rutin setiap triwulan, Brevet Pajak A dan B untuk umum, serta digitalisasi data keanggotaan melalui website ini.', 'Kegiatan ditutup dengan penandatanganan komitmen bersama pengurus PD dan PC untuk menjalankan program kerja yang telah disepakati.'],
            '08-ballroom.jpg', 160, ['Rakerda', 'Organisasi']],
        ['Berita Organisasi', 'Pelantikan Pengurus PC AKP2I Kota Bekasi', 'Pengurus Cabang Kota Bekasi resmi dilantik dan siap menjalankan program kerja di wilayahnya.',
            ['Ketua PD AKP2I Jawa Barat, {ketua}, melantik jajaran Pengurus Cabang Kota Bekasi dalam sebuah acara yang dihadiri anggota dan undangan.', 'Pengurus yang baru dilantik diharapkan dapat memperluas jangkauan layanan kepada anggota, terutama melalui kegiatan PPL dan diskusi rutin di tingkat cabang.', 'Ketua PC Kota Bekasi menyampaikan rencana kegiatan awal, termasuk pendataan ulang anggota dan kelas diskusi bulanan.'],
            '09-great-hall.jpg', 120, ['Pelantikan', 'PC Bekasi']],
        ['Pelatihan', 'PPL Coretax Diikuti Ratusan Anggota se-Jawa Barat', 'Pelatihan membahas perubahan alur administrasi perpajakan pada sistem inti administrasi perpajakan.',
            ['Bidang Pendidikan dan Pelatihan PD AKP2I Jawa Barat menyelenggarakan PPL bertema implementasi sistem inti administrasi perpajakan (Coretax).', 'Narasumber memaparkan perubahan alur kerja yang perlu dipahami konsultan pajak, mulai dari pengelolaan akun wajib pajak hingga pelaporan.', 'Peserta antusias mengikuti sesi tanya jawab, terutama terkait praktik pendampingan klien selama masa transisi.', 'Sertifikat PPL dapat diunduh peserta melalui menu Profil masing-masing.'],
            '01-seminar-room-a.jpg', 75, ['PPL', 'Coretax']],
        ['Perpajakan', 'Mengenal Coretax: Apa yang Perlu Disiapkan Konsultan Pajak', 'Beberapa hal praktis yang perlu disiapkan konsultan pajak dalam mendampingi klien di sistem administrasi baru.',
            ['Pembaruan sistem administrasi perpajakan membawa perubahan cara kerja konsultan pajak dalam mendampingi klien.', 'Langkah pertama yang disarankan adalah memastikan data identitas dan akun klien mutakhir, termasuk alamat email dan nomor telepon yang aktif.', 'Konsultan juga perlu memahami pengaturan peran dan kuasa dalam sistem, sehingga akses ke data klien sesuai kewenangan yang diberikan.', 'Terakhir, dokumentasikan setiap langkah pendampingan. Catatan yang rapi akan memudahkan penelusuran bila terjadi kendala teknis.'],
            '24-computer-lab.jpg', 60, ['Coretax', 'Tips']],
        ['Perpajakan', 'Tips Menyiapkan SPT Tahunan Klien Lebih Awal', 'Persiapan sejak awal tahun membantu konsultan menghindari antrean pekerjaan menjelang batas waktu pelaporan.',
            ['Menjelang musim pelaporan SPT Tahunan, beban kerja konsultan pajak biasanya meningkat tajam.', 'Mulailah dengan membuat daftar klien beserta jenis SPT, kelengkapan dokumen, dan target penyelesaian. Kirim permintaan dokumen sejak awal tahun.', 'Gunakan daftar periksa yang sama untuk setiap klien agar tidak ada lampiran yang terlewat, misalnya bukti potong, daftar harta, dan laporan keuangan.', 'Sampaikan perkembangan secara berkala kepada klien sehingga keputusan yang diperlukan dapat diambil lebih cepat.'],
            '20-workspace-bright.jpg', 40, ['SPT Tahunan', 'Tips']],
        ['Pelatihan', 'Pendaftaran Brevet Pajak A & B Angkatan Baru Dibuka', 'Program Brevet A & B terbuka untuk umum, mahasiswa, dan staf perpajakan perusahaan.',
            ['PD AKP2I Jawa Barat kembali membuka pendaftaran Brevet Pajak A & B untuk angkatan baru.', 'Program ini dirancang bagi lulusan akuntansi, staf keuangan, maupun masyarakat umum yang ingin memahami perpajakan secara komprehensif.', 'Peserta akan mempelajari ketentuan umum perpajakan, Pajak Penghasilan orang pribadi dan badan, PPN, serta praktik pengisian SPT.', 'Pendaftaran dilakukan secara online melalui menu Pusat Pelatihan. Kuota terbatas.'],
            '16-classroom-desks.jpg', 12, ['Brevet', 'Pendaftaran']],
        ['Perpajakan', 'Pentingnya Rekonsiliasi PPN Setiap Masa Pajak', 'Rekonsiliasi rutin membantu mendeteksi selisih faktur sebelum menjadi temuan pemeriksaan.',
            ['Selisih antara faktur keluaran, faktur masukan, dan pembukuan kerap baru disadari saat pemeriksaan.', 'Rekonsiliasi setiap masa pajak membantu wajib pajak memastikan setiap transaksi tercatat dan dilaporkan dengan benar.', 'Konsultan dapat membantu klien membangun prosedur rekonsiliasi sederhana yang dijalankan tim keuangan internal setiap bulan.'],
            '15-workspace-bright.jpg', 95, ['PPN', 'Kepatuhan']],
        ['Berita Organisasi', 'PD AKP2I Jabar Jalin Kerja Sama dengan Perguruan Tinggi', 'Kerja sama mencakup kelas tamu perpajakan dan program Brevet untuk mahasiswa.',
            ['PD AKP2I Jawa Barat menandatangani nota kesepahaman dengan sejumlah program studi akuntansi di Jawa Barat.', 'Kerja sama ini meliputi kelas tamu perpajakan oleh anggota AKP2I, magang di kantor konsultan pajak anggota, serta potongan biaya Brevet bagi mahasiswa.', 'Diharapkan kolaborasi ini melahirkan generasi konsultan pajak yang siap menghadapi kebutuhan dunia usaha.'],
            '22-lecture-hall-classic.jpg', 140, ['Kerja Sama', 'Kampus']],
        ['Perpajakan', 'Etika Konsultan Pajak dalam Era Digital', 'Kerahasiaan data klien dan kehati-hatian dalam berkomunikasi menjadi semakin penting.',
            ['Digitalisasi memudahkan pertukaran dokumen dengan klien, namun juga meningkatkan risiko kebocoran data.', 'Konsultan pajak wajib menjaga kerahasiaan data klien, termasuk saat menggunakan aplikasi pesan dan penyimpanan awan.', 'Gunakan kata sandi yang kuat, batasi akses tim sesuai kebutuhan, dan hindari membagikan dokumen klien melalui grup percakapan.'],
            '13-boardroom-white.jpg', 30, ['Etika', 'Profesi']],
        ['Pelatihan', 'Seminar PPh Pasal 21: Studi Kasus Penghitungan Bulanan', 'Seminar membedah studi kasus penghitungan dan pelaporan pemotongan PPh Pasal 21.',
            ['Seminar PPh Pasal 21 yang diselenggarakan PD AKP2I Jawa Barat membahas studi kasus penghitungan bulanan untuk berbagai status pegawai.', 'Narasumber menekankan pentingnya data status pegawai yang mutakhir agar penghitungan pemotongan sesuai ketentuan.', 'Materi seminar dapat diakses peserta melalui email yang dikirimkan panitia.'],
            '21-hall-chairs.jpg', 50, ['PPh 21', 'Seminar']],
        ['Berita Organisasi', 'Halal Bihalal Keluarga Besar AKP2I Jawa Barat', 'Silaturahmi anggota, pengurus PD, dan PC dalam suasana Idulfitri.',
            ['Keluarga besar AKP2I Jawa Barat menggelar halal bihalal yang dihadiri anggota dan pengurus dari berbagai cabang.', 'Acara diisi tausiyah, ramah tamah, dan pemberian apresiasi kepada anggota senior atas dedikasinya bagi organisasi.'],
            '07-meeting-room-banquet.jpg', 200, ['Silaturahmi']],
        ['Perpajakan', 'Mendampingi UMKM Memahami Kewajiban Pajaknya', 'UMKM membutuhkan pendampingan yang sederhana dan praktis.',
            ['Banyak pelaku UMKM yang belum memahami kewajiban perpajakannya, mulai dari pendaftaran hingga pelaporan.', 'Konsultan pajak dapat berperan dengan memberikan penjelasan sederhana, membantu pencatatan, dan mengingatkan jadwal pembayaran serta pelaporan.', 'Sejumlah anggota AKP2I Jawa Barat rutin mengikuti kegiatan klinik pajak UMKM bersama komunitas usaha di daerah.'],
            '17-meeting-table-small.jpg', 22, ['UMKM']],
        // ditulis anggota: menunggu review / draf (lihat Demo\ContentSeeder)
        ['Perpajakan', 'Pengalaman Mendampingi Pemeriksaan Pajak Pertama', 'Catatan pribadi seorang anggota saat pertama kali mendampingi klien dalam pemeriksaan.',
            ['Pemeriksaan pertama selalu menegangkan, baik bagi klien maupun konsultannya.', 'Hal yang paling membantu adalah dokumentasi yang rapi sejak awal dan komunikasi yang terbuka dengan klien.'],
            '18-meeting-room-window.jpg', 3, ['Pemeriksaan'], 'review'],
        ['Pelatihan', 'Catatan dari Kelas Brevet Angkatan Lalu', 'Kesan alumni Brevet tentang materi dan suasana belajar.',
            ['Kelas Brevet memberikan dasar yang kuat bagi saya yang berlatar belakang staf keuangan.', 'Diskusi kasus bersama pengajar praktisi menjadi bagian yang paling bermanfaat.'],
            '19-classroom-hill.jpg', 2, ['Brevet'], 'review'],
        ['Perpajakan', 'Draf: Checklist Dokumen Restitusi', 'Daftar dokumen yang biasanya diminta saat pengajuan pengembalian kelebihan pembayaran pajak.',
            ['Draf ini masih disusun.'], '23-meeting-room-modern.jpg', 1, ['Restitusi'], 'draft'],
    ],

    'threads' => [
        // [kategori forum, judul, isi, [balasan...]] — balasan diawali '>' = balasan atas komentar sebelumnya
        ['Umum', 'Aturan & Etika Diskusi Forum AKP2I Jawa Barat', 'Selamat datang di forum. Mohon berdiskusi dengan santun, tidak membagikan data pribadi klien, dan tidak melakukan promosi jasa. Diskusi yang melanggar dapat disembunyikan moderator.', [], 'pinned'],
        ['PPh', 'Penghitungan PPh 21 untuk pegawai yang masuk di pertengahan tahun', 'Rekan-rekan, bagaimana praktik penghitungan pemotongan untuk pegawai baru yang mulai bekerja di pertengahan tahun? Apakah ada yang punya template perhitungan yang rapi?',
            ['Saya biasanya memisahkan perhitungan per masa dan menyesuaikan di masa terakhir. Kuncinya data status pegawai harus lengkap sejak awal.', '>Setuju. Jangan lupa minta bukti potong dari pemberi kerja sebelumnya bila ada.', 'Template yang saya pakai berbasis spreadsheet, nanti saya bagikan setelah dirapikan ya.', '>Terima kasih Pak, sangat membantu.']],
        ['PPN', 'Faktur pajak terlambat dibuat, apa langkah terbaik?', 'Klien saya baru sadar ada beberapa faktur yang belum dibuat untuk penyerahan bulan lalu. Bagaimana sebaiknya penanganannya?',
            ['Segera buat fakturnya dan hitung potensi sanksi administrasinya. Jelaskan ke klien risikonya secara transparan.', 'Sekalian evaluasi prosedur internal klien supaya tidak berulang, misalnya pengingat otomatis dari bagian penjualan.']],
        ['Profesi', 'Tips membangun klien di awal karier konsultan pajak', 'Saya baru mendapatkan izin praktik. Ada saran bagaimana membangun basis klien dengan cara yang etis?',
            ['Mulai dari jaringan terdekat dan komunitas UMKM. Reputasi dibangun dari pelayanan yang konsisten.', 'Ikut kegiatan PPL dan forum seperti ini juga membuka jejaring dengan senior.', '>Betul, banyak rujukan klien datang dari sesama konsultan yang kelebihan pekerjaan.', 'Hindari perang harga. Lebih baik jelaskan nilai layanan Anda.']],
        ['Umum', 'Pengalaman mengikuti PPL Coretax kemarin', 'Bagaimana kesan rekan-rekan setelah PPL Coretax? Materi mana yang paling relevan untuk praktik sehari-hari?',
            ['Sesi pengelolaan akun dan kuasa paling relevan buat saya.', 'Semoga ada sesi lanjutan khusus praktik, misalnya kelas kecil dengan laptop.', '>Usulan bagus, nanti saya teruskan ke Bidang Diklat.']],
        ['PPh', 'Biaya yang boleh dikurangkan untuk wajib pajak badan', 'Apakah ada rangkuman praktis jenis biaya yang sering dikoreksi saat pemeriksaan?',
            ['Biaya yang tidak didukung bukti memadai paling sering dikoreksi. Pastikan setiap biaya ada dokumen pendukung.', 'Biaya pribadi pemegang saham yang dibebankan ke perusahaan juga rawan koreksi.']],
        ['PPN', 'PPN atas penyerahan jasa ke luar daerah', 'Klien saya penyedia jasa yang mulai melayani pelanggan di luar Jawa Barat. Apakah ada perbedaan perlakuan PPN?',
            ['Prinsipnya sama selama penyerahan di dalam daerah pabean. Yang perlu diperhatikan administrasi faktur dan identitas pembeli.']],
        ['Profesi', 'Berapa jam PPL yang ideal per tahun?', 'Selain memenuhi ketentuan, berapa jam PPL yang menurut rekan-rekan ideal untuk menjaga kompetensi?',
            ['Saya menargetkan ikut minimal satu kegiatan setiap triwulan.', 'Rekap jam PPL di profil website ini membantu sekali untuk memantau.', '>Iya, sekarang tidak perlu mengumpulkan sertifikat manual lagi.']],
        ['Umum', 'Usulan kegiatan PC Kota Bandung semester depan', 'Pengurus PC Kota Bandung membuka usulan tema diskusi bulanan. Silakan tulis usulan di sini.',
            ['Diskusi kasus pemeriksaan untuk UMKM.', 'Kelas penggunaan aplikasi akuntansi yang terintegrasi dengan pelaporan pajak.', 'Sharing session manajemen kantor konsultan pajak.']],
        ['PPh', 'Bukti potong dari pemberi kerja sebelumnya tidak diberikan', 'Pegawai baru klien saya tidak memiliki bukti potong dari kantor lamanya. Bagaimana menyikapinya?',
            ['Minta pegawai menghubungi kantor lamanya secara tertulis. Simpan bukti permintaannya.', 'Sementara itu, hitung berdasarkan data yang tersedia dan beri catatan untuk penyesuaian.']],
        ['Profesi', 'Diskusi tarif jasa konsultan untuk UMKM', 'Bagaimana menentukan tarif jasa yang wajar untuk UMKM tanpa merendahkan profesi?',
            ['Saya memakai paket layanan bulanan dengan cakupan yang jelas.', 'Jelaskan ruang lingkup tertulis agar tidak terjadi permintaan di luar kesepakatan.'], 'locked'],
        ['Umum', 'Rekomendasi buku referensi perpajakan terbaru', 'Ada rekomendasi buku atau referensi yang mutakhir untuk persiapan USKP?',
            ['Referensi resmi dari otoritas pajak tetap yang utama, dilengkapi buku ajar Brevet.', 'Ikuti juga webinar-webinar PD, materinya sering kali sudah merangkum perubahan terbaru.']],
    ],

    'research' => [
        // [judul, kategori, abstrak, kata kunci]
        ['Pengaruh Pendampingan Konsultan Pajak terhadap Kepatuhan UMKM di Kota Bandung', 'Kepatuhan', 'Penelitian ini menganalisis hubungan antara pendampingan oleh konsultan pajak dengan tingkat kepatuhan formal pelaku UMKM. Data diperoleh melalui survei terhadap pelaku UMKM yang menggunakan dan tidak menggunakan jasa konsultan. Hasil menunjukkan pendampingan berhubungan positif dengan ketepatan waktu pelaporan.', 'kepatuhan pajak, UMKM, konsultan pajak'],
        ['Kesiapan Konsultan Pajak dalam Implementasi Sistem Inti Administrasi Perpajakan', 'Administrasi', 'Studi kualitatif ini memetakan kesiapan konsultan pajak di Jawa Barat dalam beradaptasi dengan sistem administrasi perpajakan baru, meliputi kesiapan teknis, sumber daya, dan pemahaman alur kerja.', 'coretax, digitalisasi, administrasi pajak'],
        ['Persepsi Wajib Pajak Orang Pribadi terhadap Pelaporan SPT Elektronik', 'Kepatuhan', 'Penelitian ini mengkaji persepsi kemudahan dan kebermanfaatan pelaporan SPT secara elektronik pada wajib pajak orang pribadi karyawan di wilayah Bekasi.', 'SPT elektronik, persepsi, wajib pajak orang pribadi'],
        ['Analisis Faktor Penyebab Koreksi Biaya dalam Pemeriksaan Pajak Badan', 'PPh', 'Artikel ini mengidentifikasi jenis koreksi biaya yang sering muncul pada pemeriksaan wajib pajak badan berdasarkan studi dokumen dan wawancara praktisi.', 'pemeriksaan pajak, koreksi fiskal, biaya'],
        ['Efektivitas Program Brevet Pajak dalam Meningkatkan Kompetensi Staf Keuangan', 'Pendidikan', 'Penelitian ini mengevaluasi peningkatan kompetensi peserta Brevet Pajak melalui tes awal dan tes akhir, serta persepsi peserta terhadap metode pembelajaran.', 'brevet pajak, kompetensi, pelatihan'],
        ['Peran Etika Profesi dalam Menjaga Kerahasiaan Data Klien', 'Profesi', 'Kajian konseptual mengenai penerapan prinsip kerahasiaan data klien oleh konsultan pajak di era pertukaran dokumen digital.', 'etika profesi, kerahasiaan, data pribadi'],
        ['Rekonsiliasi PPN sebagai Instrumen Pengendalian Internal pada Perusahaan Dagang', 'PPN', 'Studi kasus pada perusahaan dagang menengah yang menunjukkan bagaimana rekonsiliasi PPN rutin mengurangi selisih pelaporan.', 'PPN, rekonsiliasi, pengendalian internal'],
        ['Literasi Pajak Mahasiswa Akuntansi di Jawa Barat', 'Pendidikan', 'Survei literasi pajak terhadap mahasiswa akuntansi tingkat akhir untuk memetakan kebutuhan materi perpajakan praktis sebelum memasuki dunia kerja.', 'literasi pajak, mahasiswa, pendidikan'],
    ],

    'galleries' => [
        // [judul, deskripsi, hari lalu, judul event terkait|null, [ [foto, keterangan], ... ]]
        ['Rapat Kerja Daerah 2026', 'Dokumentasi Rakerda PD AKP2I Jawa Barat bersama perwakilan seluruh PC.', 160, null,
            [['08-ballroom.jpg', 'Ruang utama Rakerda'], ['21-hall-chairs.jpg', 'Penataan kursi peserta sebelum acara'], ['07-meeting-room-banquet.jpg', 'Ruang sidang komisi'], ['13-boardroom-white.jpg', 'Rapat perumusan program kerja'], ['27-gedung-sate-plaza.jpg', 'Perjalanan peserta di Bandung'], ['26-gedung-sate-garden.jpg', 'Sesi foto bersama di Bandung']]],
        ['Pelantikan Pengurus PC Kota Bekasi', 'Pelantikan jajaran Pengurus Cabang Kota Bekasi.', 120, null,
            [['09-great-hall.jpg', 'Aula pelantikan'], ['10-auditorium-curtain.jpg', 'Panggung utama'], ['21-hall-chairs.jpg', 'Kursi undangan'], ['14-meeting-room-modern.jpg', 'Rapat perdana pengurus PC'], ['29-city-aerial.jpg', 'Wilayah kerja PC']]],
        ['PPL Coretax', 'Suasana Pendidikan Profesional Berkelanjutan tentang sistem administrasi perpajakan baru.', 75, 'PPL Implementasi Coretax bagi Konsultan Pajak',
            [['01-seminar-room-a.jpg', 'Ruang PPL sesi pagi'], ['02-seminar-room-b.jpg', 'Ruang sesi diskusi'], ['24-computer-lab.jpg', 'Sesi praktik dengan komputer'], ['03-seminar-room-c.jpg', 'Ruang kelas paralel'], ['04-seminar-room-d.jpg', 'Kelas kelompok kecil'], ['06-seminar-room-dim.jpg', 'Sesi pemaparan narasumber']]],
        ['Brevet Pajak A & B Angkatan Sebelumnya', 'Kelas Brevet untuk umum dan mahasiswa.', 95, 'Brevet Pajak A & B Angkatan II',
            [['16-classroom-desks.jpg', 'Ruang kelas Brevet'], ['19-classroom-hill.jpg', 'Kelas akhir pekan'], ['22-lecture-hall-classic.jpg', 'Ujian akhir Brevet'], ['20-lecture-hall-wide.jpg', 'Kuliah umum pembukaan'], ['05-training-room-a.jpg', 'Kelas latihan soal'], ['23-lecture-hall-small.jpg', 'Kelas remedial']]],
        ['Seminar PPh Pasal 21', 'Seminar studi kasus penghitungan PPh Pasal 21.', 50, 'Seminar Studi Kasus PPh Pasal 21',
            [['21-hall-chairs.jpg', 'Ruang seminar'], ['18-lecture-hall-steps.jpg', 'Auditorium'], ['17-lecture-hall-green.jpg', 'Sesi tanya jawab'], ['11-meeting-table-small.jpg', 'Diskusi panitia']]],
        ['Halal Bihalal Keluarga Besar', 'Silaturahmi anggota dan pengurus.', 200, null,
            [['07-meeting-room-banquet.jpg', 'Ruang ramah tamah'], ['25-gedung-sate-front.jpg', 'Bandung saat Idulfitri'], ['28-bandung-landmark.jpg', 'Lokasi kegiatan'], ['30-bogor-view.jpg', 'Perjalanan rombongan PC Bogor']]],
    ],

    'sliders' => [
        // [foto, judul, subjudul, tombol, target ('event:<judul>' | 'route:<nama>')]
        ['26-gedung-sate-garden.jpg', 'Bersama Membangun Kepatuhan Pajak di Jawa Barat', 'Wadah konsultan pajak profesional dan berintegritas di 12 kota/kabupaten.', 'Tentang Kami', 'route:site.profile'],
        ['16-classroom-desks.jpg', 'Brevet Pajak A & B Angkatan Baru', 'Pendaftaran dibuka untuk umum, mahasiswa, dan staf perpajakan. Kuota terbatas.', 'Daftar Sekarang', 'event:Brevet Pajak A & B Angkatan III'],
        ['01-seminar-room-a.jpg', 'PPL Akhir Tahun 2026', 'Evaluasi kepatuhan dan persiapan SPT Tahunan klien. 8 jam SKP.', 'Lihat Jadwal', 'event:PPL Akhir Tahun: Evaluasi Kepatuhan & Persiapan SPT Tahunan'],
    ],

    'documents' => [
        // [judul, kategori, keterangan]
        ['Anggaran Dasar & Anggaran Rumah Tangga AKP2I', 'AD/ART', 'Dokumen dasar organisasi.'],
        ['Kode Etik Konsultan Pajak AKP2I', 'Peraturan Organisasi', 'Pedoman perilaku profesional anggota.'],
        ['Pedoman Pendidikan Profesional Berkelanjutan (PPL)', 'Peraturan Organisasi', 'Ketentuan pelaksanaan dan pencatatan jam PPL.'],
        ['SK Susunan Pengurus Daerah Periode 2024–2029', 'SK', 'Surat keputusan pengesahan pengurus PD.'],
    ],

    'contact_messages' => [
        // [subjek, pesan, sudah dibaca?]
        ['Keanggotaan', 'Selamat siang, saya baru pindah domisili dari Bogor ke Bandung. Bagaimana prosedur pindah cabang?', true],
        ['Pelatihan', 'Apakah Brevet A & B angkatan baru tersedia kelas akhir pekan? Saya karyawan.', true],
        ['Iuran', 'Saya sudah transfer iuran tahun ini tetapi status di profil belum berubah. Mohon dicek.', false],
        ['Informasi Cabang', 'Apakah sudah ada PC di Kabupaten Garut? Saya dan beberapa rekan berminat bergabung.', false],
        ['Pelatihan', 'Mohon info apakah sertifikat PPL bisa dicetak ulang bila hilang.', true],
        ['Lainnya', 'Kami dari kampus ingin mengundang pengurus untuk kuliah tamu perpajakan. Siapa yang bisa dihubungi?', false],
        ['Keanggotaan', 'Bagaimana cara meningkatkan keanggotaan dari Pratama ke Madya?', true],
        ['Pelatihan', 'Untuk webinar gratis, apakah peserta umum juga mendapat sertifikat?', false],
    ],
];
