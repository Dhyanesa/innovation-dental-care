
<?php
session_start();

// Memastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// Koneksi database
require_once __DIR__ . "/../../config/database.php";

// Mengambil daftar pasien aktif
$query_pasien = "SELECT
                    pasien.id_pasien,
                    users.nama_lengkap
                 FROM pasien
                 INNER JOIN users ON pasien.id_user = users.id_user
                 WHERE pasien.status_pasien = 'aktif'
                 ORDER BY users.nama_lengkap ASC";

$result_pasien = mysqli_query($conn, $query_pasien);

if (!$result_pasien) {
    die("Gagal mengambil data pasien: " . mysqli_error($conn));
}

// Mengambil dokter aktif untuk pemilihan otomatis
$query_dokter = "SELECT id_dokter, nama_dokter, hari_praktik
                 FROM dokter
                 WHERE status_dokter = 'aktif'";

$result_dokter = mysqli_query($conn, $query_dokter);

if (!$result_dokter) {
    die("Gagal mengambil data dokter: " . mysqli_error($conn));
}

// Membuat daftar dokter berdasarkan hari praktik
$dokter_per_hari = [];

while ($dokter = mysqli_fetch_assoc($result_dokter)) {
    $dokter_per_hari[$dokter['hari_praktik']] = [
        'id' => (int) $dokter['id_dokter'],
        'nama' => $dokter['nama_dokter']
    ];
}

$pesan = "";

$id_pasien = "";
$tanggal_kontrol = "";
$jam_mulai = "18:00";
$jam_selesai = "21:00";

