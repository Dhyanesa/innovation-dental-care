
<?php
session_start();

// Cek hak akses admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// Koneksi database
require_once __DIR__ . "/../../config/database.php";

// Mengambil data dokter
$query = "SELECT * FROM dokter
          ORDER BY FIELD(hari_praktik,
          'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Gagal mengambil data dokter: " . mysqli_error($conn));
}

$total_dokter = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Dokter - Innovation Dental Care</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/style.css">
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

            <a href="index.php" class="active">
                <span>✚</span> Data Dokter
            </a>

            <a href="../jadwal/index.php">
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
                <h2>Data Dokter</h2>
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
                <span>PENGELOLAAN DATA DOKTER</span>
                <h1>Data Dokter Klinik</h1>
                <p>
                    Informasi dokter yang bertugas di Innovation Dental Care
                    beserta hari dan jam praktiknya.
                </p>
            </div>

            <div class="welcome-decoration">🦷</div>
        </section>

        <!-- TABEL DOKTER -->
        <section class="admin-content-card">

            <div class="content-heading">
                <div>
                    <h3>Daftar Dokter</h3>
                    <p>
                        Terdapat
                        <strong><?= $total_dokter; ?></strong>
                        dokter terdaftar.
                    </p>
                </div>
            </div>

            <div class="admin-table-wrapper">

                <table class="admin-table patient-table">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Dokter</th>
                            <th>Hari Praktik</th>
                            <th>Jam Mulai</th>
                            <th>Jam Selesai</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php
                    $no = 1;

                    if ($total_dokter > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                    ?>

                        <tr>
                            <td><?= $no++; ?></td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($row['nama_dokter']); ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['hari_praktik']); ?>
                            </td>

                            <td>
                                <?= date('H:i', strtotime($row['jam_mulai'])); ?> WIB
                            </td>

                            <td>
                                <?= date('H:i', strtotime($row['jam_selesai'])); ?> WIB
                            </td>

                            <td>
                                <span class="patient-status status-pasien-<?= htmlspecialchars($row['status_dokter']); ?>">
                                    <?= ucfirst(htmlspecialchars($row['status_dokter'])); ?>
                                </span>
                            </td>
                        </tr>

                    <?php
                        }
                    } else {
                    ?>

                        <tr>
                            <td colspan="6" class="admin-empty">
                                Belum ada data dokter.
                            </td>
                        </tr>

                    <?php } ?>

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
