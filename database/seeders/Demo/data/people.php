<?php

// Data demo: semua nama, alamat, dan nomor FIKTIF.
return [
    // nama dikelompokkan per suku supaya nama depan & belakang serasi; bobot = proporsi di Jawa Barat
    'name_groups' => [
        'sunda' => [40, ['Asep', 'Dedi', 'Ujang', 'Cecep', 'Dadang', 'Agus', 'Yudi', 'Gilang', 'Yogi', 'Dodi', 'Ade', 'Ruhiyat'],
            ['Euis', 'Neng', 'Lilis', 'Yanti', 'Iis', 'Rina', 'Wulan', 'Dewi', 'Imas', 'Heni'],
            ['Hidayat', 'Permana', 'Suryana', 'Supriatna', 'Sudrajat', 'Mulyana', 'Sasmita', 'Rukmana', 'Kartawijaya', 'Sumarna', 'Ramdani', 'Somantri']],
        'jawa' => [22, ['Budi', 'Joko', 'Bambang', 'Eko', 'Teguh', 'Wahyu', 'Agung', 'Bayu', 'Aditya', 'Hadi'],
            ['Sri', 'Rini', 'Ningsih', 'Endang', 'Ratna', 'Indah', 'Dian', 'Kartika'],
            ['Santoso', 'Purnomo', 'Wibowo', 'Susanto', 'Setiawan', 'Hermawan', 'Kusuma', 'Prasetyo', 'Nugroho', 'Wijayanto']],
        'umum' => [18, ['Ahmad', 'Rizki', 'Dimas', 'Fajar', 'Reza', 'Ilham', 'Arif', 'Irfan', 'Fikri', 'Taufik'],
            ['Siti', 'Putri', 'Anisa', 'Nurul', 'Fitri', 'Intan', 'Ayu', 'Mega', 'Desi'],
            ['Saputra', 'Pratama', 'Kurniawan', 'Firmansyah', 'Nugraha', 'Maulana', 'Hakim', 'Fauzi', 'Iskandar', 'Ramadhan']],
        'batak' => [8, ['Sahat', 'Martua', 'Hotman', 'Parlindungan', 'Robert', 'Togar'],
            ['Rosa', 'Butet', 'Lasma', 'Rotua', 'Mariani'],
            ['Siregar', 'Nasution', 'Simanjuntak', 'Harahap', 'Lubis', 'Sitompul', 'Panjaitan']],
        'minang' => [5, ['Rizal', 'Hendra', 'Yulius', 'Afdal', 'Zulkifli'],
            ['Yulia', 'Rahmi', 'Fitria', 'Nofrianti'],
            ['Chaniago', 'Tanjung', 'Piliang', 'Koto', 'Sikumbang']],
        'tionghoa' => [7, ['Ricky', 'Hendrik', 'Benny', 'Johan', 'Daniel', 'Steven', 'Kevin'],
            ['Veronika', 'Linda', 'Fransiska', 'Melissa', 'Jessica', 'Susan'],
            ['Halim', 'Tanoto', 'Gozali', 'Hartono', 'Salim', 'Wijaya', 'Gunawan', 'Santosa']],
    ],
    // gelar: anggota utama/madya bergelar sarjana; A.Md./S.Sos. hanya untuk pratama
    'degrees' => ['S.E.', 'S.E., M.Ak.', 'S.Ak.', 'S.E., M.M.', 'S.E., Ak.', 'S.H.', 'S.E., M.Si.', 'S.Ak., M.Ak.'],
    'degrees_pratama' => ['S.E.', 'S.Ak.', 'A.Md.', 'S.Sos.', 'S.H.'],
    'office_patterns' => ['KKP %s & Rekan', 'Konsultan Pajak %s', 'Kantor Konsultan Pajak %s', 'KKP %s Tax Consulting', '%s & Partners Tax Advisory'],
    // PC: kode => [nama kota, bobot jumlah anggota, kode telepon, jalan contoh]
    'branches' => [
        'BDG' => ['Kota Bandung', 22, '022', ['Jl. Buah Batu', 'Jl. Pasirkaliki', 'Jl. Setiabudi', 'Jl. Soekarno-Hatta', 'Jl. Dipatiukur']],
        'BDGKAB' => ['Kabupaten Bandung', 9, '022', ['Jl. Raya Banjaran', 'Jl. Raya Dayeuhkolot', 'Jl. Raya Soreang']],
        'BKS' => ['Kota Bekasi', 18, '021', ['Jl. Ahmad Yani', 'Jl. Juanda', 'Jl. Kemakmuran', 'Jl. Harapan Indah']],
        'BKSKAB' => ['Kabupaten Bekasi', 9, '021', ['Jl. Raya Cikarang', 'Jl. Kalimalang', 'Jl. Raya Tambun']],
        'BGR' => ['Kota Bogor', 12, '0251', ['Jl. Pajajaran', 'Jl. Sudirman', 'Jl. Raya Tajur']],
        'BGRKAB' => ['Kabupaten Bogor', 8, '021', ['Jl. Raya Cibinong', 'Jl. Raya Parung', 'Jl. Raya Bojonggede']],
        'DPK' => ['Kota Depok', 10, '021', ['Jl. Margonda Raya', 'Jl. Juanda', 'Jl. Raya Sawangan']],
        'CMH' => ['Kota Cimahi', 6, '022', ['Jl. Amir Machmud', 'Jl. Gatot Subroto']],
        'CRB' => ['Kota Cirebon', 7, '0231', ['Jl. Siliwangi', 'Jl. Tuparev', 'Jl. Kartini']],
        'TSM' => ['Kota Tasikmalaya', 5, '0265', ['Jl. HZ. Mustofa', 'Jl. Sutisna Senjaya']],
        'KRW' => ['Kabupaten Karawang', 6, '0267', ['Jl. Tuparev', 'Jl. Kertabumi']],
        'SKB' => ['Kota Sukabumi', 4, '0266', ['Jl. Ahmad Yani', 'Jl. Siliwangi']],
    ],
    'umum_roles' => ['staf pajak perusahaan', 'mahasiswa akuntansi', 'staf keuangan', 'pemilik UMKM', 'calon konsultan pajak'],
];
