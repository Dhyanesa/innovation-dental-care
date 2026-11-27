
<?php
session_start();

// Memastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

require_once __DIR__ . "/../../config/database.php";

// Mengambil ID pasien
$id_pasien = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_pasien <= 0) {
    header("Location: index.php");
    exit;
}

// Membuat token keamanan
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Mengambil data pasien
$query = "SELECT pasien.id_pasien, pasien.id_user,
                 users.nama_lengkap
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

// Memeriksa apakah pasien memiliki jadwal kontrol
$query_jadwal = "SELECT COUNT(*) AS total
                 FROM jadwal_kontrol
                 WHERE id_pasien = ?";

$stmt_jadwal = mysqli_prepare($conn, $query_jadwal);
mysqli_stmt_bind_param($stmt_jadwal, "i", $id_pasien);
mysqli_stmt_execute($stmt_jadwal);

$result_jadwal = mysqli_stmt_get_result($stmt_jadwal);
$data_jadwal = mysqli_fetch_assoc($result_jadwal);

$ada_jadwal = $data_jadwal['total'] > 0;

// Proses penghapusan ketika tombol ditekan
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $pesan = "Permintaan tidak valid. Silakan coba kembali.";
    } elseif ($ada_jadwal) {
        $pesan = "Pasien tidak dapat dihapus karena masih memiliki riwayat jadwal kontrol. Ubah status pasien menjadi tidak aktif melalui menu Edit.";
    } else {

        mysqli_begin_transaction($conn);

        try {
            // Menghapus data pasien
            $hapus_pasien = mysqli_prepare(
                $conn,
                "DELETE FROM pasien WHERE id_pasien = ?"
            );

            mysqli_stmt_bind_param(
                $hapus_pasien,
                "i",
                $id_pasien
            );

            if (!mysqli_stmt_execute($hapus_pasien)) {
                throw new Exception("Gagal menghapus data pasien.");
            }

            // Menghapus akun pengguna pasien
            $hapus_user = mysqli_prepare(
                $conn,
                "DELETE FROM users WHERE id_user = ?"
            );

            mysqli_stmt_bind_param(
                $hapus_user,
                "i",
                $pasien['id_user']
            );

            if (!mysqli_stmt_execute($hapus_user)) {
                throw new Exception("Gagal menghapus akun pasien.");
            }

            mysqli_commit($conn);

            unset($_SESSION['csrf_token']);

            echo "<script>
                alert('Data pasien berhasil dihapus!');
                window.location.href = 'index.php';
            </script>";
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $pesan = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Data Pasien</title>
</head>
<body>

    <h2>Hapus Data Pasien</h2>

    <?php if ($pesan != "") : ?>
        <p style="color: red;">
            <?= htmlspecialchars($pesan); ?>
        </p>
    <?php endif; ?>

    <p>
        Apakah kamu yakin ingin menghapus data pasien
        <strong>
            <?= htmlspecialchars($pasien['nama_lengkap']); ?>
        </strong>?
    </p>

    <?php if ($ada_jadwal) : ?>

        <p style="color: red;">
            Pasien ini masih memiliki riwayat jadwal kontrol.
            Data tidak dapat dihapus.
            Silakan ubah status pasien menjadi tidak aktif.
        </p>

        <a href="edit.php?id=<?= $id_pasien; ?>">
            Edit Status Pasien
        </a>

    <?php else : ?>

        <form method="POST">
            <input type="hidden" name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

            <button type="submit">
                Ya, Hapus Pasien
            </button>

            <a href="index.php">Batal</a>
        </form>

    <?php endif; ?>

</body>
</html>
