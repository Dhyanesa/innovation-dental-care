
<?php
session_start();

if (isset($_SESSION['id_user'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: ../admin/dashboard.php");
        exit;
    } else {
        header("Location: ../pasien/dashboard.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Innovation Dental Care</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-page">

    <div class="login-container">

        <div class="login-card">

            <a href="../index.php" class="login-logo">
                <img src="../assets/images/Logo-IDC.png"
                     alt="Logo Innovation Dental Care">
            </a>

            <h2>Selamat Datang!</h2>

            <p class="login-description">
                Silakan masuk ke akun Innovation Dental Care
            </p>

            <form action="proses-login.php" method="POST">

                <div class="login-input-group">
                    <label>Username atau Email</label>
                    <input
                        type="text"
                        name="username"
                        placeholder="Masukkan username atau email"
                        required
                    >
                </div>

                <div class="login-input-group">
                    <label>Password</label>
                    <input
                        type="password"
                        name="password"
                        placeholder="Masukkan password"
                        required
                    >
                </div>

                <button type="submit" class="login-submit">
                    Login
                </button>

            </form>

            <a href="../index.php" class="back-home">
                ← Kembali ke Beranda
            </a>

        </div>

        <p class="login-footer">
            © <?= date('Y'); ?> Innovation Dental Care Pontianak
        </p>

    </div>

</body>
</html>
