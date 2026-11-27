
<?php
session_start();

// Cek hak akses admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

require_once __DIR__ . "/../../config/database.php";

$id_pasien = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_pasien <= 0) {
    header("Location: index.php");
    exit;
}

$query = "SELECT
            pasien.*,
            users.id_user,
            users.nama_lengkap,
            users.username,
            users.email
          FROM pasien
          JOIN users ON pasien.id_user = users.id_user
          WHERE pasien.id_pasien = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_pasien);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$pasien = mysqli_fetch_assoc($result);

if (!$pasien) {
    echo "Data pasien tidak ditemukan.";
    exit;
}

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');

    $tanggal_mulai = !empty($_POST['tanggal_mulai_perawatan'])
        ? $_POST['tanggal_mulai_perawatan']
        : null;

    $jenis_perawatan = $_POST['jenis_perawatan'] ?? '';
    $status_pasien = $_POST['status_pasien'] ?? '';

    if (
        $nama_lengkap === '' ||
        $username === '' ||
        $email === '' ||
        $no_telepon === ''
    ) {
        $pesan = "Semua data wajib diisi kecuali tanggal mulai perawatan.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan = "Format email tidak valid.";
    } elseif (!in_array($jenis_perawatan, ['Behel', 'Scaling', 'Tambal Gigi', 'Lainnya'], true)) {
        $pesan = "Jenis perawatan tidak valid.";
    } elseif (!in_array($status_pasien, ['aktif', 'tidak_aktif'], true)) {
        $pesan = "Status pasien tidak valid.";
    } else {

        $cek = mysqli_prepare(
            $conn,
            "SELECT id_user FROM users
             WHERE (username = ? OR email = ?)
             AND id_user != ?"
        );

        mysqli_stmt_bind_param(
            $cek,
            "ssi",
            $username,
            $email,
            $pasien['id_user']
        );

        mysqli_stmt_execute($cek);
        $hasil_cek = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasil_cek) > 0) {
            $pesan = "Username atau email sudah digunakan pengguna lain.";
        } else {

            mysqli_begin_transaction($conn);

            try {
                $update_user = mysqli_prepare(
                    $conn,
                    "UPDATE users
                     SET nama_lengkap = ?, username = ?, email = ?
                     WHERE id_user = ?"
                );

                mysqli_stmt_bind_param(
                    $update_user,
                    "sssi",
                    $nama_lengkap,
                    $username,
                    $email,
                    $pasien['id_user']
                );

                if (!mysqli_stmt_execute($update_user)) {
                    throw new Exception("Gagal memperbarui data akun.");
                }

                $update_pasien = mysqli_prepare(
                    $conn,
                    "UPDATE pasien
                     SET no_telepon = ?,
                         tanggal_mulai_perawatan = ?,
                         jenis_perawatan = ?,
                         status_pasien = ?
                     WHERE id_pasien = ?"
                );

                mysqli_stmt_bind_param(
                    $update_pasien,
                    "ssssi",
                    $no_telepon,
                    $tanggal_mulai,
                    $jenis_perawatan,
                    $status_pasien,
                    $id_pasien
                );

                if (!mysqli_stmt_execute($update_pasien)) {
                    throw new Exception("Gagal memperbarui data pasien.");
                }

                mysqli_commit($conn);

                echo "<script>
                    alert('Data pasien berhasil diperbarui!');
                    window.location.href = 'index.php';
                </script>";
                exit;

            } catch (Exception $e) {
                mysqli_rollback($conn);
                $pesan = $e->getMessage();
            }
        }
    }

    // Mempertahankan data input jika terjadi kesalahan
    $pasien['nama_lengkap'] = $nama_lengkap;
    $pasien['username'] = $username;
    $pasien['email'] = $email;
    $pasien['no_telepon'] = $no_telepon;
    $pasien['tanggal_mulai_perawatan'] = $tanggal_mulai;
    $pasien['jenis_perawatan'] = $jenis_perawatan;
    $pasien['status_pasien'] = $status_pasien;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Pasien - Innovation Dental Care</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/innovation_dental_care/assets/css/style.css?v=2">

    <style>
        .patient-form-card {
            max-width: 950px;
        }

        .patient-form-heading {
            margin-bottom: 25px;
        }

        .patient-form-heading h3 {
            color: #405b75;
            font-size: 17px;
            margin-bottom: 6px;
        }

        .patient-form-heading p {
            color: #96a7b8;
            font-size: 11px;
            line-height: 1.7;
        }

        .patient-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .patient-form-group {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .patient-form-group.full-width {
            grid-column: 1 / -1;
        }

        .patient-form-group label {
            color: #52677c;
            font-size: 12px;
            font-weight: 600;
        }

        .patient-form-group input,
        .patient-form-group select {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #dce8f0;
            border-radius: 11px;
            outline: none;
            background: #fbfdff;
            color: #45586c;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            transition: 0.25s;
        }

        .patient-form-group input:focus,
        .patient-form-group select:focus {
            border-color: #86c7e9;
            background: white;
            box-shadow: 0 0 0 4px rgba(134, 199, 233, 0.15);
        }

        .patient-form-info {
            background: linear-gradient(110deg, #edf8ff, #f0fbf8);
            border: 1px solid #e0f0f5;
            padding: 15px 17px;
            border-radius: 12px;
            margin-top: 25px;
            color: #62859b;
            font-size: 11px;
            line-height: 1.8;
        }

        .patient-form-error {
            background: #fff0f1;
            color: #c65e6b;
            border: 1px solid #f8d8dc;
            padding: 13px 16px;
            border-radius: 11px;
            font-size: 12px;
            margin-bottom: 22px;
        }

        .patient-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 25px;
        }

        .patient-btn {
            display: inline-block;
            padding: 11px 22px;
            border-radius: 11px;
            text-decoration: none;
            border: none;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.25s;
        }

        .patient-btn-save {
            background: linear-gradient(120deg, #80bce2, #83d2b2);
            color: white;
            box-shadow: 0 5px 12px rgba(100, 170, 180, 0.15);
        }

        .patient-btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(100, 170, 180, 0.25);
        }

        .patient-btn-cancel {
            background: #fff0f2;
            color: #c77c8c;
            border: 1px solid #f8dce2;
        }

        .patient-btn-cancel:hover {
            background: #ffe4e9;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .patient-form-grid {
                grid-template-columns: 1fr;
            }

            .patient-form-group.full-width {
                grid-column: auto;
            }

            .patient-form-actions {
                flex-direction: column-reverse;
            }

            .patient-btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body class="admin-page">

<div class="admin-layout">

    <aside class="admin-sidebar">

        <a href="../dashboard.php" class="admin-brand">
            <img src="../../assets/images/Logo-IDC.png" alt="Logo Klinik">
            <span>INNOVATION<br>DENTAL CARE</span>
        </a>

        <div class="sidebar-label">MENU UTAMA</div>

        <nav class="admin-menu">
            <a href="../dashboard.php"><span>▦</span> Dashboard</a>
            <a href="index.php" class="active"><span>♙</span> Data Pasien</a>
            <a href="../dokter/index.php"><span>✚</span> Data Dokter</a>
            <a href="../jadwal/index.php"><span>▣</span> Jadwal Kontrol</a>
            <a href="../pengajuan/index.php"><span>↻</span> Pengajuan Perubahan</a>
        </nav>

        <div class="sidebar-bottom">
            <a href="../../auth/logout.php" onclick="return confirm('Apakah kamu yakin ingin logout?');">
                <span>↪</span> Logout
            </a>
        </div>

    </aside>

    <main class="admin-main">

        <header class="admin-topbar">
            <div>
                <span class="admin-greeting">ADMINISTRATOR PANEL</span>
                <h2>Edit Data Pasien</h2>
            </div>

            <div class="admin-profile">
                <div class="profile-icon">A</div>
                <div>
                    <strong><?= htmlspecialchars($_SESSION['nama_lengkap']); ?></strong>
                    <small>Administrator</small>
                </div>
            </div>
        </header>

        <section class="admin-welcome patient-welcome">
            <div>
                <span>PENGELOLAAN DATA PASIEN</span>
                <h1>Edit Data Pasien</h1>
                <p>Perbarui informasi akun dan perawatan pasien.</p>
            </div>

            <div class="welcome-decoration">🦷</div>
        </section>

        <section class="admin-content-card patient-form-card">

            <div class="patient-form-heading">
                <h3>Formulir Edit Pasien</h3>
                <p>Pastikan informasi pasien yang diperbarui sudah benar.</p>
            </div>

            <?php if ($pesan !== '') : ?>
                <div class="patient-form-error">
                    <?= htmlspecialchars($pesan); ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="patient-form-grid">

                    <div class="patient-form-group full-width">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap"
                            value="<?= htmlspecialchars($pasien['nama_lengkap']); ?>" required>
                    </div>

                    <div class="patient-form-group">
                        <label>Username</label>
                        <input type="text" name="username"
                            value="<?= htmlspecialchars($pasien['username']); ?>" required>
                    </div>

                    <div class="patient-form-group">
                        <label>Email</label>
                        <input type="email" name="email"
                            value="<?= htmlspecialchars($pasien['email']); ?>" required>
                    </div>

                    <div class="patient-form-group">
                        <label>Nomor Telepon</label>
                        <input type="text" name="no_telepon"
                            value="<?= htmlspecialchars($pasien['no_telepon']); ?>" required>
                    </div>

                    <div class="patient-form-group">
                        <label>Tanggal Mulai Perawatan</label>
                        <input type="date" name="tanggal_mulai_perawatan"
                            value="<?= htmlspecialchars($pasien['tanggal_mulai_perawatan'] ?? ''); ?>">
                    </div>

                    <div class="patient-form-group">
                        <label>Jenis Perawatan</label>
                        <select name="jenis_perawatan" required>
                            <option value="Behel" <?= $pasien['jenis_perawatan'] == 'Behel' ? 'selected' : ''; ?>>Behel</option>
                            <option value="Scaling" <?= $pasien['jenis_perawatan'] == 'Scaling' ? 'selected' : ''; ?>>Scaling</option>
                            <option value="Tambal Gigi" <?= $pasien['jenis_perawatan'] == 'Tambal Gigi' ? 'selected' : ''; ?>>Tambal Gigi</option>
                            <option value="Lainnya" <?= $pasien['jenis_perawatan'] == 'Lainnya' ? 'selected' : ''; ?>>Lainnya</option>
                        </select>
                    </div>

                    <div class="patient-form-group">
                        <label>Status Pasien</label>
                        <select name="status_pasien" required>
                            <option value="aktif" <?= $pasien['status_pasien'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="tidak_aktif" <?= $pasien['status_pasien'] == 'tidak_aktif' ? 'selected' : ''; ?>>Tidak Aktif</option>
                        </select>
                    </div>

                </div>

                <div class="patient-form-info">
                    <strong>Informasi:</strong> Perubahan data akan langsung diperbarui pada akun pasien yang bersangkutan.
                </div>

                <div class="patient-form-actions">
                    <a href="index.php" class="patient-btn patient-btn-cancel">Batal</a>
                    <button type="submit" class="patient-btn patient-btn-save">Simpan Perubahan</button>
                </div>

            </form>

        </section>

        <footer class="admin-footer">
            © <?= date('Y'); ?> Innovation Dental Care Pontianak
        </footer>

    </main>

</div>

</body>
</html>
