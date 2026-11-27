
<?php
session_start();

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pasien') {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$id_user = (int) $_SESSION['id_user'];

$stmt = mysqli_prepare($conn, "
    SELECT id_pasien FROM pasien WHERE id_user = ?
");
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$hasil_pasien = mysqli_stmt_get_result($stmt);
$data_pasien = mysqli_fetch_assoc($hasil_pasien);

if (!$data_pasien) {
    die("Data pasien tidak ditemukan.");
}

$id_pasien = (int) $data_pasien['id_pasien'];

$stmt_jadwal = mysqli_prepare($conn, "
    SELECT
        jadwal_kontrol.*,
        dokter.nama_dokter
    FROM jadwal_kontrol
    JOIN dokter ON jadwal_kontrol.id_dokter = dokter.id_dokter
    WHERE jadwal_kontrol.id_pasien = ?
    ORDER BY jadwal_kontrol.tanggal_kontrol DESC
");

mysqli_stmt_bind_param($stmt_jadwal, "i", $id_pasien);
mysqli_stmt_execute($stmt_jadwal);
$hasil_jadwal = mysqli_stmt_get_result($stmt_jadwal);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Jadwal Kontrol Saya - Innovation Dental Care</title>

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
            display: flex;
            min-height: 100vh;
        }

        .patient-sidebar {
            width: 250px;
            background: #fff;
            padding: 28px 20px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #edf1f5;
            position: fixed;
            height: 100vh;
        }

        .patient-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            text-decoration: none;
            color: #40536a;
            margin-bottom: 45px;
        }

        .patient-brand img {
            width: 43px;
            height: 43px;
            object-fit: contain;
        }

        .patient-brand span {
            font-size: 11px;
            font-weight: 800;
            line-height: 1.5;
            letter-spacing: .5px;
        }

        .sidebar-label {
            font-size: 10px;
            font-weight: 700;
            color: #a3b2c1;
            margin: 0 0 15px 10px;
            letter-spacing: 1px;
        }

        .patient-menu {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .patient-menu a,
        .patient-logout a {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 14px;
            border-radius: 11px;
            text-decoration: none;
            color: #8293a5;
            font-size: 11px;
            font-weight: 500;
            transition: .25s;
        }

        .patient-menu a span,
        .patient-logout a span {
            font-size: 17px;
        }

        .patient-menu a:hover,
        .patient-menu a.active {
            background: linear-gradient(110deg, #e6f2ff, #e8f8f0);
            color: #548eaa;
        }

        .patient-logout {
            margin-top: auto;
        }

        .patient-logout a:hover {
            background: #fff0f2;
            color: #d77f91;
        }

        .patient-main {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 30px 38px;
        }

        .patient-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .patient-topbar h2 {
            font-size: 19px;
            color: #40536a;
        }

        .patient-topbar small {
            display: block;
            font-size: 10px;
            color: #9aabba;
            margin-top: 5px;
        }

        .patient-profile {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .profile-icon {
            width: 39px;
            height: 39px;
            border-radius: 12px;
            background: linear-gradient(130deg, #b9dcff, #c9f0df);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #527b95;
            font-weight: 700;
        }

        .patient-profile strong {
            display: block;
            font-size: 11px;
        }

        .patient-profile small {
            font-size: 9px;
            color: #9aabba;
        }

        .patient-welcome {
            background: linear-gradient(115deg, #dcecff, #dcf5e9, #fce5ef);
            padding: 30px 35px;
            border-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .patient-welcome span {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #6d91a5;
        }

        .patient-welcome h1 {
            font-size: 23px;
            color: #405b75;
            margin: 8px 0;
        }

        .patient-welcome p {
            font-size: 11px;
            color: #71899b;
            line-height: 1.8;
        }

        .welcome-icon {
            font-size: 55px;
        }

        .notice {
            padding: 14px 17px;
            border-radius: 11px;
            font-size: 11px;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .notice.success {
            background: #e9f8ef;
            color: #4e9b70;
            border: 1px solid #d0efdc;
        }

        .schedule-card {
            background: #fff;
            padding: 25px;
            border-radius: 17px;
            border: 1px solid #edf1f5;
        }

        .section-heading {
            margin-bottom: 20px;
        }

        .section-heading h3 {
            font-size: 15px;
            color: #40536a;
        }

        .section-heading p {
            font-size: 10px;
            color: #9aabba;
            margin-top: 5px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .schedule-table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
            text-align: left;
        }

        .schedule-table th {
            background: #f4f8fc;
            color: #8195a8;
            font-size: 10px;
            font-weight: 600;
            padding: 14px 12px;
            white-space: nowrap;
        }

        .schedule-table th:first-child {
            border-radius: 9px 0 0 9px;
        }

        .schedule-table th:last-child {
            border-radius: 0 9px 9px 0;
        }

        .schedule-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #edf1f5;
            color: #61768a;
            font-size: 10px;
            vertical-align: middle;
        }

        .schedule-table tbody tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-menunggu {
            background: #fff4d9;
            color: #c69a42;
        }

        .status-hadir {
            background: #e5f7ec;
            color: #5aab7b;
        }

        .status-mengajukan_perubahan {
            background: #e6f2ff;
            color: #6d9fbd;
        }

        .status-terjadwal {
            background: #e5f7ec;
            color: #5aab7b;
        }

        .status-dibatalkan {
            background: #ffedf0;
            color: #d77f91;
        }

        .status-selesai {
            background: #eee9ff;
            color: #8c7bc5;
        }

        .schedule-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 145px;
        }

        .schedule-button {
            display: inline-block;
            text-align: center;
            padding: 8px 11px;
            border-radius: 9px;
            text-decoration: none;
            border: none;
            font-family: 'Poppins', sans-serif;
            font-size: 9px;
            font-weight: 600;
            cursor: pointer;
            transition: .25s;
        }

        .schedule-button:hover {
            transform: translateY(-2px);
        }

        .button-confirm {
            background: linear-gradient(120deg, #80bce2, #83d2b2);
            color: #fff;
        }

        .button-change {
            background: #fff4d9;
            color: #bd9649;
        }

        .pending-note {
            color: #9b91c7;
            font-size: 9px;
            line-height: 1.7;
        }

        .no-action {
            color: #a5b2bf;
            font-size: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 35px 15px;
            color: #96a7b8;
            font-size: 11px;
        }

        .empty-state div {
            font-size: 35px;
            margin-bottom: 10px;
        }

        .patient-footer {
            margin-top: 30px;
            text-align: center;
            color: #a5b2bf;
            font-size: 10px;
        }

        @media (max-width: 768px) {
            .patient-sidebar {
                width: 75px;
                padding: 20px 10px;
            }

            .patient-brand {
                justify-content: center;
            }

            .patient-brand span,
            .sidebar-label {
                display: none;
            }

            .patient-menu a,
            .patient-logout a {
                justify-content: center;
                padding: 13px 5px;
                font-size: 0;
            }

            .patient-menu a span,
            .patient-logout a span {
                font-size: 19px;
            }

            .patient-main {
                margin-left: 75px;
                width: calc(100% - 75px);
                padding: 22px 15px;
            }

            .patient-welcome {
                padding: 23px 20px;
            }

            .patient-welcome h1 {
                font-size: 18px;
            }

            .welcome-icon {
                font-size: 40px;
            }

            .schedule-card {
                padding: 17px;
            }

            .patient-topbar h2 {
                font-size: 15px;
            }
        }
    </style>
</head>

<body>

<div class="patient-layout">

    <aside class="patient-sidebar">

        <a href="dashboard.php" class="patient-brand">
            <img src="../assets/images/Logo-IDC.png" alt="Logo Klinik">
            <span>INNOVATION<br>DENTAL CARE</span>
        </a>

        <div class="sidebar-label">MENU PASIEN</div>

        <nav class="patient-menu">
            <a href="dashboard.php">
                <span>▦</span> Dashboard
            </a>

            <a href="jadwal.php" class="active">
                <span>▣</span> Jadwal Kontrol
            </a>

            <a href="konfirmasi.php">
                <span>✓</span> Konfirmasi Kehadiran
            </a>
        </nav>

        <div class="patient-logout">
            <a href="../auth/logout.php" onclick="return confirm('Apakah kamu yakin ingin logout?');">
                <span>↪</span> Logout
            </a>
        </div>

    </aside>

    <main class="patient-main">

        <header class="patient-topbar">
            <div>
                <h2>Jadwal Kontrol Saya</h2>
                <small>Informasi jadwal perawatan gigimu</small>
            </div>

            <div class="patient-profile">
                <div class="profile-icon">P</div>
                <div>
                    <strong><?= htmlspecialchars($_SESSION['nama_lengkap']); ?></strong>
                    <small>Pasien</small>
                </div>
            </div>
        </header>

        <section class="patient-welcome">
            <div>
                <span>PATIENT PORTAL</span>
                <h1>Jadwal Kontrol Saya</h1>
                <p>
                    Pantau jadwal kontrol, lihat dokter yang bertugas,
                    dan kelola konfirmasi kehadiranmu.
                </p>
            </div>

            <div class="welcome-icon">🗓️</div>
        </section>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'berhasil'): ?>
            <div class="notice success">
                ✓ Kehadiran berhasil dikonfirmasi.
            </div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] === 'pengajuan'): ?>
            <div class="notice success">
                ✓ Pengajuan perubahan berhasil dikirim dan menunggu keputusan admin.
            </div>
        <?php endif; ?>

        <section class="schedule-card">

            <div class="section-heading">
                <h3>Daftar Jadwal Kontrol</h3>
                <p>Berikut adalah daftar jadwal kontrol yang telah dibuat oleh klinik.</p>
            </div>

            <?php if (mysqli_num_rows($hasil_jadwal) > 0): ?>

                <div class="table-wrapper">

                    <table class="schedule-table">

                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal Kontrol</th>
                                <th>Waktu</th>
                                <th>Dokter</th>
                                <th>Status Konfirmasi</th>
                                <th>Status Kontrol</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php $no = 1; ?>

                            <?php while ($jadwal = mysqli_fetch_assoc($hasil_jadwal)): ?>

                                <tr>
                                    <td><?= $no++; ?></td>

                                    <td>
                                        <?= date('d-m-Y', strtotime($jadwal['tanggal_kontrol'])); ?>
                                    </td>

                                    <td>
                                        <?= date('H:i', strtotime($jadwal['jam_mulai'])); ?>
                                        -
                                        <?= date('H:i', strtotime($jadwal['jam_selesai'])); ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($jadwal['nama_dokter']); ?>
                                    </td>

                                    <td>
                                        <span class="status-badge status-<?= htmlspecialchars($jadwal['status_konfirmasi']); ?>">
                                            <?= ucfirst(str_replace('_', ' ', htmlspecialchars($jadwal['status_konfirmasi']))); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="status-badge status-<?= htmlspecialchars($jadwal['status_kontrol']); ?>">
                                            <?= ucfirst(htmlspecialchars($jadwal['status_kontrol'])); ?>
                                        </span>
                                    </td>

                                    <td>

                                        <div class="schedule-actions">

                                            <?php if ($jadwal['status_kontrol'] === 'terjadwal'): ?>

                                                <?php if ($jadwal['status_konfirmasi'] === 'menunggu'): ?>

                                                    <a href="konfirmasi.php" class="schedule-button button-confirm">
                                                        ✓ Konfirmasi Kehadiran
                                                    </a>

                                                <?php endif; ?>

                                                <?php if ($jadwal['status_konfirmasi'] !== 'mengajukan_perubahan'): ?>

                                                    <a href="pengajuan.php?id=<?= (int) $jadwal['id_jadwal']; ?>" class="schedule-button button-change">
                                                        Ajukan Perubahan
                                                    </a>

                                                <?php else: ?>

                                                    <span class="pending-note">
                                                        Menunggu keputusan admin
                                                    </span>

                                                <?php endif; ?>

                                            <?php else: ?>

                                                <span class="no-action">Tidak ada tindakan</span>

                                            <?php endif; ?>

                                        </div>

                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty-state">
                    <div>📅</div>
                    <strong>Belum Ada Jadwal Kontrol</strong>
                    <p>Jadwal kontrolmu belum tersedia. Silakan hubungi klinik jika membutuhkan informasi lebih lanjut.</p>
                </div>

            <?php endif; ?>

        </section>

        <footer class="patient-footer">
            © <?= date('Y'); ?> Innovation Dental Care Pontianak
        </footer>

    </main>

</div>

</body>
</html>
