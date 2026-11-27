
<?php
session_start();

// Pastikan pengguna sudah login sebagai admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

// Mengambil ringkasan data
$total_pasien = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM pasien"
))['total'];

$total_dokter = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM dokter WHERE status_dokter = 'aktif'"
))['total'];

$total_jadwal = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM jadwal_kontrol WHERE status_kontrol = 'terjadwal'"
))['total'];

$total_pengajuan = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM pengajuan_perubahan WHERE status_pengajuan = 'menunggu'"
))['total'];

// Mengambil 5 jadwal kontrol terbaru
$query_jadwal = mysqli_query($conn, "
    SELECT
        jadwal_kontrol.tanggal_kontrol,
        jadwal_kontrol.jam_mulai,
        jadwal_kontrol.jam_selesai,
        jadwal_kontrol.status_konfirmasi,
        jadwal_kontrol.status_kontrol,
        users.nama_lengkap,
        dokter.nama_dokter
    FROM jadwal_kontrol
    JOIN pasien ON jadwal_kontrol.id_pasien = pasien.id_pasien
    JOIN users ON pasien.id_user = users.id_user
    JOIN dokter ON jadwal_kontrol.id_dokter = dokter.id_dokter
    ORDER BY jadwal_kontrol.created_at DESC
    LIMIT 5
");

function tanggalIndonesia($tanggal) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April',
        'Mei', 'Juni', 'Juli', 'Agustus',
        'September', 'Oktober', 'November', 'Desember'
    ];

    $pecah = explode('-', $tanggal);

    return (int)$pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0];
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Admin - Innovation Dental Care</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="admin-page">

<div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="admin-sidebar">

        <a href="dashboard.php" class="admin-brand">
            <img src="../assets/images/Logo-IDC.png" alt="Logo Klinik">
            <span>INNOVATION<br>DENTAL CARE</span>
        </a>

        <div class="sidebar-label">MENU UTAMA</div>

        <nav class="admin-menu">
            <a href="dashboard.php" class="active">
                <span>▦</span> Dashboard
            </a>

            <a href="pasien/index.php">
                <span>♙</span> Data Pasien
            </a>

            <a href="dokter/index.php">
                <span>✚</span> Data Dokter
            </a>

            <a href="jadwal/index.php">
                <span>▣</span> Jadwal Kontrol
            </a>

            <a href="pengajuan/index.php">
                <span>↻</span> Pengajuan Perubahan
            </a>
        </nav>

        <div class="sidebar-bottom">
            <a href="../auth/logout.php"
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
                <h2>Dashboard</h2>
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
        <section class="admin-welcome">
            <div>
                <span>INNOVATION DENTAL CARE PONTIANAK</span>
                <h1>Selamat datang, <?= htmlspecialchars($_SESSION['nama_lengkap']); ?>!</h1>
                <p>Kelola data pasien, dokter, dan jadwal kontrol klinik melalui dashboard ini.</p>
            </div>

            <div class="welcome-decoration">🦷</div>
        </section>

        <!-- STATISTICS -->
        <section class="admin-statistics">

            <div class="stat-card stat-blue">
                <div class="stat-icon">♙</div>
                <div>
                    <p>Total Pasien</p>
                    <h2><?= $total_pasien; ?></h2>
                    <small>Pasien terdaftar</small>
                </div>
            </div>

            <div class="stat-card stat-green">
                <div class="stat-icon">✚</div>
                <div>
                    <p>Dokter Aktif</p>
                    <h2><?= $total_dokter; ?></h2>
                    <small>Dokter praktik</small>
                </div>
            </div>

            <div class="stat-card stat-pink">
                <div class="stat-icon">▣</div>
                <div>
                    <p>Jadwal Terjadwal</p>
                    <h2><?= $total_jadwal; ?></h2>
                    <small>Kontrol belum dibatalkan atau selesai</small>
                </div>
            </div>

            <div class="stat-card stat-yellow">
                <div class="stat-icon">↻</div>
                <div>
                    <p>Pengajuan Menunggu</p>
                    <h2><?= $total_pengajuan; ?></h2>
                    <small>Perlu ditinjau admin</small>
                </div>
            </div>

        </section>

        <!-- RECENT SCHEDULE -->
        <section class="admin-content-card">

            <div class="content-heading">
                <div>
                    <h3>Jadwal Kontrol Terbaru</h3>
                    <p>Daftar jadwal kontrol pasien yang baru dibuat.</p>
                </div>

                <a href="jadwal/index.php" class="admin-view-all">
                    Lihat Semua →
                </a>
            </div>

            <div class="admin-table-wrapper">
                <table class="admin-table">

                    <thead>
                        <tr>
                            <th>Pasien</th>
                            <th>Dokter</th>
                            <th>Tanggal</th>
                            <th>Jam</th>
                            <th>Konfirmasi</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($query_jadwal && mysqli_num_rows($query_jadwal) > 0): ?>

                        <?php while ($jadwal = mysqli_fetch_assoc($query_jadwal)): ?>

                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($jadwal['nama_lengkap']); ?></strong>
                                </td>

                                <td><?= htmlspecialchars($jadwal['nama_dokter']); ?></td>

                                <td><?= tanggalIndonesia($jadwal['tanggal_kontrol']); ?></td>

                                <td>
                                    <?= substr($jadwal['jam_mulai'], 0, 5); ?>
                                    -
                                    <?= substr($jadwal['jam_selesai'], 0, 5); ?>
                                </td>

                                <td>
                                    <span class="admin-status status-<?= htmlspecialchars($jadwal['status_konfirmasi']); ?>">
                                        <?= ucfirst(str_replace('_', ' ', $jadwal['status_konfirmasi'])); ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="admin-empty">
                                Belum ada jadwal kontrol.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </section>

        <footer class="admin-footer">
            © <?= date('Y'); ?> Innovation Dental Care Pontianak
        </footer>

    </main>

</div>

</body>
</html>
