
<?php
session_start();

// Cek hak akses admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

require_once __DIR__ . "/../../config/database.php";

// Ambil ID jadwal
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_jadwal = (int) $_GET['id'];
$pesan = "";

// Ambil data jadwal
$query = mysqli_query($conn, "
    SELECT
        jadwal_kontrol.*,
        users.nama_lengkap,
        dokter.nama_dokter
    FROM jadwal_kontrol
    JOIN pasien ON jadwal_kontrol.id_pasien = pasien.id_pasien
    JOIN users ON pasien.id_user = users.id_user
    JOIN dokter ON jadwal_kontrol.id_dokter = dokter.id_dokter
    WHERE jadwal_kontrol.id_jadwal = $id_jadwal
");

if (!$query || mysqli_num_rows($query) == 0) {
    die("Jadwal kontrol tidak ditemukan.");
}

$data = mysqli_fetch_assoc($query);

// Ambil daftar dokter aktif berdasarkan hari praktik
$dokter_per_hari = [];

$query_dokter = mysqli_query($conn, "
    SELECT nama_dokter, hari_praktik
    FROM dokter
    WHERE status_dokter = 'aktif'
");

while ($dokter = mysqli_fetch_assoc($query_dokter)) {
    $dokter_per_hari[$dokter['hari_praktik']] = $dokter['nama_dokter'];
}

// Proses ketika tombol simpan ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tanggal = $_POST['tanggal_kontrol'] ?? "";
    $jam_mulai = $_POST['jam_mulai'] ?? "";
    $jam_selesai = $_POST['jam_selesai'] ?? "";

    $hari = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu'
    ];

    $tanggal_obj = DateTime::createFromFormat('!Y-m-d', $tanggal);

    $format_jam_valid =
        preg_match('/^\d{2}:\d{2}$/', $jam_mulai) &&
        preg_match('/^\d{2}:\d{2}$/', $jam_selesai);

    if ($tanggal === "" || $jam_mulai === "" || $jam_selesai === "") {
        $pesan = "Semua kolom wajib diisi.";
    } elseif (
        !$tanggal_obj ||
        $tanggal_obj->format('Y-m-d') !== $tanggal
    ) {
        $pesan = "Tanggal tidak valid.";
    } elseif ($tanggal < date('Y-m-d')) {
        $pesan = "Tanggal kontrol tidak boleh sebelum hari ini.";
    } else {

        $nama_hari = $hari[(int) $tanggal_obj->format('N')];

        if ($nama_hari === 'Minggu') {
            $pesan = "Klinik tutup pada hari Minggu.";
        } elseif (
            !$format_jam_valid ||
            $jam_mulai < '18:00' ||
            $jam_selesai > '21:00' ||
            $jam_mulai >= $jam_selesai
        ) {
            $pesan = "Jam kontrol harus antara 18.00 sampai 21.00 dan jam selesai harus setelah jam mulai.";
        } else {

            // Cari dokter otomatis berdasarkan hari
            $stmt_dokter = mysqli_prepare($conn, "
                SELECT id_dokter
                FROM dokter
                WHERE hari_praktik = ?
                AND status_dokter = 'aktif'
                LIMIT 1
            ");

            mysqli_stmt_bind_param($stmt_dokter, "s", $nama_hari);
            mysqli_stmt_execute($stmt_dokter);

            $hasil_dokter = mysqli_stmt_get_result($stmt_dokter);
            $data_dokter = mysqli_fetch_assoc($hasil_dokter);

            if (!$data_dokter) {
                $pesan = "Dokter tidak tersedia pada hari tersebut.";
            } else {

                $id_dokter = (int) $data_dokter['id_dokter'];

                $stmt_update = mysqli_prepare($conn, "
                    UPDATE jadwal_kontrol
                    SET
                        id_dokter = ?,
                        tanggal_kontrol = ?,
                        jam_mulai = ?,
                        jam_selesai = ?
                    WHERE id_jadwal = ?
                ");

                mysqli_stmt_bind_param(
                    $stmt_update,
                    "isssi",
                    $id_dokter,
                    $tanggal,
                    $jam_mulai,
                    $jam_selesai,
                    $id_jadwal
                );

                if (mysqli_stmt_execute($stmt_update)) {
                    echo "<script>
                        alert('Jadwal kontrol berhasil diperbarui!');
                        window.location.href = 'index.php';
                    </script>";
                    exit;
                } else {
                    $pesan = "Gagal memperbarui jadwal: " . mysqli_error($conn);
                }
            }
        }
    }

    // Perbarui data tampilan setelah input
    $data['tanggal_kontrol'] = $tanggal;
    $data['jam_mulai'] = $jam_mulai;
    $data['jam_selesai'] = $jam_selesai;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Jadwal Kontrol - Innovation Dental Care</title>

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

        .schedule-form-group input {
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

        .schedule-form-group input:focus {
            border-color: #91c9d6;
            box-shadow: 0 0 0 3px rgba(145, 201, 214, 0.15);
        }

        .schedule-form-group input[readonly] {
            background: #f4f9f8;
            color: #568b84;
            font-weight: 600;
            cursor: default;
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
                <h2>Edit Jadwal Kontrol</h2>
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
                <h1>Edit Jadwal Kontrol</h1>
                <p>Perbarui informasi jadwal kunjungan pasien Innovation Dental Care.</p>
            </div>

            <div class="welcome-decoration">🦷</div>
        </section>

        <!-- FORM -->
        <section class="schedule-form-card">

            <div class="schedule-form-heading">
                <h3>Form Edit Jadwal</h3>
                <p>Periksa dan sesuaikan informasi jadwal kontrol pasien.</p>
            </div>

            <div class="schedule-info">
                <strong>Informasi Pasien</strong><br>
                Nama pasien:
                <?= htmlspecialchars($data['nama_lengkap']); ?>
                <br>
                Klinik beroperasi Senin–Sabtu pukul 18.00–21.00 WIB.
                Dokter akan mengikuti hari praktik berdasarkan tanggal kontrol.
            </div>

            <?php if ($pesan !== "") : ?>
                <div class="schedule-error">
                    <?= htmlspecialchars($pesan); ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="schedule-form-group">
                    <label for="tanggal_kontrol">Tanggal Kontrol</label>

                    <input
                        type="date"
                        name="tanggal_kontrol"
                        id="tanggal_kontrol"
                        value="<?= htmlspecialchars($data['tanggal_kontrol']); ?>"
                        min="<?= date('Y-m-d'); ?>"
                        required>
                </div>

                <div class="schedule-form-group">
                    <label for="nama_dokter">Dokter yang Bertugas</label>

                    <input
                        type="text"
                        id="nama_dokter"
                        value="<?= htmlspecialchars($data['nama_dokter']); ?>"
                        readonly>
                </div>

                <div class="schedule-form-group">
                    <label for="jam_mulai">Jam Mulai Kunjungan</label>

                    <input
                        type="time"
                        name="jam_mulai"
                        id="jam_mulai"
                        value="<?= htmlspecialchars(substr($data['jam_mulai'], 0, 5)); ?>"
                        min="18:00"
                        max="21:00"
                        required>
                </div>

                <div class="schedule-form-group">
                    <label for="jam_selesai">Jam Selesai Kunjungan</label>

                    <input
                        type="time"
                        name="jam_selesai"
                        id="jam_selesai"
                        value="<?= htmlspecialchars(substr($data['jam_selesai'], 0, 5)); ?>"
                        min="18:00"
                        max="21:00"
                        required>
                </div>

                <div class="schedule-form-actions">
                    <button type="submit" class="schedule-save-button">
                        Simpan Perubahan
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
    const dokterPerHari = <?= json_encode(
        $dokter_per_hari,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ); ?>;

    const inputTanggal = document.getElementById('tanggal_kontrol');
    const inputDokter = document.getElementById('nama_dokter');

    function tampilkanDokter() {
        if (!inputTanggal.value) {
            inputDokter.value = "";
            return;
        }

        const tanggal = new Date(inputTanggal.value + 'T00:00:00');

        const hari = [
            'Minggu',
            'Senin',
            'Selasa',
            'Rabu',
            'Kamis',
            'Jumat',
            'Sabtu'
        ];

        const namaHari = hari[tanggal.getDay()];

        if (namaHari === 'Minggu') {
            inputDokter.value = "Klinik tutup";
        } else {
            inputDokter.value =
                dokterPerHari[namaHari] || "Dokter tidak tersedia";
        }
    }

    inputTanggal.addEventListener('change', tampilkanDokter);

    // Menampilkan dokter sesuai tanggal awal
    tampilkanDokter();
</script>

</body>
</html>
