
<?php
session_start();

// Pastikan pengguna sudah login sebagai admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Innovation Dental Care</title>
</head>
<body>

    <h1>Dashboard Admin</h1>

    <h3>
        Selamat datang, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>!
    </h3>

    <p>Anda berhasil login sebagai Administrator.</p>

</body>
</html>