// Proses penyimpanan jadwal
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id_pasien = (int) ($_POST['id_pasien'] ?? 0);
    $tanggal_kontrol = $_POST['tanggal_kontrol'] ?? "";
    $jam_mulai = $_POST['jam_mulai'] ?? "";
    $jam_selesai = $_POST['jam_selesai'] ?? "";

    $nama_hari = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu'
    ];

    if (
        $id_pasien <= 0 ||
        $tanggal_kontrol === "" ||
        $jam_mulai === "" ||
        $jam_selesai === ""
    ) {
        $pesan = "Semua data wajib diisi.";
    } else {

        $tanggal_obj = DateTime::createFromFormat(
            '!Y-m-d',
            $tanggal_kontrol
        );

        if (
            !$tanggal_obj ||
            $tanggal_obj->format('Y-m-d') !== $tanggal_kontrol
        ) {
            $pesan = "Tanggal kontrol tidak valid.";
        } elseif ($tanggal_kontrol < date('Y-m-d')) {
            $pesan = "Tanggal kontrol tidak boleh sebelum hari ini.";
        } else {

            $hari = $nama_hari[(int) $tanggal_obj->format('N')];

            // Memeriksa format dan rentang jam
            $format_jam_valid =
                preg_match('/^\d{2}:\d{2}$/', $jam_mulai) &&
                preg_match('/^\d{2}:\d{2}$/', $jam_selesai);

            if ($hari === 'Minggu') {
                $pesan = "Klinik tutup pada hari Minggu.";
            } elseif (
                !$format_jam_valid ||
                $jam_mulai < '18:00' ||
                $jam_selesai > '21:00' ||
                $jam_mulai >= $jam_selesai
            ) {
                $pesan = "Rentang waktu harus berada antara pukul 18.00–21.00 dan jam selesai harus lebih besar dari jam mulai.";
            } else {

                // Memeriksa pasien aktif
                $cek_pasien = mysqli_prepare(
                    $conn,
                    "SELECT id_pasien
                     FROM pasien
                     WHERE id_pasien = ?
                     AND status_pasien = 'aktif'"
                );

                mysqli_stmt_bind_param(
                    $cek_pasien,
                    "i",
                    $id_pasien
                );

                mysqli_stmt_execute($cek_pasien);
                $hasil_pasien = mysqli_stmt_get_result($cek_pasien);

                if (mysqli_num_rows($hasil_pasien) === 0) {
                    $pesan = "Pasien tidak ditemukan atau sudah tidak aktif.";
                } else {

                    // Menentukan dokter otomatis berdasarkan hari
                    $cek_dokter = mysqli_prepare(
                        $conn,
                        "SELECT id_dokter
                         FROM dokter
                         WHERE hari_praktik = ?
                         AND status_dokter = 'aktif'
                         LIMIT 1"
                    );

                    mysqli_stmt_bind_param(
                        $cek_dokter,
                        "s",
                        $hari
                    );

                    mysqli_stmt_execute($cek_dokter);
                    $hasil_dokter = mysqli_stmt_get_result($cek_dokter);

                    $data_dokter = mysqli_fetch_assoc($hasil_dokter);

                    if (!$data_dokter) {
                        $pesan = "Dokter untuk hari tersebut belum tersedia.";
                    } else {

                        $id_dokter = (int) $data_dokter['id_dokter'];

                        // Menyimpan jadwal kontrol
                        $query_simpan = "INSERT INTO jadwal_kontrol
                                        (
                                            id_pasien,
                                            id_dokter,
                                            tanggal_kontrol,
                                            jam_mulai,
                                            jam_selesai
                                        )
                                        VALUES (?, ?, ?, ?, ?)";

                        $stmt = mysqli_prepare(
                            $conn,
                            $query_simpan
                        );

                        mysqli_stmt_bind_param(
                            $stmt,
                            "iisss",
                            $id_pasien,
                            $id_dokter,
                            $tanggal_kontrol,
                            $jam_mulai,
                            $jam_selesai
                        );

                        if (mysqli_stmt_execute($stmt)) {
                            echo "<script>
                                alert('Jadwal kontrol berhasil ditambahkan!');
                                window.location.href = 'index.php';
                            </script>";
                            exit;
                        } else {
                            $pesan = "Gagal menyimpan jadwal kontrol.";
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tambah Jadwal Kontrol - Innovation Dental Care</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/innovation_dental_care/assets/css/style.css?v=3">

    <style>
        .schedule-form-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(70, 100, 130, 0.06);
        }

        .schedule-form-heading {
            margin-bottom: 25px;
        }

        .schedule-form-heading h3 {
            color: #34445c;
            font-size: 17px;
            margin-bottom: 6px;
        }

        .schedule-form-heading p {
            color: #8a98a9;
            font-size: 11px;
        }

        .schedule-info {
            background: linear-gradient(115deg, #e5f1ff, #e4f7ed);
            border: 1px solid #dceee9;
            border-radius: 13px;
            padding: 15px 18px;
            margin-bottom: 25px;
            color: #597b83;
            font-size: 11px;
            line-height: 1.8;
        }

        .schedule-info strong {
            color: #477c80;
        }

        .schedule-error {
            background: #fff0f2;
            color: #c66c80;
            border: 1px solid #f8dce3;
            padding: 13px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 12px;
        }

        .schedule-form-group {
            margin-bottom: 20px;
        }

        .schedule-form-group label {
            display: block;
            color: #526176;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 9px;
        }

        .schedule-form-group input,
        .schedule-form-group select {
            display: block;
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #e2eaf0;
            border-radius: 11px;
            background: #fbfdff;
            color: #526176;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            outline: none;
            box-sizing: border-box;
            transition: 0.25s;
        }

        .schedule-form-group input:focus,
        .schedule-form-group select:focus {
            border-color: #91c9d6;
            box-shadow: 0 0 0 3px rgba(145, 201, 214, 0.15);
        }

        .schedule-doctor-info {
            background: linear-gradient(115deg, #f0f8ff, #eefaf3);
            border: 1px solid #e0eee9;
            color: #718b91;
            padding: 15px;
            border-radius: 12px;
            font-size: 11px;
            line-height: 1.7;
        }

        .schedule-doctor-info strong {
            display: block;
            color: #4c8e86;
            font-size: 13px;
            margin-top: 5px;
        }

        .schedule-form-actions {
            display: flex;
            gap: 12px;
            margin-top: 28px;
            flex-wrap: wrap;
        }

        .schedule-save-button,
        .schedule-back-button {
            display: inline-block;
            padding: 12px 22px;
            border: none;
            border-radius: 11px;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: 0.25s;
        }

        .schedule-save-button {
            background: linear-gradient(120deg, #80bce2, #83d2b2);
            color: white;
            box-shadow: 0 5px 12px rgba(100, 170, 180, 0.15);
        }

        .schedule-save-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(100, 170, 180, 0.25);
        }

        .schedule-back-button {
            background: #f0f3f7;
            color: #758397;
        }

        .schedule-back-button:hover {
            background: #e5eaf0;
        }

        @media (max-width: 768px) {
            .schedule-form-card {
                padding: 20px;
            }

            .schedule-form-actions {
                flex-direction: column;
            }

            .schedule-save-button,
            .schedule-back-button {
                width: 100%;
                text-align: center;
                box-sizing: border-box;
            }
        }
    </style>
</head>

<body class="admin-page">

<div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="admin-sidebar">

        <a href="../dashboard.php" class="admin-brand">
            <img src="../../assets/images/Logo-IDC.png" alt="Logo Klinik">
            <span>INNOVATION<br>DENTAL CARE</span>
        </a>

        <div class="sidebar-label">MENU UTAMA</div>

        <nav class="admin-menu">
            <a href="../dashboard.php">
                <span>▦</span> Dashboard
            </a>

            <a href="../pasien/index.php">
                <span>♙</span> Data Pasien
            </a>

            <a href="../dokter/index.php">
                <span>✚</span> Data Dokter
            </a>

            <a href="index.php" class="active">
                <span>▣</span> Jadwal Kontrol
            </a>

            <a href="../pengajuan/index.php">
                <span>↻</span> Pengajuan Perubahan
            </a>
        </nav>

        <div class="sidebar-bottom">
            <a href="../../auth/logout.php"
               onclick="return confirm('Apakah kamu yakin ingin logout?');">
                <span>↪</span> Logout
            </a>
        </div>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="admin-main">

        <header class="admin-topbar">
            <div>
                <span class="admin-greeting">ADMINISTRATOR PANEL</span>
                <h2>Tambah Jadwal Kontrol</h2>
            </div>

            <div class="admin-profile">
                <div class="profile-icon">A</div>
                <div>
                    <strong><?= htmlspecialchars($_SESSION['nama_lengkap']); ?></strong>
                    <small>Administrator</small>
                </div>
            </div>
        </header>

        <!-- WELCOME -->
        <section class="admin-welcome patient-welcome">
            <div>
                <span>PENGELOLAAN JADWAL KONTROL</span>
                <h1>Tambah Jadwal Baru</h1>
                <p>Atur jadwal kunjungan kontrol pasien Innovation Dental Care.</p>
            </div>

            <div class="welcome-decoration">🦷</div>
        </section>

        <!-- FORM -->
        <section class="schedule-form-card">

            <div class="schedule-form-heading">
                <h3>Form Tambah Jadwal</h3>
                <p>Lengkapi informasi berikut untuk membuat jadwal kontrol pasien.</p>
            </div>

            <div class="schedule-info">
                <strong>Informasi Jam Operasional Klinik</strong><br>
                Klinik beroperasi Senin–Sabtu pukul 18.00–21.00 WIB.
                Dokter akan ditentukan otomatis berdasarkan hari kontrol.
            </div>

            <?php if ($pesan !== "") : ?>
                <div class="schedule-error">
                    <?= htmlspecialchars($pesan); ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="schedule-form-group">
                    <label for="id_pasien">Nama Pasien</label>

                    <select name="id_pasien" id="id_pasien" required>
                        <option value="">-- Pilih Pasien --</option>

                        <?php while ($pasien = mysqli_fetch_assoc($result_pasien)) : ?>
                            <option
                                value="<?= (int) $pasien['id_pasien']; ?>"
                                <?= $id_pasien == $pasien['id_pasien'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($pasien['nama_lengkap']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="schedule-form-group">
                    <label for="tanggal_kontrol">Tanggal Kontrol</label>

                    <input
                        type="date"
                        id="tanggal_kontrol"
                        name="tanggal_kontrol"
                        min="<?= date('Y-m-d'); ?>"
                        value="<?= htmlspecialchars($tanggal_kontrol); ?>"
                        required>
                </div>

                <div class="schedule-form-group">
                    <label>Dokter yang Bertugas</label>

                    <div class="schedule-doctor-info">
                        Dokter akan ditentukan otomatis berdasarkan tanggal kontrol.
                        <strong id="nama_dokter">Pilih tanggal kontrol terlebih dahulu.</strong>
                    </div>
                </div>

                <div class="schedule-form-group">
                    <label for="jam_mulai">Jam Mulai Kunjungan</label>

                    <input
                        type="time"
                        id="jam_mulai"
                        name="jam_mulai"
                        min="18:00"
                        max="21:00"
                        value="<?= htmlspecialchars($jam_mulai); ?>"
                        required>
                </div>

                <div class="schedule-form-group">
                    <label for="jam_selesai">Jam Selesai Kunjungan</label>

                    <input
                        type="time"
                        id="jam_selesai"
                        name="jam_selesai"
                        min="18:00"
                        max="21:00"
                        value="<?= htmlspecialchars($jam_selesai); ?>"
                        required>
                </div>

                <div class="schedule-form-actions">
                    <button type="submit" class="schedule-save-button">
                        Simpan Jadwal
                    </button>

                    <a href="index.php" class="schedule-back-button">
                        Kembali
                    </a>
                </div>

            </form>

        </section>

        <footer class="admin-footer">
            © <?= date('Y'); ?> Innovation Dental Care Pontianak
        </footer>

    </main>

</div>

<script>
    // Daftar dokter dari database
    const dokterPerHari = <?= json_encode(
        $dokter_per_hari,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ); ?>;

    const inputTanggal = document.getElementById('tanggal_kontrol');
    const namaDokter = document.getElementById('nama_dokter');

    // Nama hari berdasarkan JavaScript
    const namaHari = [
        'Minggu',
        'Senin',
        'Selasa',
        'Rabu',
        'Kamis',
        'Jumat',
        'Sabtu'
    ];

    function tampilkanDokter() {
        const tanggal = inputTanggal.value;

        if (!tanggal) {
            namaDokter.textContent = 'Pilih tanggal kontrol terlebih dahulu.';
            return;
        }

        const tanggalDipilih = new Date(tanggal + 'T00:00:00');
        const hari = namaHari[tanggalDipilih.getDay()];

        if (hari === 'Minggu') {
            namaDokter.textContent = 'Klinik tutup pada hari Minggu.';
            return;
        }

        const dokter = dokterPerHari[hari];

        if (dokter) {
            namaDokter.textContent = dokter.nama + ' (' + hari + ')';
        } else {
            namaDokter.textContent = 'Dokter untuk hari tersebut belum tersedia.';
        }
    }

    inputTanggal.addEventListener('change', tampilkanDokter);

    // Menampilkan dokter jika tanggal sudah terisi
    tampilkanDokter();
</script>

</body>
</html>
