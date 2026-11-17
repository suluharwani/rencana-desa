<?php
declare(strict_types=1);

function load_env(string $path): void {
    if (!is_readable($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($value !== '' && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

load_env(__DIR__ . '/.env');

session_start();

define('DB_HOST', getenv('DB_HOST') !== false ? (string)getenv('DB_HOST') : '127.0.0.1');
define('DB_PORT', (int)(getenv('DB_PORT') !== false ? getenv('DB_PORT') : 3306));
define('DB_USER', getenv('DB_USER') !== false ? (string)getenv('DB_USER') : 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? (string)getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') !== false ? (string)getenv('DB_NAME') : 'perencanaan_desa');

define('APP_NAME', 'Pengelolaan Perencanaan Desa');
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'password');

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsnNoDb = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdoServer = new PDO($dsnNoDb, DB_USER, DB_PASS, $options);
    $pdoServer->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

    $dsn = $dsnNoDb . ';dbname=' . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    init_db($pdo);
    return $pdo;
}

function init_db(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        `key` VARCHAR(100) PRIMARY KEY,
        `value` TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS baseline (
        id INT AUTO_INCREMENT PRIMARY KEY,
        label VARCHAR(255) NOT NULL,
        nilai VARCHAR(255) NOT NULL,
        keterangan VARCHAR(255) DEFAULT '',
        urutan INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS baseline_tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kelompok VARCHAR(100) NOT NULL,
        item TEXT NOT NULL,
        urutan INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS pillars (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kode VARCHAR(20) NOT NULL,
        nama VARCHAR(255) NOT NULL,
        deskripsi TEXT,
        indikator TEXT,
        urutan INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS roadmap (
        id INT AUTO_INCREMENT PRIMARY KEY,
        periode VARCHAR(100) NOT NULL,
        judul VARCHAR(255) NOT NULL,
        poin TEXT NOT NULL,
        urutan INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS year1_months (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bulan VARCHAR(50) NOT NULL,
        judul VARCHAR(255) NOT NULL,
        kegiatan TEXT NOT NULL,
        urutan INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS routines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kegiatan VARCHAR(255) NOT NULL,
        frekuensi VARCHAR(100) NOT NULL,
        bukti TEXT NOT NULL,
        urutan INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS quarterly (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kuartal VARCHAR(50) NOT NULL,
        fokus VARCHAR(100) NOT NULL,
        target TEXT NOT NULL,
        output VARCHAR(255) NOT NULL,
        urutan INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS semester_eval (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bidang VARCHAR(100) NOT NULL,
        semester1 TEXT NOT NULL,
        semester2 TEXT NOT NULL,
        urutan INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS monitoring (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kode VARCHAR(50) NOT NULL,
        program VARCHAR(255) NOT NULL,
        lokasi VARCHAR(255) DEFAULT '',
        pic VARCHAR(255) DEFAULT '',
        target VARCHAR(255) DEFAULT '',
        anggaran VARCHAR(255) DEFAULT '',
        progres_fisik VARCHAR(255) DEFAULT '',
        realisasi_biaya VARCHAR(255) DEFAULT '',
        status VARCHAR(50) DEFAULT 'Belum dinilai',
        bukti TEXT,
        tindak_lanjut TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS budget_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kategori VARCHAR(100) NOT NULL,
        item TEXT NOT NULL,
        urutan INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS checklist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tahap VARCHAR(100) NOT NULL,
        item TEXT NOT NULL,
        selesai TINYINT(1) DEFAULT 0,
        urutan INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        judul VARCHAR(255) NOT NULL,
        isi TEXT NOT NULL,
        urutan INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(100) DEFAULT '',
        kontak VARCHAR(100) DEFAULT '',
        jenis VARCHAR(50) DEFAULT 'Saran',
        pesan TEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'Baru',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    seed_defaults($pdo);
}

function seed_defaults(PDO $pdo): void {
    $defaults = [
        'judul' => 'MASTER PLAN DESA PENGANTEN',
        'subjudul' => 'Roadmap pembangunan, pemberdayaan masyarakat, pelayanan publik, dan sistem monitoring.',
        'lokasi' => 'Kecamatan Klambu, Kabupaten Grobogan',
        'periode' => '2027–2034 (contoh, sesuaikan masa jabatan & dokumen resmi)',
        'versi' => 'Konsep 1.0',
        'visi' => 'Mewujudkan Desa Penganten yang mandiri, produktif, adil, terbuka, dan berdaya melalui penguatan ekonomi pertanian, pemerataan pembangunan, pemberdayaan pemuda, peningkatan pendidikan, serta pelayanan publik yang mudah dan transparan.',
        'slogan' => 'Pertanian kuat, pemuda hebat, pendidikan maju, pemerintahan melayani.',
        'prinsip' => "Berbasis data\nPartisipatif\nPrioritas adil\nAnggaran sah\nTerukur\nTerbuka",
        'tujuan' => "Program memiliki PIC dan jadwal.\nAnggaran sesuai kewenangan dan APBDes.\nManfaat dirasakan lintas dusun.\nProgres diperiksa setiap bulan.\nHasil dilaporkan kepada masyarakat.",
    ];
    $stmt = $pdo->prepare('INSERT IGNORE INTO settings(`key`,`value`) VALUES (?,?)');
    foreach ($defaults as $k => $v) $stmt->execute([$k, $v]);

    if ((int)$pdo->query('SELECT COUNT(*) FROM baseline')->fetchColumn() === 0) {
        $pdo->exec("INSERT INTO baseline(label,nilai,keterangan,urutan) VALUES
            ('Luas wilayah','7,82','km² · data historis publikasi 2023 · verifikasi ulang',1),
            ('Penduduk historis','4.553','jiwa · data administrasi 2022 · verifikasi ulang',2),
            ('Laki-laki','2.319','jiwa · data historis · verifikasi ulang',3),
            ('Perempuan','2.234','jiwa · data historis · verifikasi ulang',4)");
    }

    // Existing installations keep their original values; make their historical status explicit.
    $pdo->exec("UPDATE baseline SET keterangan=CONCAT(TRIM(TRAILING ' · verifikasi ulang' FROM keterangan), ' · verifikasi ulang')
        WHERE (label IN ('Luas wilayah','Penduduk historis','Laki-laki','Perempuan'))
        AND keterangan NOT LIKE '%verifikasi ulang%'");

    if ((int)$pdo->query('SELECT COUNT(*) FROM baseline_tasks')->fetchColumn() === 0) {
        $stmt = $pdo->prepare('INSERT INTO baseline_tasks(kelompok,item,urutan) VALUES (?,?,?)');
        $rows = [
            ['Kependudukan & wilayah', 'Jumlah dusun, RT/RW, KK dan penduduk terbaru.', 1],
            ['Pertanian', 'Luas sawah, komoditas, kalender tanam.', 2],
            ['Pertanian', 'Kelompok tani dan anggota aktif.', 3],
            ['Pertanian & infrastruktur', 'Pompa, saluran irigasi, jalan usaha tani.', 4],
            ['Pertanian', 'Produksi jagung per musim dan pascapanen.', 5],
            ['Ekonomi', 'UMKM, usaha rumah tangga, potensi pasar.', 6],
            ['Pendidikan', 'Data siswa per jenjang dan kebutuhan bantuan.', 7],
            ['Kepemudaan', 'Organisasi pemuda dan fasilitas olahraga.', 8],
            ['Layanan & kesehatan', 'Kondisi kendaraan layanan dan akses kesehatan.', 9],
        ];
        foreach ($rows as $r) $stmt->execute($r);
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM pillars')->fetchColumn() === 0) {
        $stmt = $pdo->prepare('INSERT INTO pillars(kode,nama,deskripsi,indikator,urutan) VALUES (?,?,?,?,?)');
        $rows = [
            ['1', 'Pertanian untuk petani', 'Pompanisasi dikelola kelompok tani, kas transparan, perbaikan irigasi/jalan usaha tani, dan kajian pengeringan jagung.', "Seluruh pompa terinventarisasi.\nAda operator, SOP, tarif dan buku kas.\nData kebutuhan irigasi dan jalan tersedia.\nStudi kelayakan pengeringan selesai.", 1],
            ['2', 'Pembangunan adil', 'Pemetaan kebutuhan per dusun, skor prioritas, musyawarah, pelaksanaan dan pengawasan terbuka.', "Setiap dusun memiliki daftar usulan.\nUsulan memiliki skor dan status.\nRealisasi fisik dan keuangan dilaporkan.\nAda daftar pekerjaan tertunda.", 2],
            ['3', 'Pemuda berprestasi', 'Karang taruna per dusun, pembinaan sepak bola/voli/futsal, pasar rakyat dan kewirausahaan pemuda.', "Pengurus aktif dan program kerja tersedia.\nLatihan dan kegiatan rutin berjalan.\nAtlet dan prestasi tercatat.\nUMKM terlibat dalam pasar rakyat.", 3],
            ['4', 'Pendidikan masa depan', 'Database siswa terbatas, pemetaan kebutuhan, prestasi, minat, informasi beasiswa dan pendampingan.', "Data siswa terverifikasi dan aman.\nSiswa yang perlu bantuan teridentifikasi.\nPendampingan seleksi/beasiswa tercatat.\nEvaluasi pendidikan per semester.", 4],
            ['5', 'Pelayanan digital', 'Pengajuan surat online, nomor tiket, status proses, notifikasi, arsip, pengaduan dan jalur layanan offline.', "Portal dan SOP pelayanan aktif.\nPermohonan memiliki nomor tiket.\nWaktu layanan dipantau.\nData pribadi terlindungi.", 5],
            ['6', 'Mobil siaga', 'Kajian kebutuhan, koordinasi fasilitas kesehatan, biaya operasional, pengemudi, SOP dan pengadaan sesuai APBDes.', "Studi kebutuhan dan biaya tersedia.\nPengemudi dan SOP ditetapkan.\nAnggaran operasional tersedia.\nLog penggunaan dan perawatan tercatat.", 6],
        ];
        foreach ($rows as $r) $stmt->execute($r);
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM roadmap')->fetchColumn() === 0) {
        $stmt = $pdo->prepare('INSERT INTO roadmap(periode,judul,poin,urutan) VALUES (?,?,?,?)');
        $rows = [
            ['Tahun 1 · Fondasi', 'Konsolidasi', "Baseline seluruh dusun.\nTim dan tata kelola program.\nSOP pompanisasi.\nKarang taruna aktif.\nPortal pelayanan tahap awal.\nDatabase pendidikan aman.", 1],
            ['Tahun 2 · Implementasi', 'Pembangunan', "Prioritas jalan tani/irigasi.\nUji coba pengeringan/kemitraan.\nPembinaan olahraga.\nPendampingan beasiswa.\nPelayanan digital operasional.", 2],
            ['Tahun 3–4 · Ekspansi', 'Produktivitas', "Perluasan manfaat pertanian.\nPengembangan UMKM dan pasar.\nPembinaan atlet berjenjang.\nPenguatan pendampingan siswa.\nPerbaikan mutu layanan.", 3],
            ['Tahun 5–6 · Penguatan', 'Kemandirian', "Kelompok tani makin mandiri.\nPemeliharaan aset terencana.\nPemerataan antarwilayah.\nPenguatan organisasi pemuda.\nEvaluasi dampak program.", 4],
            ['Tahun 7 · Optimalisasi', 'Peningkatan mutu', "Perbaikan program yang tertinggal.\nStandarisasi SOP.\nPenguatan kapasitas pelaksana.\nAudit manfaat dan aset.", 5],
            ['Tahun 8 · Konsolidasi', 'Keberlanjutan', "Evaluasi akhir periode.\nPemeliharaan dan serah kelola.\nDokumentasi praktik baik.\nRekomendasi periode berikutnya.", 6],
            ['Lintas tahun', 'Program berkelanjutan', "Pelayanan administrasi.\nPemantauan pendidikan.\nPerawatan infrastruktur.\nTransparansi anggaran.", 7],
            ['Lintas tahun', 'Evaluasi rutin', "Rapat bulanan.\nRapor triwulan.\nEvaluasi semester.\nLaporan tahunan.", 8],
        ];
        foreach ($rows as $r) $stmt->execute($r);
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM year1_months')->fetchColumn() === 0) {
        $stmt = $pdo->prepare('INSERT INTO year1_months(bulan,judul,kegiatan,urutan) VALUES (?,?,?,?)');
        $rows = [
            ['Bulan 1', 'Konsolidasi', 'Rapat perangkat, BPD dan Kadus; pembagian peran; jadwal kerja; kanal aspirasi.', 1],
            ['Bulan 2', 'Pendataan', 'Survei dusun, pertanian, infrastruktur, siswa, pemuda, UMKM dan layanan.', 2],
            ['Bulan 3', 'Prioritas', 'Verifikasi usulan, skoring kebutuhan, musyawarah dan daftar prioritas.', 3],
            ['Bulan 4', 'Pertanian', 'Inventaris pompa, kondisi, operator, biaya, tarif dan rancangan SOP.', 4],
            ['Bulan 5', 'Kepemudaan', 'Aktivasi karang taruna, struktur per dusun, program dan kalender olahraga.', 5],
            ['Bulan 6', 'Digitalisasi', 'Portal surat tahap pertama, nomor tiket, notifikasi dan SOP pelayanan.', 6],
            ['Bulan 7', 'Pendidikan', 'Database siswa terbatas, identifikasi kebutuhan, prestasi dan minat.', 7],
            ['Bulan 8', 'Infrastruktur', 'Survei teknis jalan/irigasi, volume, desain, RAB dan kesiapan lahan.', 8],
            ['Bulan 9', 'Jagung', 'Survei volume panen, kadar air, biaya pengeringan, mitra dan pasar.', 9],
            ['Bulan 10', 'Olahraga/UMKM', 'Latihan rutin, kompetisi, pendampingan usaha dan persiapan pasar rakyat.', 10],
            ['Bulan 11', 'Mobil siaga', 'Analisis kebutuhan, opsi kendaraan, biaya total, pengemudi dan SOP.', 11],
            ['Bulan 12', 'Evaluasi', 'Verifikasi capaian, realisasi anggaran, kendala dan rencana tahun kedua.', 12],
        ];
        foreach ($rows as $r) $stmt->execute($r);
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM routines')->fetchColumn() === 0) {
        $stmt = $pdo->prepare('INSERT INTO routines(kegiatan,frekuensi,bukti,urutan) VALUES (?,?,?,?)');
        $rows = [
            ['Rapat koordinasi pemerintah desa', 'Bulanan', 'Undangan, daftar hadir, notulen, tindak lanjut', 1],
            ['Pemeriksaan administrasi dan keuangan', 'Bulanan sesuai siklus penatausahaan', 'Dokumen transaksi dan laporan sesuai ketentuan', 2],
            ['Pemantauan pompanisasi', 'Bulanan / sesuai musim tanam', 'Log penggunaan, perawatan, buku kas', 3],
            ['Pemantauan infrastruktur', 'Bulanan saat pekerjaan berjalan', 'Foto, volume, progres dan catatan mutu', 4],
            ['Kegiatan karang taruna', 'Minimal satu kegiatan bulanan (target)', 'Jadwal, peserta, dokumentasi, evaluasi', 5],
            ['Evaluasi layanan digital', 'Bulanan', 'Jumlah tiket, status, waktu penyelesaian', 6],
            ['Publikasi informasi program', 'Bulanan', 'Ringkasan capaian dan penggunaan anggaran yang layak publik', 7],
        ];
        foreach ($rows as $r) $stmt->execute($r);
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM quarterly')->fetchColumn() === 0) {
        $stmt = $pdo->prepare('INSERT INTO quarterly(kuartal,fokus,target,output,urutan) VALUES (?,?,?,?,?)');
        $rows = [
            ['Q1 · Bulan 1–3', 'Pemetaan', "Baseline dusun dan data sektoral tervalidasi.\nTim pelaksana dan PIC ditetapkan.\nDaftar prioritas dan masalah disepakati.\nIndikator serta kebutuhan biaya awal disusun.", 'Dokumen baseline dan daftar prioritas.', 1],
            ['Q2 · Bulan 4–6', 'Sistem', "SOP dan pengelola pompanisasi berjalan.\nKarang taruna terbentuk dan memiliki agenda.\nPortal layanan tahap awal diuji.\nDokumen teknis infrastruktur disiapkan.", 'Kelembagaan dan layanan mulai berfungsi.', 2],
            ['Q3 · Bulan 7–9', 'Pengembangan', "Database pendidikan dan skema pendampingan tersedia.\nPembinaan atlet mulai rutin.\nStudi kelayakan pengeringan jagung selesai.\nProgram infrastruktur siap sesuai anggaran.", 'Studi, kesiapan teknis dan program berjalan.', 3],
            ['Q4 · Bulan 10–12', 'Evaluasi', "Kegiatan olahraga dan UMKM berjalan.\nKeputusan investasi pengeringan didasarkan studi.\nKajian mobil siaga selesai.\nLaporan capaian dan rencana tahun berikutnya disusun.", 'Laporan tahunan dan rencana tindak lanjut.', 4],
        ];
        foreach ($rows as $r) $stmt->execute($r);
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM semester_eval')->fetchColumn() === 0) {
        $stmt = $pdo->prepare('INSERT INTO semester_eval(bidang,semester1,semester2,urutan) VALUES (?,?,?,?)');
        $rows = [
            ['Pertanian', 'Inventaris pompa, kelompok tani, SOP dan pembukuan.', 'Evaluasi layanan, kas, perawatan dan rencana pengembangan.', 1],
            ['Pembangunan', 'Peta kebutuhan, skor, survei dan dokumen teknis.', 'Realisasi pekerjaan sesuai APBDes dan kesiapan teknis.', 2],
            ['Pemuda', 'Organisasi, pelatih/relawan dan kalender kegiatan.', 'Evaluasi atlet, kompetisi, UMKM dan pasar rakyat.', 3],
            ['Pendidikan', 'Data dan identifikasi kebutuhan siswa.', 'Pendampingan dan evaluasi semester.', 4],
            ['Digital', 'Portal tahap awal dan SOP.', 'Statistik penggunaan, kualitas layanan dan perbaikan.', 5],
            ['Mobil siaga', 'Kajian kebutuhan dan biaya.', 'Keputusan pengadaan hanya bila layak dan anggaran tersedia.', 6],
        ];
        foreach ($rows as $r) $stmt->execute($r);
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM monitoring')->fetchColumn() === 0) {
        $stmt = $pdo->prepare('INSERT INTO monitoring(kode,program,lokasi,pic,target,anggaran,progres_fisik,realisasi_biaya,status,bukti,tindak_lanjut) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        $rows = [
            ['PRT-01', 'Pengelolaan pompanisasi', 'Diisi per lokasi', 'Diisi', 'Inventaris, SOP, log', 'Diisi APBDes/sumber sah', '0%', '0%', 'Belum dinilai', 'Foto, buku kas, log', 'Mulai inventarisasi pompa'],
            ['INF-01', 'Perbaikan jalan/irigasi', 'Diisi per dusun', 'Diisi', 'Volume sesuai dokumen', 'Diisi', '0%', '0%', 'Belum dinilai', 'RAB, foto, pemeriksaan', 'Survei teknis & prioritas dusun'],
            ['PEM-01', 'Aktivasi karang taruna', 'Desa/dusun', 'Diisi', 'Pengurus dan agenda aktif', 'Diisi', '0%', '0%', 'Belum dinilai', 'SK/berita acara, kegiatan', 'Pembentukan pengurus per dusun'],
            ['EDU-01', 'Pendampingan pendidikan', 'Desa', 'Diisi', 'Siswa sasaran terlayani', 'Diisi', '0%', '0%', 'Belum dinilai', 'Data akses terbatas', 'Penyusunan database siswa terbatas'],
            ['DIG-01', 'Portal pelayanan surat', 'Balai desa/online', 'Diisi', 'Tiket dan status aktif', 'Diisi', '0%', '0%', 'Belum dinilai', 'Log sistem, statistik', 'Rancang SOP & portal tahap awal'],
            ['MOB-01', 'Kajian mobil siaga', 'Desa', 'Diisi', 'Studi dan biaya total', 'Diisi', '0%', '0%', 'Belum dinilai', 'Dokumen kajian', 'Analisis kebutuhan & opsi kendaraan'],
        ];
        foreach ($rows as $r) $stmt->execute($r);
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM budget_notes')->fetchColumn() === 0) {
        $stmt = $pdo->prepare('INSERT INTO budget_notes(kategori,item,urutan) VALUES (?,?,?)');
        $rows = [
            ['Urutan penganggaran', 'Penuhi belanja wajib dan kewajiban pemerintahan desa.', 1],
            ['Urutan penganggaran', 'Pastikan pemeliharaan aset/fasilitas yang ada.', 2],
            ['Urutan penganggaran', 'Prioritaskan kegiatan berdampak luas dan mendesak.', 3],
            ['Urutan penganggaran', 'Alokasikan pemberdayaan dengan output terukur.', 4],
            ['Urutan penganggaran', 'Lakukan investasi baru setelah studi kelayakan.', 5],
            ['Sumber dana (perlu diverifikasi)', 'PADes dan hasil pengelolaan aset sesuai aturan.', 1],
            ['Sumber dana (perlu diverifikasi)', 'Dana Desa dan Alokasi Dana Desa sesuai peruntukan.', 2],
            ['Sumber dana (perlu diverifikasi)', 'Bantuan keuangan kabupaten/provinsi/pusat.', 3],
            ['Sumber dana (perlu diverifikasi)', 'Program sektoral pertanian dan pendidikan.', 4],
            ['Sumber dana (perlu diverifikasi)', 'Kemitraan, CSR, sponsor dan swadaya yang sah.', 5],
            ['Catatan khusus', 'Tanah bengkok: pastikan status tanah, mekanisme lelang/sewa, pencatatan pendapatan, dan penganggarannya benar. Hasil lelang tidak boleh langsung dibelanjakan di luar mekanisme keuangan desa.', 1],
            ['Catatan khusus', 'Mobil siaga: pengadaan harus masuk dokumen perencanaan dan APBDes, memenuhi aturan pengadaan/aset, serta disertai anggaran BBM, servis, pajak, pengemudi dan SOP.', 2],
        ];
        foreach ($rows as $r) $stmt->execute($r);
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM checklist')->fetchColumn() === 0) {
        $stmt = $pdo->prepare('INSERT INTO checklist(tahap,item,selesai,urutan) VALUES (?,?,?,?)');
        $rows = [
            ['Sebelum pelantikan / persiapan', 'Susun visi-misi menjadi program terukur.', 0, 1],
            ['Sebelum pelantikan / persiapan', 'Kumpulkan data publik dan dokumen perencanaan desa yang tersedia secara sah.', 0, 2],
            ['Sebelum pelantikan / persiapan', 'Temui kelompok tani, pemuda, sekolah, UMKM dan tokoh masyarakat.', 0, 3],
            ['Sebelum pelantikan / persiapan', 'Siapkan daftar data yang perlu divalidasi per dusun.', 0, 4],
            ['Sebelum pelantikan / persiapan', 'Susun rancangan indikator dan format monitoring.', 0, 5],
            ['Hari 1–30', 'Koordinasi perangkat desa, BPD dan kepala dusun.', 0, 1],
            ['Hari 1–30', 'Tetapkan alur koordinasi, jadwal rapat dan penanggung jawab.', 0, 2],
            ['Hari 1–30', 'Buka kanal aspirasi dan pengaduan warga.', 0, 3],
            ['Hari 31–60', 'Lakukan pendataan pertanian, infrastruktur, pendidikan, pemuda dan UMKM.', 0, 1],
            ['Hari 31–60', 'Verifikasi kondisi pompa, irigasi dan jalan usaha tani.', 0, 2],
            ['Hari 31–60', 'Inventarisasi kebutuhan layanan dan kesiapan digital.', 0, 3],
            ['Hari 61–90', 'Musyawarahkan dan skor usulan pembangunan.', 0, 1],
            ['Hari 61–90', 'Tetapkan daftar prioritas dan kegiatan yang siap dianggarkan.', 0, 2],
            ['Hari 61–90', 'Terbitkan ringkasan baseline dan rencana tindak lanjut untuk warga.', 0, 3],
        ];
        foreach ($rows as $r) $stmt->execute($r);
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM notes')->fetchColumn() === 0) {
        $stmt = $pdo->prepare('INSERT INTO notes(judul,isi,urutan) VALUES (?,?,?)');
        $rows = [
            ['Penting: status dokumen', 'HTML/rancangan ini merupakan konsep dan alat kerja awal, bukan dokumen APBDes atau RPJM Desa yang telah disahkan. Program final harus disesuaikan dengan kewenangan desa, hasil musyawarah, RPJM Desa, RKP Desa, APBDes, ketentuan penggunaan sumber dana, dan regulasi terbaru Kabupaten Grobogan.', 1],
            ['Perencanaan dan masa jabatan', 'UU Nomor 3 Tahun 2024 mengubah ketentuan masa jabatan kepala desa menjadi delapan tahun. Periode kalender 2027–2034 adalah contoh. Tanggal dan tahapan perencanaan harus mengikuti tanggal pelantikan, ketentuan transisi, serta arahan pemerintah kabupaten.', 2],
            ['Keuangan dan aset desa', 'Gunakan prinsip tertib, transparan, akuntabel, partisipatif serta disiplin anggaran. Pendapatan dari pengelolaan aset desa harus dicatat dan dibelanjakan melalui mekanisme keuangan desa yang berlaku. Pastikan status aset pompa dan kendaraan jelas.', 3],
            ['Perlindungan data pendidikan', 'Nilai rapor, identitas anak dan kondisi keluarga bukan materi dashboard publik. Batasi akses, tetapkan tujuan pengumpulan, persetujuan yang diperlukan, keamanan penyimpanan, retensi dan prosedur koreksi data.', 4],
            ['Pengadaan dan pembangunan fisik', 'Setiap pekerjaan perlu dasar kewenangan, dokumen teknis, RAB, standar mutu, proses pengadaan yang berlaku, pengawasan, dokumentasi dan berita acara pemeriksaan.', 5],
            ['Referensi awal untuk verifikasi', "UU Nomor 6 Tahun 2014 tentang Desa sebagaimana diubah, terakhir dengan UU Nomor 3 Tahun 2024.\nPermendagri Nomor 20 Tahun 2018 tentang Pengelolaan Keuangan Desa.\nPeraturan teknis penyusunan RPJM Desa dan RKP Desa yang berlaku.\nPeraturan Bupati Grobogan tentang perencanaan dan pengelolaan keuangan desa, setelah diperiksa status dan perubahan terbarunya.", 6],
        ];
        foreach ($rows as $r) $stmt->execute($r);
    }
}

function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function require_login(): void {
    if (empty($_SESSION['logged_in'])) {
        header('Location: login.php');
        exit;
    }
}

function flash_set(string $msg, string $type = 'ok'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function flash_get(): ?array {
    if (!isset($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

function post(string $key, string $default = ''): string {
    return trim((string)($_POST[$key] ?? $default));
}

function get_setting(PDO $pdo, string $key, string $default = ''): string {
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE `key`=?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? (string)$row['value'] : $default;
}

function set_setting(PDO $pdo, string $key, string $value): void {
    $stmt = $pdo->prepare('INSERT INTO settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
    $stmt->execute([$key, $value]);
}
?>
