
<?php
session_start();

// Memastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// Koneksi database
require_once __DIR__ . "/../../config/database.php";

$pesan = "";
$jenis_pesan = "";

// Membuat token keamanan
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Proses menyelesaikan kontrol
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';
    $id_jadwal = filter_input(INPUT_POST, 'id_jadwal', FILTER_VALIDATE_INT);

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $pesan = "Permintaan tidak valid.";
        $jenis_pesan = "error";

    } elseif (!$id_jadwal) {
        $pesan = "Data jadwal tidak valid.";
        $jenis_pesan = "error";

    } else {

        $stmt = mysqli_prepare($conn, "
            UPDATE jadwal_kontrol
            SET status_kontrol = 'selesai'
            WHERE id_jadwal = ?
            AND status_kontrol = 'terjadwal'
            AND status_konfirmasi = 'hadir'
        ");

        mysqli_stmt_bind_param($stmt, "i", $id_jadwal);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            mysqli_stmt_close($stmt);

            header("Location: index.php?status=selesai");
            exit;
        } else {
            $pesan = "Jadwal tidak dapat diselesaikan. Pastikan pasien sudah mengonfirmasi kehadiran dan jadwal masih terjadwal.";
            $jenis_pesan = "error";
        }

        mysqli_stmt_close($stmt);
    }
}

// Pesan setelah proses
if (isset($_GET['status']) && $_GET['status'] === 'selesai') {
    $pesan = "Kontrol pasien berhasil ditandai selesai.";
    $jenis_pesan = "success";
}

// Mengambil data jadwal kontrol
$query = "SELECT
            jadwal_kontrol.id_jadwal,
            jadwal_kontrol.tanggal_kontrol,
            jadwal_kontrol.jam_mulai,
            jadwal_kontrol.jam_selesai,
            jadwal_kontrol.status_konfirmasi,
            jadwal_kontrol.status_kontrol,
            users.nama_lengkap,
            dokter.nama_dokter
          FROM jadwal_kontrol
          INNER JOIN pasien
            ON jadwal_kontrol.id_pasien = pasien.id_pasien
          INNER JOIN users
            ON pasien.id_user = users.id_user
          INNER JOIN dokter
            ON jadwal_kontrol.id_dokter = dokter.id_dokter
          ORDER BY jadwal_kontrol.tanggal_kontrol DESC,
                   jadwal_kontrol.jam_mulai ASC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Gagal mengambil data jadwal: " . mysqli_error($conn));
}

$total_jadwal = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Jadwal Kontrol - Innovation Dental Care</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/innovation_dental_care/assets/css/style.css?v=2">
</head>

<body class="admin-page">

<div class="admin-layout">

    <!-- Sidebar -->
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

            <a href="../jadwal/index.php" class="active">
                <span>▣</span> Jadwal Kontrol
            </a>

            <a href="../pengajuan/index.php">
                <span>↻</span> Pengajuan Perubahan
            </a>
        </nav>

        <div class="sidebar-bottom">
            <a href="../../auth/logout.php" onclick="return confirm('Apakah kamu yakin ingin logout?');">
                <span>↪</span> Logout
            </a>
        </div>

    </aside>

    <!-- Konten Utama -->
    <main class="admin-main">

        <!-- Header -->
        <header class="admin-topbar">

            <div>
                <span class="admin-greeting">ADMINISTRATOR PANEL</span>
                <h2>Jadwal Kontrol</h2>
            </div>

            <div class="admin-profile">
                <div class="profile-icon">A</div>
                <div>
                    <strong><?= htmlspecialchars($_SESSION['nama_lengkap']); ?></strong>
                    <small>Administrator</small>
                </div>
            </div>

        </header>

        <!-- Welcome -->
        <section class="admin-welcome patient-welcome">
            <div>
                <span>PENGELOLAAN JADWAL KONTROL</span>
                <h1>Jadwal Kontrol Pasien</h1>
                <p>
                    Pantau jadwal kontrol, status konfirmasi kehadiran,
                    dan penyelesaian kontrol pasien dengan mudah.
                </p>
            </div>

            <div class="welcome-decoration">🦷</div>
        </section>

        <!-- Pesan -->
        <?php if ($pesan !== ""): ?>
            <div class="schedule-message <?= htmlspecialchars($jenis_pesan); ?>">
                <?= htmlspecialchars($pesan); ?>
            </div>
        <?php endif; ?>

        <!-- Tabel Jadwal -->
        <section class="admin-content-card">

            <div class="content-heading">
                <div>
                    <h3>Daftar Jadwal Pasien</h3>
                    <p>
                        Terdapat <strong><?= $total_jadwal; ?></strong>
                        jadwal kontrol terdaftar.
                    </p>
                </div>

                <a href="tambah.php" class="patient-add-button">
                    + Tambah Jadwal
                </a>
            </div>

            <div class="admin-table-wrapper">

                <table class="admin-table patient-table">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Pasien</th>
                            <th>Dokter</th>
                            <th>Tanggal Kontrol</th>
                            <th>Jam Mulai</th>
                            <th>Jam Selesai</th>
                            <th>Konfirmasi</th>
                            <th>Status Kontrol</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php
                    $no = 1;

                    if ($total_jadwal > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {

                            $status_konfirmasi = $row['status_konfirmasi'];
                            $status_kontrol = $row['status_kontrol'];

                            $class_konfirmasi = 'status-' . $status_konfirmasi;
                            $class_kontrol = 'status-' . $status_kontrol;
                    ?>

                        <tr>
                            <td><?= $no++; ?></td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($row['nama_lengkap']); ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['nama_dokter']); ?>
                            </td>

                            <td>
                                <?= date('d-m-Y', strtotime($row['tanggal_kontrol'])); ?>
                            </td>

                            <td>
                                <?= date('H:i', strtotime($row['jam_mulai'])); ?> WIB
                            </td>

                            <td>
                                <?= date('H:i', strtotime($row['jam_selesai'])); ?> WIB
                            </td>

                            <td>
                                <span class="admin-status schedule-status <?= htmlspecialchars($class_konfirmasi); ?>">
                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $status_konfirmasi))); ?>
                                </span>
                            </td>

                            <td>
                                <span class="admin-status schedule-status <?= htmlspecialchars($class_kontrol); ?>">
                                    <?= htmlspecialchars(ucfirst($status_kontrol)); ?>
                                </span>
                            </td>

                            <td class="schedule-actions">

                                <?php if ($status_kontrol === 'terjadwal'): ?>

                                    <a
                                        href="edit.php?id=<?= (int) $row['id_jadwal']; ?>"
                                        class="patient-edit-button">
                                        Edit
                                    </a>

                                    <?php if ($status_konfirmasi === 'hadir'): ?>

                                        <form method="POST"
                                              class="schedule-finish-form"
                                              onsubmit="return confirm('Apakah pasien benar-benar sudah selesai melakukan kontrol?');">

                                            <input type="hidden"
                                                   name="csrf_token"
                                                   value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                                            <input type="hidden"
                                                   name="id_jadwal"
                                                   value="<?= (int) $row['id_jadwal']; ?>">

                                            <button type="submit" class="schedule-finish-button">
                                                Selesaikan
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                <?php else: ?>

                                    <span class="schedule-no-action">
                                        Tidak ada aksi
                                    </span>

                                <?php endif; ?>

                            </td>
                        </tr>

                    <?php
                        }
                    } else {
                    ?>

                        <tr>
                            <td colspan="9" class="admin-empty">
                                Belum ada jadwal kontrol pasien.
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
