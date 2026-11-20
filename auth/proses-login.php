
<?php
session_start();

$conn = null;
require_once __DIR__ . "/../config/database.php";

// Pastikan form dikirim menggunakan POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

// Ambil data dari form
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    echo "<script>
        alert('Username dan password wajib diisi!');
        window.location='login.php';
    </script>";
    exit;
}

// Cari akun berdasarkan username atau email
$query = "SELECT * FROM users WHERE username = ? OR email = ?";
$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param($stmt, "ss", $username, $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Periksa akun dan password
if ($user && password_verify($password, $user['password'])) {

    session_regenerate_id(true);

    $_SESSION['id_user'] = $user['id_user'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    // Arahkan sesuai role
    if ($user['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
        exit;
    } elseif ($user['role'] === 'pasien') {
        header("Location: ../pasien/dashboard.php");
        exit;
    }

}

// Jika login gagal
echo "<script>
    alert('Username atau password salah!');
    window.location='login.php';
</script>";
exit;
?>
