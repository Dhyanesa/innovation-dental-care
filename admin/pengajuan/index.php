
<?php
session_start();

// Cek hak akses admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

require_once __DIR__ . "/../../config/database.php";

date_default_timezone_set('Asia/Jakarta');

$pesan = "";
$jenis_pesan = "";

// Token keamanan form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fungsi menentukan dokter berdasarkan hari
function cariDokter($conn, $tanggal)
{
    $hari = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu'
    ];

    $nama_hari = $hari[(int) date('N', strtotime($tanggal))];

    if ($nama_hari === 'Minggu') {
        throw new Exception("Klinik tutup pada hari Minggu.");
    }

    $stmt = mysqli_prepare($conn, "
        SELECT id_dokter
        FROM dokter
        WHERE hari_praktik = ?
        AND status_dokter = 'aktif'
        LIMIT 1
    ");

    mysqli_stmt_bind_param($stmt, "s", $nama_hari);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $dokter = mysqli_fetch_assoc($hasil);

    mysqli_stmt_close($stmt);

    if (!$dokter) {
        throw new Exception("Dokter tidak tersedia pada hari tersebut.");
    }

    return (int) $dokter['id_dokter'];
}

// Fungsi validasi jadwal
function validasiJadwal($tanggal, $jam_mulai, $jam_selesai)
{
    $tanggal_obj = DateTime::createFromFormat('!Y-m-d', $tanggal);

    if (!$tanggal_obj || $tanggal_obj->format('Y-m-d') !== $tanggal) {
        throw new Exception("Tanggal tidak valid.");
    }

    if ($tanggal < date('Y-m-d')) {
        throw new Exception("Tanggal jadwal tidak boleh sudah lewat.");
    }

    if (
        !preg_match('/^(18|19|20|21):[0-5][0-9]$/', $jam_mulai) ||
        !preg_match('/^(18|19|20|21):[0-5][0-9]$/', $jam_selesai)
    ) {
        throw new Exception("Format jam tidak valid.");
    }

    if (
        $jam_mulai < '18:00' ||
        $jam_selesai > '21:00' ||
        $jam_mulai >= $jam_selesai
    ) {
        throw new Exception(
            "Jam praktik klinik adalah 18.00–21.00. Pastikan jam mulai lebih awal dari jam selesai."
        );
    }

    return [$tanggal, $jam_mulai, $jam_selesai];
}

