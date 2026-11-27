
<?php
session_start();

// Cek hak akses admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// Koneksi database
require_once __DIR__ . "/../../config/database.php";

// Ambil data pasien
$query = "SELECT
            pasien.id_pasien,
            users.nama_lengkap,
            users.username,
            users.email,
            pasien.no_telepon,
            pasien.jenis_perawatan,
            pasien.status_pasien
          FROM pasien
          INNER JOIN users
          ON pasien.id_user = users.id_user
          ORDER BY pasien.id_pasien DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Gagal mengambil data pasien: " . mysqli_error($conn));
}

$total_pasien = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Pasien - Innovation Dental Care</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/innovation_dental_care/assets/css/style.css?v=3">

    <style>
        /* TOMBOL EDIT */
        .patient-edit-button {
            display: inline-block !important;
            padding: 9px 17px !important;
            margin-right: 5px;
            border-radius: 10px !important;
            background: linear-gradient(135deg, #fff0b8, #ffe1a3) !important;
            color: #98702b !important;
            border: 1px solid #f5dfa2 !important;
            font-family: 'Poppins', sans-serif;
            font-size: 11px !important;
            font-weight: 700 !important;
            text-decoration: none !important;
            transition: 0.3s;
        }

        .patient-edit-button:hover {
            background: #ffdda0 !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(210, 170, 90, 0.2);
        }

        /* TOMBOL HAPUS */
        .patient-delete-button {
            display: inline-block !important;
            padding: 9px 17px !important;
            border-radius: 10px !important;
            background: linear-gradient(135deg, #ffe2eb, #ffcbdc) !important;
            color: #b65d7a !important;
            border: 1px solid #f5c5d5 !important;
            font-family: 'Poppins', sans-serif;
            font-size: 11px !important;
            font-weight: 700 !important;
            text-decoration: none !important;
            transition: 0.3s;
        }

        .patient-delete-button:hover {
            background: #ffc2d5 !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(210, 120, 150, 0.2);
        }

        .patient-actions {
            white-space: nowrap;
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

            <a href="index.php" class="active">
                <span>♙</span> Data Pasien
            </a>

            <a href="../dokter/index.php">
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
                <h2>Data Pasien</h2>
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
                <span>PENGELOLAAN DATA PASIEN</span>
                <h1>Data Pasien Klinik</h1>
                <p>Kelola informasi dan akun pasien Innovation Dental Care dengan mudah.</p>
            </div>

            <div class="welcome-decoration">🦷</div>
        </section>

        <!-- PATIENT TABLE -->
        <section class="admin-content-card">

            <div class="content-heading">
                <div>
                    <h3>Daftar Pasien</h3>
                    <p>
                        Terdapat
                        <strong><?= $total_pasien; ?></strong>
                        pasien terdaftar.
                    </p>
                </div>

                <a href="tambah.php" class="patient-add-button">
                    + Tambah Pasien
                </a>
            </div>

            <div class="admin-table-wrapper">

                <table class="admin-table patient-table">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Pasien</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>No. Telepon</th>
                            <th>Perawatan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php
                    $no = 1;

                    if ($total_pasien > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                    ?>

                        <tr>
                            <td><?= $no++; ?></td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($row['nama_lengkap']); ?>
                                </strong>
                            </td>

                            <td><?= htmlspecialchars($row['username']); ?></td>

                            <td><?= htmlspecialchars($row['email']); ?></td>

                            <td><?= htmlspecialchars($row['no_telepon']); ?></td>

                            <td>
                                <?= htmlspecialchars($row['jenis_perawatan']); ?>
                            </td>

                            <td>
                                <span class="patient-status status-pasien-<?= htmlspecialchars($row['status_pasien']); ?>">
                                    <?= ucfirst(htmlspecialchars($row['status_pasien'])); ?>
                                </span>
                            </td>

                            <td class="patient-actions">

                                <a
                                    href="edit.php?id=<?= (int) $row['id_pasien']; ?>"
                                    class="patient-edit-button">
                                    Edit
                                </a>

                                <a
                                    href="hapus.php?id=<?= (int) $row['id_pasien']; ?>"
                                    class="patient-delete-button"
                                    onclick="return confirm('Apakah kamu yakin ingin menghapus pasien ini?');">
                                    Hapus
                                </a>

                            </td>
                        </tr>

                    <?php
                        }
                    } else {
                    ?>

                        <tr>
                            <td colspan="8" class="admin-empty">
                                Belum ada data pasien.
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
