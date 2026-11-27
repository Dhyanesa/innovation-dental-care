<?php
session_start();

// Memastikan pasien sudah login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pasien') {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

// Mengambil ID pasien
$id_user = (int) $_SESSION['id_user'];

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

$pesan = "";
$jenis_pesan = "";

// Memproses konfirmasi kehadiran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_jadwal = isset($_POST['id_jadwal'])
        ? (int) $_POST['id_jadwal']
        : 0;

    if ($id_jadwal <= 0) {
        $pesan = "Jadwal kontrol tidak valid.";
        $jenis_pesan = "error";
    } else {
        $stmt_update = mysqli_prepare($conn, "
            UPDATE jadwal_kontrol
            SET status_konfirmasi = 'hadir'
            WHERE id_jadwal = ?
            AND id_pasien = ?
            AND status_konfirmasi = 'menunggu'
            AND status_kontrol = 'terjadwal'
        ");

        mysqli_stmt_bind_param(
            $stmt_update,
            "ii",
            $id_jadwal,
            $id_pasien
        );

        mysqli_stmt_execute($stmt_update);

        if (mysqli_stmt_affected_rows($stmt_update) > 0) {
            header("Location: jadwal.php?status=berhasil");
            exit;
        } else {
            $pesan = "Konfirmasi gagal. Jadwal mungkin sudah dikonfirmasi atau tidak tersedia.";
            $jenis_pesan = "error";
        }

        mysqli_stmt_close($stmt_update);
    }
}

// Mengambil jadwal yang menunggu konfirmasi
$stmt_jadwal = mysqli_prepare($conn, "
    SELECT
        id_jadwal,
        tanggal_kontrol,
        jam_mulai,
        jam_selesai
    FROM jadwal_kontrol
    WHERE id_pasien = ?
    AND status_konfirmasi = 'menunggu'
    AND status_kontrol = 'terjadwal'
    ORDER BY tanggal_kontrol ASC
");

mysqli_stmt_bind_param($stmt_jadwal, "i", $id_pasien);
mysqli_stmt_execute($stmt_jadwal);

$result_jadwal = mysqli_stmt_get_result($stmt_jadwal);

$nama_pasien = $_SESSION['nama_lengkap'] ?? 'Pasien';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Konfirmasi Kehadiran - Innovation Dental Care</title>

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

        .patient-layout {
            min-height: 100vh;
        }

        /* SIDEBAR */
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

        /* MAIN CONTENT */
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

        /* CONTENT CARD */
        .content-card {
            background: #ffffff;
            border: 1px solid #edf1f5;
            border-radius: 18px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(65, 91, 117, 0.03);
        }

        .card-heading {
            margin-bottom: 20px;
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

        /* ALERT */
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

        /* SCHEDULE ITEM */
        .schedule-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .schedule-item {
            border: 1px solid #edf1f5;
            border-radius: 15px;
            padding: 19px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            transition: 0.2s;
        }

        .schedule-item:hover {
            border-color: #c9e2f5;
            box-shadow: 0 5px 16px rgba(83, 135, 174, 0.06);
        }

        .schedule-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .schedule-icon {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            border-radius: 13px;
            background: linear-gradient(135deg, #e3f0ff, #e2f7ed);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
        }

        .schedule-info h4 {
            font-size: 13px;
            color: #40536a;
            margin-bottom: 5px;
        }

        .schedule-info p {
            font-size: 11px;
            color: #91a0af;
            line-height: 1.8;
        }

        .schedule-action button {
            border: none;
            padding: 11px 17px;
            border-radius: 10px;
            background: linear-gradient(110deg, #83bce5, #83d2b2);
            color: #ffffff;
            font-family: inherit;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .schedule-action button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(100, 175, 170, 0.2);
        }

        .empty-state {
            text-align: center;
            padding: 45px 20px;
        }

        .empty-icon {
            font-size: 38px;
            margin-bottom: 12px;
        }

        .empty-state h4 {
            font-size: 14px;
            color: #52677e;
            margin-bottom: 7px;
        }

        .empty-state p {
            font-size: 11px;
            color: #9aa8b7;
            line-height: 1.8;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #6d9fc2;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
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
            .sidebar-label,
            .patient-menu a:not(.active) {
                font-size: 0;
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
                padding: 18px;
            }

            .schedule-item {
                align-items: flex-start;
                flex-direction: column;
            }

            .schedule-action {
                width: 100%;
            }

            .schedule-action button {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .topbar h2 {
                font-size: 15px;
            }

            .profile-info {
                display: none;
            }

            .welcome-banner p {
                font-size: 11px;
            }
        }
    </style>
</head>

<body>

<div class="patient-layout">

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

            <a href="jadwal.php">
                <span>▣</span>
                Jadwal Kontrol
            </a>

            <a href="konfirmasi.php" class="active">
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
            <h2>Konfirmasi Kehadiran</h2>

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
            <h1>Konfirmasi Kehadiran 🦷</h1>
            <p>
                Pastikan kehadiranmu pada jadwal kontrol yang telah diberikan
                oleh klinik. Tekan tombol konfirmasi apabila kamu dapat hadir
                sesuai jadwal tersebut.
            </p>
        </section>

        <!-- DAFTAR JADWAL -->
        <section class="content-card">

            <div class="card-heading">
                <h3>Jadwal Menunggu Konfirmasi</h3>
                <p>Daftar jadwal kontrol yang perlu kamu konfirmasi.</p>
            </div>

            <?php if (!empty($pesan)) : ?>
                <div class="alert <?= $jenis_pesan; ?>">
                    <?= htmlspecialchars($pesan); ?>
                </div>
            <?php endif; ?>

            <?php if (mysqli_num_rows($result_jadwal) > 0) : ?>

                <div class="schedule-list">

                    <?php while ($jadwal = mysqli_fetch_assoc($result_jadwal)) : ?>

                        <div class="schedule-item">

                            <div class="schedule-info">
                                <div class="schedule-icon">📅</div>

                                <div>
                                    <h4>
                                        <?= date('d-m-Y', strtotime($jadwal['tanggal_kontrol'])); ?>
                                    </h4>

                                    <p>
                                        Jam kontrol:
                                        <?= date('H:i', strtotime($jadwal['jam_mulai'])); ?>
                                        -
                                        <?= date('H:i', strtotime($jadwal['jam_selesai'])); ?>
                                        WIB
                                    </p>

                                    <p>Status: Menunggu konfirmasi</p>
                                </div>
                            </div>

                            <div class="schedule-action">
                                <form method="POST"
                                      onsubmit="return confirm('Apakah kamu yakin dapat hadir pada jadwal kontrol ini?');">

                                    <input type="hidden"
                                           name="id_jadwal"
                                           value="<?= (int) $jadwal['id_jadwal']; ?>">

                                    <button type="submit">
                                        ✓ Konfirmasi Hadir
                                    </button>

                                </form>
                            </div>

                        </div>

                    <?php endwhile; ?>

                </div>

            <?php else : ?>

                <div class="empty-state">
                    <div class="empty-icon">🗓️</div>

                    <h4>Tidak Ada Jadwal Menunggu Konfirmasi</h4>

                    <p>
                        Saat ini tidak ada jadwal kontrol yang perlu
                        dikonfirmasi. Kamu dapat melihat jadwal kontrol
                        melalui halaman Jadwal Kontrol.
                    </p>

                    <a href="jadwal.php" class="back-link">
                        Lihat Jadwal Kontrol →
                    </a>
                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>