// Proses persetujuan atau penolakan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';
    $id_pengajuan = filter_input(INPUT_POST, 'id_pengajuan', FILTER_VALIDATE_INT);
    $aksi = $_POST['aksi'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {

        $pesan = "Permintaan tidak valid. Silakan muat ulang halaman.";
        $jenis_pesan = "error";

    } elseif (
        !$id_pengajuan ||
        !in_array($aksi, ['setujui', 'tolak_buat_jadwal'], true)
    ) {

        $pesan = "Data pengajuan tidak valid.";
        $jenis_pesan = "error";

    } else {

        mysqli_begin_transaction($conn);

        try {

            // Ambil pengajuan yang masih menunggu
            $stmt = mysqli_prepare($conn, "
                SELECT
                    pengajuan_perubahan.*,
                    jadwal_kontrol.id_pasien,
                    jadwal_kontrol.status_kontrol
                FROM pengajuan_perubahan
                JOIN jadwal_kontrol
                    ON pengajuan_perubahan.id_jadwal = jadwal_kontrol.id_jadwal
                WHERE pengajuan_perubahan.id_pengajuan = ?
                AND pengajuan_perubahan.status_pengajuan = 'menunggu'
                FOR UPDATE
            ");

            mysqli_stmt_bind_param($stmt, "i", $id_pengajuan);
            mysqli_stmt_execute($stmt);

            $hasil = mysqli_stmt_get_result($stmt);
            $pengajuan = mysqli_fetch_assoc($hasil);

            mysqli_stmt_close($stmt);

            if (!$pengajuan) {
                throw new Exception(
                    "Pengajuan tidak ditemukan atau sudah diproses."
                );
            }

            if ($pengajuan['status_kontrol'] !== 'terjadwal') {
                throw new Exception(
                    "Jadwal lama tidak lagi aktif sehingga pengajuan tidak dapat diproses."
                );
            }

            $id_jadwal_lama = (int) $pengajuan['id_jadwal'];
            $id_pasien = (int) $pengajuan['id_pasien'];

            if ($aksi === 'setujui') {

                // Gunakan tanggal dan jam usulan pasien
                $tanggal = $pengajuan['tanggal_usulan'];
                $jam_mulai = substr($pengajuan['jam_mulai_usulan'], 0, 5);
                $jam_selesai = substr($pengajuan['jam_selesai_usulan'], 0, 5);

                [$tanggal, $jam_mulai, $jam_selesai] =
                    validasiJadwal($tanggal, $jam_mulai, $jam_selesai);

                $status = 'disetujui';
                $catatan = 'Pengajuan perubahan jadwal disetujui. Jadwal baru telah dibuat.';

            } else {

                // Gunakan jadwal baru yang dibuat admin
                $tanggal = $_POST['tanggal_baru'] ?? '';
                $jam_mulai = $_POST['jam_mulai_baru'] ?? '';
                $jam_selesai = $_POST['jam_selesai_baru'] ?? '';

                [$tanggal, $jam_mulai, $jam_selesai] =
                    validasiJadwal($tanggal, $jam_mulai, $jam_selesai);

                $status = 'ditolak';
                $catatan = 'Pengajuan pasien ditolak. Admin telah membuat jadwal pengganti.';
            }

            // Tentukan dokter otomatis berdasarkan tanggal baru
            $id_dokter = cariDokter($conn, $tanggal);

            // Batalkan jadwal lama
            $stmt_lama = mysqli_prepare($conn, "
                UPDATE jadwal_kontrol
                SET status_kontrol = 'dibatalkan'
                WHERE id_jadwal = ?
                AND status_kontrol = 'terjadwal'
            ");

            mysqli_stmt_bind_param($stmt_lama, "i", $id_jadwal_lama);

            if (!mysqli_stmt_execute($stmt_lama) ||
                mysqli_stmt_affected_rows($stmt_lama) !== 1) {
                throw new Exception("Gagal membatalkan jadwal lama.");
            }

            mysqli_stmt_close($stmt_lama);

            // Buat jadwal kontrol baru
            $stmt_baru = mysqli_prepare($conn, "
                INSERT INTO jadwal_kontrol (
                    id_pasien,
                    id_dokter,
                    tanggal_kontrol,
                    jam_mulai,
                    jam_selesai,
                    status_konfirmasi,
                    status_kontrol,
                    catatan
                ) VALUES (?, ?, ?, ?, ?, 'menunggu', 'terjadwal', ?)
            ");

            mysqli_stmt_bind_param(
                $stmt_baru,
                "iissss",
                $id_pasien,
                $id_dokter,
                $tanggal,
                $jam_mulai,
                $jam_selesai,
                $catatan
            );

            if (!mysqli_stmt_execute($stmt_baru)) {
                throw new Exception("Gagal membuat jadwal pengganti.");
            }

            mysqli_stmt_close($stmt_baru);

            // Simpan keputusan admin pada pengajuan lama
            $stmt_status = mysqli_prepare($conn, "
                UPDATE pengajuan_perubahan
                SET
                    status_pengajuan = ?,
                    catatan_admin = ?
                WHERE id_pengajuan = ?
            ");

            mysqli_stmt_bind_param(
                $stmt_status,
                "ssi",
                $status,
                $catatan,
                $id_pengajuan
            );

            if (!mysqli_stmt_execute($stmt_status)) {
                throw new Exception("Gagal menyimpan keputusan admin.");
            }

            mysqli_stmt_close($stmt_status);

            // Simpan seluruh perubahan
            mysqli_commit($conn);

            header("Location: index.php?status=" . $status);
            exit;

        } catch (Exception $e) {

            mysqli_rollback($conn);

            $pesan = $e->getMessage();
            $jenis_pesan = "error";
        }
    }
}

// Pesan setelah proses
if (isset($_GET['status'])) {

    if ($_GET['status'] === 'disetujui') {

        $pesan = "Pengajuan berhasil disetujui. Jadwal lama dibatalkan dan jadwal baru berhasil dibuat.";
        $jenis_pesan = "success";

    } elseif ($_GET['status'] === 'ditolak') {

        $pesan = "Pengajuan ditolak. Jadwal lama dibatalkan dan jadwal pengganti berhasil dibuat.";
        $jenis_pesan = "success";
    }
}

// Ambil seluruh data pengajuan
$query = mysqli_query($conn, "
    SELECT
        pengajuan_perubahan.*,
        jadwal_kontrol.tanggal_kontrol,
        jadwal_kontrol.jam_mulai,
        jadwal_kontrol.jam_selesai,
        users.nama_lengkap,
        dokter.nama_dokter
    FROM pengajuan_perubahan
    JOIN jadwal_kontrol
        ON pengajuan_perubahan.id_jadwal = jadwal_kontrol.id_jadwal
    JOIN pasien
        ON jadwal_kontrol.id_pasien = pasien.id_pasien
    JOIN users
        ON pasien.id_user = users.id_user
    JOIN dokter
        ON jadwal_kontrol.id_dokter = dokter.id_dokter
    ORDER BY pengajuan_perubahan.created_at DESC
");

if (!$query) {
    die("Gagal mengambil data pengajuan: " . mysqli_error($conn));
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pengajuan Perubahan - Innovation Dental Care</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/innovation_dental_care/assets/css/style.css?v=3">

    <style>
        .request-welcome {
            background: linear-gradient(115deg, #dcecff, #dcf5e9, #fce5ef);
        }

        .request-message {
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 12px;
            line-height: 1.7;
            margin-bottom: 22px;
        }

        .request-message.success {
            background: #e9f8ef;
            color: #4e9b70;
            border: 1px solid #d0efdc;
        }

        .request-message.error {
            background: #fff0f1;
            color: #c65e6b;
            border: 1px solid #f8d8dc;
        }

        .request-table {
            min-width: 1250px;
        }

        .request-table th {
            padding: 15px 12px;
            white-space: nowrap;
        }

        .request-table td {
            padding: 15px 12px;
            vertical-align: top;
            line-height: 1.8;
        }

        .request-reason {
            max-width: 180px;
            white-space: normal;
            overflow-wrap: break-word;
        }

        .request-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            white-space: nowrap;
        }

        .request-status.menunggu {
            background: #fff4d9;
            color: #c69a42;
        }

        .request-status.disetujui {
            background: #e5f7ec;
            color: #5aab7b;
        }

        .request-status.ditolak {
            background: #ffedf0;
            color: #d77f91;
        }

        .request-action {
            min-width: 220px;
        }

        .request-action form {
            margin: 0;
        }

        .request-approve-button,
        .request-reject-button {
            width: 100%;
            padding: 10px 13px;
            border: none;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.25s;
        }

        .request-approve-button {
            background: linear-gradient(120deg, #80bce2, #83d2b2);
            color: white;
        }

        .request-approve-button:hover,
        .request-reject-button:hover {
            transform: translateY(-2px);
        }

        .request-reject-button {
            background: #ffedf0;
            color: #d77f91;
        }

        .request-action-divider {
            border: none;
            border-top: 1px solid #edf1f5;
            margin: 15px 0;
        }

        .request-form-title {
            color: #52677c;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .request-form-group {
            margin-bottom: 12px;
        }

        .request-form-group label {
            display: block;
            color: #71869a;
            font-size: 10px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .request-form-group input {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 11px;
            border: 1px solid #dce8f0;
            border-radius: 9px;
            outline: none;
            background: #fbfdff;
            color: #45586c;
            font-family: 'Poppins', sans-serif;
            font-size: 10px;
        }

        .request-form-group input:focus {
            border-color: #86c7e9;
            box-shadow: 0 0 0 3px rgba(134, 199, 233, 0.15);
        }

        .request-processed {
            color: #91a2b2;
            font-size: 11px;
            font-weight: 500;
        }

        .request-empty {
            text-align: center;
            padding: 35px 20px;
            color: #96a7b8;
            font-size: 12px;
        }

        .request-admin-note {
            max-width: 170px;
            white-space: normal;
            overflow-wrap: break-word;
        }

        @media (max-width: 768px) {
            .request-table {
                min-width: 1250px;
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

            <a href="../jadwal/index.php">
                <span>▣</span> Jadwal Kontrol
            </a>

            <a href="index.php" class="active">
                <span>↻</span> Pengajuan Perubahan
            </a>
        </nav>

        <div class="sidebar-bottom">
            <a href="../../auth/logout.php" onclick="return confirm('Apakah kamu yakin ingin logout?');">
                <span>↪</span> Logout
            </a>
        </div>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="admin-main">

        <!-- TOPBAR -->
        <header class="admin-topbar">
            <div>
                <span class="admin-greeting">ADMINISTRATOR PANEL</span>
                <h2>Pengajuan Perubahan Jadwal</h2>
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
        <section class="admin-welcome request-welcome">
            <div>
                <span>PENGELOLAAN JADWAL KONTROL</span>
                <h1>Pengajuan Perubahan</h1>
                <p>Periksa dan proses permintaan perubahan jadwal dari pasien.</p>
            </div>

            <div class="welcome-decoration">🗓️</div>
        </section>

        <!-- CONTENT -->
        <section class="admin-content-card">

            <div class="content-heading">
                <div>
                    <h3>Daftar Pengajuan Pasien</h3>
                    <p>Kelola permintaan perubahan jadwal kontrol pasien.</p>
                </div>
            </div>

            <!-- NOTIFICATION -->
            <?php if ($pesan !== ""): ?>
                <div class="request-message <?= $jenis_pesan === 'success' ? 'success' : 'error'; ?>">
                    <?= htmlspecialchars($pesan); ?>
                </div>
            <?php endif; ?>

            <!-- TABLE -->
            <?php if (mysqli_num_rows($query) > 0): ?>

                <div class="admin-table-wrapper">

                    <table class="admin-table request-table">

                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Pasien</th>
                                <th>Jadwal Lama</th>
                                <th>Dokter Lama</th>
                                <th>Usulan Tanggal</th>
                                <th>Usulan Waktu</th>
                                <th>Alasan</th>
                                <th>Status</th>
                                <th>Catatan Admin</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php $no = 1; ?>

                            <?php while ($data = mysqli_fetch_assoc($query)): ?>

                                <tr>

                                    <td><?= $no++; ?></td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($data['nama_lengkap']); ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= date('d-m-Y', strtotime($data['tanggal_kontrol'])); ?>
                                        <br>
                                        <?= date('H:i', strtotime($data['jam_mulai'])); ?>
                                        -
                                        <?= date('H:i', strtotime($data['jam_selesai'])); ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($data['nama_dokter']); ?>
                                    </td>

                                    <td>
                                        <?= date('d-m-Y', strtotime($data['tanggal_usulan'])); ?>
                                    </td>

                                    <td>
                                        <?= date('H:i', strtotime($data['jam_mulai_usulan'])); ?>
                                        -
                                        <?= date('H:i', strtotime($data['jam_selesai_usulan'])); ?>
                                    </td>

                                    <td class="request-reason">
                                        <?= nl2br(htmlspecialchars($data['alasan'])); ?>
                                    </td>

                                    <td>
                                        <span class="request-status <?= htmlspecialchars($data['status_pengajuan']); ?>">
                                            <?= ucfirst(htmlspecialchars($data['status_pengajuan'])); ?>
                                        </span>
                                    </td>

                                    <td class="request-admin-note">
                                        <?= htmlspecialchars($data['catatan_admin'] ?? '-'); ?>
                                    </td>

                                    <td class="request-action">

                                        <?php if ($data['status_pengajuan'] === 'menunggu'): ?>

                                            <!-- SETUJUI PENGAJUAN -->
                                            <form method="POST"
                                                onsubmit="return confirm('Yakin ingin menyetujui pengajuan ini dan membuat jadwal baru?');">

                                                <input type="hidden" name="csrf_token"
                                                    value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                                                <input type="hidden" name="id_pengajuan"
                                                    value="<?= (int) $data['id_pengajuan']; ?>">

                                                <input type="hidden" name="aksi" value="setujui">

                                                <button type="submit" class="request-approve-button">
                                                    ✓ Setujui Pengajuan
                                                </button>

                                            </form>

                                            <hr class="request-action-divider">

                                            <!-- TOLAK DAN BUAT JADWAL BARU -->
                                            <div class="request-form-title">
                                                Buat Jadwal Pengganti
                                            </div>

                                            <form method="POST"
                                                onsubmit="return confirm('Tolak pengajuan dan buat jadwal baru untuk pasien ini?');">

                                                <input type="hidden" name="csrf_token"
                                                    value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                                                <input type="hidden" name="id_pengajuan"
                                                    value="<?= (int) $data['id_pengajuan']; ?>">

                                                <input type="hidden" name="aksi"
                                                    value="tolak_buat_jadwal">

                                                <div class="request-form-group">
                                                    <label>Tanggal Baru</label>
                                                    <input
                                                        type="date"
                                                        name="tanggal_baru"
                                                        min="<?= date('Y-m-d'); ?>"
                                                        required
                                                    >
                                                </div>

                                                <div class="request-form-group">
                                                    <label>Jam Mulai</label>
                                                    <input
                                                        type="time"
                                                        name="jam_mulai_baru"
                                                        min="18:00"
                                                        max="21:00"
                                                        required
                                                    >
                                                </div>

                                                <div class="request-form-group">
                                                    <label>Jam Selesai</label>
                                                    <input
                                                        type="time"
                                                        name="jam_selesai_baru"
                                                        min="18:00"
                                                        max="21:00"
                                                        required
                                                    >
                                                </div>

                                                <button type="submit" class="request-reject-button">
                                                    Tolak & Buat Jadwal Baru
                                                </button>

                                            </form>

                                        <?php else: ?>

                                            <span class="request-processed">
                                                ✓ Sudah diproses
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="request-empty">
                    <div style="font-size: 35px; margin-bottom: 12px;">📋</div>
                    <strong>Belum Ada Pengajuan</strong>
                    <p>Belum ada pengajuan perubahan jadwal dari pasien.</p>
                </div>

            <?php endif; ?>

        </section>

        <!-- FOOTER -->
        <footer class="admin-footer">
            © <?= date('Y'); ?> Innovation Dental Care Pontianak
        </footer>

    </main>

</div>

</body>
</html>
