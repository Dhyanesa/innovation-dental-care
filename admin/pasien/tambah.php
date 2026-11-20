
<?php
session_start();

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

require_once __DIR__ . "/../../config/database.php";

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $telepon = trim($_POST['no_telepon'] ?? '');
    $tanggal_mulai = $_POST['tanggal_mulai_perawatan'] ?: null;
    $jenis_perawatan = $_POST['jenis_perawatan'] ?? 'Behel';

    if (
        $nama === '' || $username === '' || $email === '' ||
        $password === '' || $telepon === ''
    ) {
        $pesan = "Semua data wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan = "Format email tidak valid!";
    } elseif (strlen($password) < 8) {
        $pesan = "Password minimal 8 karakter!";
    } else {

        // Periksa username dan email
        $cek = mysqli_prepare(
            $conn,
            "SELECT id_user FROM users WHERE username = ? OR email = ?"
        );

        mysqli_stmt_bind_param($cek, "ss", $username, $email);
        mysqli_stmt_execute($cek);

        $hasil = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasil) > 0) {
            $pesan = "Username atau email sudah digunakan!";
        } else {

            mysqli_begin_transaction($conn);

            try {
                // Buat akun pasien
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $query_user = mysqli_prepare(
                    $conn,
                    "INSERT INTO users
                    (nama_lengkap, username, email, password, role)
                    VALUES (?, ?, ?, ?, 'pasien')"
                );

                mysqli_stmt_bind_param(
                    $query_user,
                    "ssss",
                    $nama,
                    $username,
                    $email,
                    $hash
                );

                if (!mysqli_stmt_execute($query_user)) {
                    throw new Exception("Gagal membuat akun pasien.");
                }

                $id_user = mysqli_insert_id($conn);

                // Simpan data pasien
                $query_pasien = mysqli_prepare(
                    $conn,
                    "INSERT INTO pasien
                    (id_user, no_telepon, tanggal_mulai_perawatan, jenis_perawatan)
                    VALUES (?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $query_pasien,
                    "isss",
                    $id_user,
                    $telepon,
                    $tanggal_mulai,
                    $jenis_perawatan
                );

                if (!mysqli_stmt_execute($query_pasien)) {
                    throw new Exception("Gagal menyimpan data pasien.");
                }

                mysqli_commit($conn);

                echo "<script>
                    alert('Pasien berhasil ditambahkan!');
                    window.location='index.php';
                </script>";
                exit;

            } catch (Exception $e) {
                mysqli_rollback($conn);
                $pesan = $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pasien - Innovation Dental Care</title>
</head>
<body>

    <h1>Tambah Pasien</h1>

    <?php if ($pesan !== '') { ?>
        <p style="color:red;">
            <?php echo htmlspecialchars($pesan); ?>
        </p>
    <?php } ?>

    <form method="POST">

        <label>Nama Lengkap</label><br>
        <input type="text" name="nama_lengkap" required>
        <br><br>

        <label>Username</label><br>
        <input type="text" name="username" required>
        <br><br>

        <label>Email</label><br>
        <input type="email" name="email" required>
        <br><br>

        <label>Password Akun</label><br>
        <input type="password" name="password" minlength="8" required>
        <br><br>

        <label>Nomor Telepon</label><br>
        <input type="text" name="no_telepon" required>
        <br><br>

        <label>Tanggal Mulai Perawatan</label><br>
        <input type="date" name="tanggal_mulai_perawatan">
        <br><br>

        <label>Jenis Perawatan</label><br>
        <select name="jenis_perawatan" required>
            <option value="Behel">Behel</option>
            <option value="Kontrol Gigi">Kontrol Gigi</option>
            <option value="Lainnya">Lainnya</option>
        </select>
        <br><br>

        <button type="submit">Simpan Pasien</button>
        <a href="index.php">Batal</a>

    </form>

</body>
</html>
