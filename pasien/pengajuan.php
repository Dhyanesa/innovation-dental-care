<?php
session_start();

// Memastikan pasien sudah login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pasien') {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

date_default_timezone_set('Asia/Jakarta');

$id_user = (int) $_SESSION['id_user'];
$pesan = "";
$jenis_pesan = "";

// Mengambil ID pasien
$stmt_pasien = mysqli_prepare($conn, "
    SELECT id_pasien
    FROM pasien
    WHERE id_user = ?
");

mysqli_stmt_bind_param($stmt_pasien, "i", $id_user);
mysqli_stmt_execute($stmt_pasien);

$result_pasien = mysqli_stmt_get_result($stmt_pasien);
$data_pasien = mysqli_fetch_assoc($result_pasien);

if (!$data_pasien) {
    die("Data pasien tidak ditemukan.");
}

$id_pasien = (int) $data_pasien['id_pasien'];

// Mengambil ID jadwal dari URL
$id_jadwal = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_jadwal <= 0) {
    die("ID jadwal tidak valid.");
}

// Memastikan jadwal milik pasien dan masih terjadwal
$stmt_cek = mysqli_prepare($conn, "
    SELECT *
    FROM jadwal_kontrol
    WHERE id_jadwal = ?
    AND id_pasien = ?
    AND status_kontrol = 'terjadwal'
");

mysqli_stmt_bind_param($stmt_cek, "ii", $id_jadwal, $id_pasien);
mysqli_stmt_execute($stmt_cek);

$result_cek = mysqli_stmt_get_result($stmt_cek);
$data_jadwal = mysqli_fetch_assoc($result_cek);

if (!$data_jadwal) {
    die("Jadwal tidak ditemukan atau tidak dapat diubah.");
}

// Memproses pengajuan perubahan jadwal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tanggal_baru = $_POST['tanggal_baru'] ?? '';
    $jam_mulai_baru = $_POST['jam_mulai_baru'] ?? '';
    $jam_selesai_baru = $_POST['jam_selesai_baru'] ?? '';
    $alasan = trim($_POST['alasan'] ?? '');

    // Validasi tanggal
    if (empty($tanggal_baru) || empty($jam_mulai_baru) || empty($jam_selesai_baru) || empty($alasan)) {
        $pesan = "Semua kolom wajib diisi.";
        $jenis_pesan = "error";

    } elseif (strtotime($tanggal_baru) < strtotime(date('Y-m-d'))) {
        $pesan = "Tanggal pengajuan tidak boleh sebelum hari ini.";
        $jenis_pesan = "error";

    } elseif (date('N', strtotime($tanggal_baru)) == 7) {
        $pesan = "Klinik tutup pada hari Minggu.";
        $jenis_pesan = "error";

    } elseif ($jam_mulai_baru < '18:00' || $jam_selesai_baru > '21:00') {
        $pesan = "Jam pengajuan harus berada dalam jam operasional klinik, yaitu 18.00-21.00.";
        $jenis_pesan = "error";

    } elseif ($jam_mulai_baru >= $jam_selesai_baru) {
        $pesan = "Jam selesai harus lebih besar dari jam mulai.";
        $jenis_pesan = "error";

    } elseif (strlen($alasan) > 10000) {
        $pesan = "Alasan pengajuan terlalu panjang.";
        $jenis_pesan = "error";

    } else {

        // Memeriksa apakah sudah ada pengajuan yang menunggu
        $stmt_cek_pengajuan = mysqli_prepare($conn, "
            SELECT id_pengajuan
            FROM pengajuan_perubahan
            WHERE id_jadwal = ?
            AND status_pengajuan = 'menunggu'
        ");

        mysqli_stmt_bind_param($stmt_cek_pengajuan, "i", $id_jadwal);
        mysqli_stmt_execute($stmt_cek_pengajuan);

        $result_pengajuan = mysqli_stmt_get_result($stmt_cek_pengajuan);

        if (mysqli_num_rows($result_pengajuan) > 0) {
            $pesan = "Pengajuan perubahan jadwal untuk jadwal ini masih menunggu keputusan admin.";
            $jenis_pesan = "error";

        } else {

            mysqli_begin_transaction($conn);

            try {

                // Menyimpan pengajuan perubahan jadwal
                $stmt_insert = mysqli_prepare($conn, "
                    INSERT INTO pengajuan_perubahan
                    (
                        id_jadwal,
                        tanggal_baru,
                        jam_mulai_baru,
                        jam_selesai_baru,
                        alasan,
                        status_pengajuan
                    )
                    VALUES (?, ?, ?, ?, ?, 'menunggu')
                ");

                mysqli_stmt_bind_param(
                    $stmt_insert,
                    "issss",
                    $id_jadwal,
                    $tanggal_baru,
                    $jam_mulai_baru,
                    $jam_selesai_baru,
                    $alasan
                );

                if (!mysqli_stmt_execute($stmt_insert)) {
                    throw new Exception("Gagal menyimpan pengajuan perubahan jadwal.");
                }

                // Mengubah status konfirmasi jadwal
                $stmt_update = mysqli_prepare($conn, "
                    UPDATE jadwal_kontrol
                    SET status_konfirmasi = 'mengajukan_perubahan'
                    WHERE id_jadwal = ?
                    AND id_pasien = ?
                    AND status_kontrol = 'terjadwal'
                ");

                mysqli_stmt_bind_param(
                    $stmt_update,
                    "ii",
                    $id_jadwal,
                    $id_pasien
                );

                if (!mysqli_stmt_execute($stmt_update) || mysqli_stmt_affected_rows($stmt_update) === 0) {
                    throw new Exception("Gagal memperbarui status jadwal.");
                }

                mysqli_commit($conn);

                header("Location: jadwal.php?status=pengajuan");
                exit;

            } catch (Exception $e) {

                mysqli_rollback($conn);

                $pesan = $e->getMessage();
                $jenis_pesan = "error";
            }
        }

        mysqli_stmt_close($stmt_cek_pengajuan);
    }
}

$nama_pasien = $_SESSION['nama_lengkap'] ?? 'Pasien';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pengajuan Perubahan Jadwal - Innovation Dental Care</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f8fc;
            color: #40536a;
        }

        /* LAYOUT */
        .patient-sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #ffffff;
            padding: 28px 18px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #edf1f5;
            z-index: 10;
        }

        .patient-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            text-decoration: none;
            color: #40536a;
            padding: 0 5px;
            margin-bottom: 42px;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.5;
            letter-spacing: 0.4px;
        }

        .patient-brand img {
            width: 43px;
            height: 43px;
            object-fit: contain;
        }

        .sidebar-label {
            font-size: 10px;
            font-weight: 700;
            color: #a0adbb;
            letter-spacing: 1.2px;
            padding: 0 12px;
            margin-bottom: 13px;
        }

        .patient-menu {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .patient-menu a {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 14px;
            border-radius: 12px;
            color: #78899c;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: 0.2s;
        }

        .patient-menu a span {
            width: 20px;
            text-align: center;
            font-size: 17px;
        }

        .patient-menu a:hover,
        .patient-menu a.active {
            background: linear-gradient(110deg, #e6f2ff, #e8f8f0);
            color: #4d83a8;
            font-weight: 600;
        }

        .patient-logout {
            margin-top: auto;
        }

        .patient-logout a {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 14px;
            border-radius: 12px;
            color: #e28c9d;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        .patient-logout a:hover {
            background: #fff0f2;
        }

        .patient-logout span {
            width: 20px;
            text-align: center;
            font-size: 17px;
        }

        /* MAIN */
        .patient-main {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 30px 38px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .topbar h2 {
            font-size: 19px;
            font-weight: 700;
            color: #40536a;
        }

        .patient-profile {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .profile-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #c7e3ff, #c8f0df);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #547b9b;
            font-weight: 700;
        }

        .profile-info strong {
            display: block;
            font-size: 12px;
            color: #40536a;
        }

        .profile-info small {
            font-size: 10px;
            color: #9aa8b7;
        }

        /* HERO */
        .welcome-banner {
            background: linear-gradient(115deg, #dcecff, #dcf5e9, #fce5ef);
            border-radius: 20px;
            padding: 28px 32px;
            margin-bottom: 27px;
        }

        .welcome-banner h1 {
            font-size: 23px;
            color: #40536a;
            margin-bottom: 8px;
        }

        .welcome-banner p {
            font-size: 12px;
            line-height: 1.8;
            color: #657b91;
            max-width: 650px;
        }

        /* FORM CARD */
        .content-card {
            background: #ffffff;
            border: 1px solid #edf1f5;
            border-radius: 18px;
            padding: 27px;
            box-shadow: 0 5px 20px rgba(65, 91, 117, 0.03);
            max-width: 850px;
        }

        .card-heading {
            margin-bottom: 22px;
        }

        .card-heading h3 {
            font-size: 15px;
            color: #40536a;
            margin-bottom: 5px;
        }

        .card-heading p {
            font-size: 11px;
            color: #9aa8b7;
        }

        /* INFO JADWAL */
        .schedule-info {
            background: linear-gradient(110deg, #f0f7ff, #effaf5);
            border: 1px solid #e5f0f5;
            border-radius: 13px;
            padding: 17px 19px;
            margin-bottom: 23px;
        }

        .schedule-info h4 {
            font-size: 12px;
            color: #547b9b;
            margin-bottom: 8px;
        }

        .schedule-info p {
            font-size: 11px;
            color: #71869a;
            line-height: 1.9;
        }

        /* FORM */
        .form-group {
            margin-bottom: 19px;
        }

        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #52677e;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e4eaf0;
            border-radius: 10px;
            outline: none;
            font-family: inherit;
            font-size: 11px;
            color: #40536a;
            background: #ffffff;
            transition: 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #9bc9e8;
            box-shadow: 0 0 0 3px rgba(155, 201, 232, 0.13);
        }

        .form-group textarea {
            min-height: 115px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 17px;
        }

        .form-hint {
            display: block;
            font-size: 10px;
            color: #9aa8b7;
            margin-top: 7px;
        }

        .alert {
            padding: 13px 16px;
            border-radius: 11px;
            font-size: 11px;
            margin-bottom: 20px;
        }

        .alert.error {
            background: #fff0f1;
            color: #d66f7c;
            border: 1px solid #f8dadd;
        }

        /* BUTTON */
        .form-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 25px;
        }

        .btn-submit {
            border: none;
            padding: 12px 21px;
            border-radius: 10px;
            background: linear-gradient(110deg, #83bce5, #83d2b2);
            color: #ffffff;
            font-family: inherit;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(100, 175, 170, 0.2);
        }

        .btn-cancel {
            padding: 12px 20px;
            border-radius: 10px;
            background: #f2f5f8;
            color: #71869a;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-cancel:hover {
            background: #e8edf2;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .patient-sidebar {
                width: 75px;
                padding: 25px 10px;
            }

            .patient-brand {
                justify-content: center;
                padding: 0;
            }

            .patient-brand span,
            .sidebar-label {
                display: none;
            }

            .patient-menu a {
                justify-content: center;
                padding: 13px 5px;
                font-size: 0;
            }

            .patient-menu a span,
            .patient-logout span {
                font-size: 19px;
            }

            .patient-logout a {
                justify-content: center;
                padding: 13px 5px;
                font-size: 0;
            }

            .patient-main {
                margin-left: 75px;
                width: calc(100% - 75px);
                padding: 22px 17px;
            }

            .welcome-banner {
                padding: 23px;
            }

            .welcome-banner h1 {
                font-size: 19px;
            }

            .content-card {
                padding: 19px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }

        @media (max-width: 480px) {
            .topbar h2 {
                font-size: 15px;
            }

            .profile-info {
                display: none;
            }

            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .form-actions a,
            .form-actions button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<!-- SIDEBAR -->
<aside class="patient-sidebar">

    <a href="dashboard.php" class="patient-brand">
        <img src="../assets/images/Logo-IDC.png" alt="Logo Klinik">
        <span>INNOVATION<br>DENTAL CARE</span>
    </a>

    <div class="sidebar-label">MENU PASIEN</div>

    <nav class="patient-menu">
        <a href="dashboard.php">
            <span>▦</span>
            Dashboard
        </a>

        <a href="jadwal.php" class="active">
            <span>▣</span>
            Jadwal Kontrol
        </a>

        <a href="konfirmasi.php">
            <span>✓</span>
            Konfirmasi Kehadiran
        </a>
    </nav>

    <div class="patient-logout">
        <a href="../auth/logout.php" onclick="return confirm('Apakah kamu yakin ingin logout?');">
            <span>↪</span>
            Logout
        </a>
    </div>

</aside>

<!-- MAIN CONTENT -->
<main class="patient-main">

    <div class="topbar">
        <h2>Pengajuan Perubahan Jadwal</h2>

        <div class="patient-profile">
            <div class="profile-avatar">
                <?= strtoupper(substr($nama_pasien, 0, 1)); ?>
            </div>

            <div class="profile-info">
                <strong><?= htmlspecialchars($nama_pasien); ?></strong>
                <small>Pasien</small>
            </div>
        </div>
    </div>

    <!-- HERO -->
    <section class="welcome-banner">
        <h1>Ajukan Perubahan Jadwal 📝</h1>

        <p>
            Jika kamu berhalangan hadir pada jadwal kontrol yang telah
            ditentukan, silakan ajukan perubahan jadwal dengan mengisi
            tanggal, waktu, dan alasan pengajuan.
        </p>
    </section>

    <!-- FORM -->
    <section class="content-card">

        <div class="card-heading">
            <h3>Formulir Pengajuan Perubahan</h3>
            <p>Lengkapi informasi berikut untuk mengajukan perubahan jadwal kontrol.</p>
        </div>

        <div class="schedule-info">
            <h4>📅 Jadwal Kontrol Saat Ini</h4>

            <p>
                Tanggal:
                <strong>
                    <?= date('d-m-Y', strtotime($data_jadwal['tanggal_kontrol'])); ?>
                </strong>
            </p>

            <p>
                Waktu:
                <strong>
                    <?= date('H:i', strtotime($data_jadwal['jam_mulai'])); ?>
                    -
                    <?= date('H:i', strtotime($data_jadwal['jam_selesai'])); ?> WIB
                </strong>
            </p>
        </div>

        <?php if (!empty($pesan)) : ?>
            <div class="alert <?= $jenis_pesan; ?>">
                <?= htmlspecialchars($pesan); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label for="tanggal_baru">Tanggal Pengajuan Baru</label>

                <input
                    type="date"
                    id="tanggal_baru"
                    name="tanggal_baru"
                    min="<?= date('Y-m-d'); ?>"
                    value="<?= htmlspecialchars($_POST['tanggal_baru'] ?? ''); ?>"
                    required
                >

                <small class="form-hint">
                    Klinik beroperasi setiap Senin sampai Sabtu.
                </small>
            </div>

            <div class="form-row">

                <div class="form-group">
                    <label for="jam_mulai_baru">Jam Mulai</label>

                    <input
                        type="time"
                        id="jam_mulai_baru"
                        name="jam_mulai_baru"
                        min="18:00"
                        max="21:00"
                        value="<?= htmlspecialchars($_POST['jam_mulai_baru'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="jam_selesai_baru">Jam Selesai</label>

                    <input
                        type="time"
                        id="jam_selesai_baru"
                        name="jam_selesai_baru"
                        min="18:00"
                        max="21:00"
                        value="<?= htmlspecialchars($_POST['jam_selesai_baru'] ?? ''); ?>"
                        required
                    >
                </div>

            </div>

            <div class="form-group">
                <label for="alasan">Alasan Perubahan Jadwal</label>

                <textarea
                    id="alasan"
                    name="alasan"
                    placeholder="Tuliskan alasan mengapa kamu ingin mengubah jadwal kontrol..."
                    required
                ><?= htmlspecialchars($_POST['alasan'] ?? ''); ?></textarea>

                <small class="form-hint">
                    Jelaskan alasan pengajuan agar admin dapat mempertimbangkannya.
                </small>
            </div>

            <div class="form-actions">

                <button type="submit" class="btn-submit">
                    Kirim Pengajuan
                </button>

                <a href="jadwal.php" class="btn-cancel">
                    Batal
                </a>

            </div>

        </form>

    </section>

</main>

</body>
</html>