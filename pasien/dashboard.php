
<?php
session_start();

// Pastikan pengguna sudah login sebagai pasien
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pasien') {
    header("Location: ../auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Pasien - Innovation Dental Care</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f8fc;
            color: #40536a;
        }

        .patient-layout {
            display: flex;
            min-height: 100vh;
        }

        .patient-sidebar {
            width: 250px;
            background: #ffffff;
            padding: 28px 20px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #edf1f5;
            position: fixed;
            height: 100vh;
        }

        .patient-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            text-decoration: none;
            color: #40536a;
            margin-bottom: 45px;
        }

        .patient-brand img {
            width: 43px;
            height: 43px;
            object-fit: contain;
        }

        .patient-brand span {
            font-size: 11px;
            font-weight: 800;
            line-height: 1.5;
            letter-spacing: 0.5px;
        }

        .sidebar-label {
            font-size: 10px;
            font-weight: 700;
            color: #a3b2c1;
            margin: 0 0 15px 10px;
            letter-spacing: 1px;
        }

        .patient-menu {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .patient-menu a,
        .patient-logout a {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 14px;
            border-radius: 11px;
            text-decoration: none;
            color: #8293a5;
            font-size: 11px;
            font-weight: 500;
            transition: 0.25s;
        }

        .patient-menu a span,
        .patient-logout a span {
            font-size: 17px;
        }

        .patient-menu a:hover,
        .patient-menu a.active {
            background: linear-gradient(110deg, #e6f2ff, #e8f8f0);
            color: #548eaa;
        }

        .patient-logout {
            margin-top: auto;
        }

        .patient-logout a:hover {
            background: #fff0f2;
            color: #d77f91;
        }

        .patient-main {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 30px 38px;
        }

        .patient-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .patient-topbar h2 {
            font-size: 19px;
            color: #40536a;
        }

        .patient-topbar small {
            display: block;
            font-size: 10px;
            color: #9aabba;
            margin-top: 5px;
        }

        .patient-profile {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .profile-icon {
            width: 39px;
            height: 39px;
            border-radius: 12px;
            background: linear-gradient(130deg, #b9dcff, #c9f0df);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #527b95;
            font-weight: 700;
        }

        .patient-profile strong {
            display: block;
            font-size: 11px;
        }

        .patient-profile small {
            font-size: 9px;
            color: #9aabba;
        }

        .patient-welcome {
            background: linear-gradient(115deg, #dcecff, #dcf5e9, #fce5ef);
            padding: 35px;
            border-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .patient-welcome span {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #6d91a5;
        }

        .patient-welcome h1 {
            font-size: 25px;
            color: #405b75;
            margin: 9px 0;
        }

        .patient-welcome p {
            font-size: 11px;
            color: #71899b;
            line-height: 1.8;
        }

        .welcome-icon {
            font-size: 65px;
            opacity: 0.8;
        }

        .section-title {
            margin-bottom: 17px;
        }

        .section-title h3 {
            font-size: 15px;
            color: #40536a;
        }

        .section-title p {
            font-size: 10px;
            color: #9aabba;
            margin-top: 5px;
        }

        .patient-menu-cards {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .patient-card {
            background: #ffffff;
            padding: 25px;
            border-radius: 17px;
            border: 1px solid #edf1f5;
            text-decoration: none;
            transition: 0.25s;
        }

        .patient-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(91, 125, 155, 0.09);
        }

        .card-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            font-size: 23px;
            margin-bottom: 17px;
        }

        .card-blue {
            background: #e6f2ff;
        }

        .card-green {
            background: #e5f7ec;
        }

        .patient-card h4 {
            font-size: 13px;
            color: #40536a;
            margin-bottom: 7px;
        }

        .patient-card p {
            font-size: 10px;
            color: #91a2b2;
            line-height: 1.8;
        }

        .card-link {
            display: inline-block;
            margin-top: 16px;
            font-size: 10px;
            font-weight: 600;
            color: #75a9c5;
        }

        .patient-footer {
            margin-top: 35px;
            text-align: center;
            color: #a5b2bf;
            font-size: 10px;
        }

        @media (max-width: 768px) {
            .patient-sidebar {
                width: 75px;
                padding: 20px 10px;
            }

            .patient-brand {
                justify-content: center;
            }

            .patient-brand span,
            .sidebar-label,
            .patient-menu a:not(.active),
            .patient-menu a,
            .patient-logout a {
                font-size: 0;
            }

            .patient-brand img {
                width: 40px;
            }

            .patient-menu a,
            .patient-logout a {
                justify-content: center;
                padding: 13px 5px;
            }

            .patient-menu a span,
            .patient-logout a span {
                font-size: 19px;
            }

            .patient-main {
                margin-left: 75px;
                width: calc(100% - 75px);
                padding: 22px 15px;
            }

            .patient-welcome {
                padding: 24px 20px;
            }

            .patient-welcome h1 {
                font-size: 19px;
            }

            .welcome-icon {
                font-size: 42px;
            }

            .patient-menu-cards {
                grid-template-columns: 1fr;
            }

            .patient-topbar h2 {
                font-size: 15px;
            }
        }
    </style>
</head>

<body>

<div class="patient-layout">

    <aside class="patient-sidebar">

        <a href="dashboard.php" class="patient-brand">
            <img src="../assets/images/Logo-IDC.png" alt="Logo Klinik">
            <span>INNOVATION<br>DENTAL CARE</span>
        </a>

        <div class="sidebar-label">MENU PASIEN</div>

        <nav class="patient-menu">
            <a href="dashboard.php" class="active">
                <span>▦</span> Dashboard
            </a>

            <a href="jadwal.php">
                <span>▣</span> Jadwal Kontrol
            </a>

            <a href="konfirmasi.php">
                <span>✓</span> Konfirmasi Kehadiran
            </a>
        </nav>

        <div class="patient-logout">
            <a href="../auth/logout.php" onclick="return confirm('Apakah kamu yakin ingin logout?');">
                <span>↪</span> Logout
            </a>
        </div>

    </aside>

    <main class="patient-main">

        <header class="patient-topbar">
            <div>
                <h2>Dashboard Pasien</h2>
                <small>Selamat datang di layanan pasien Innovation Dental Care</small>
            </div>

            <div class="patient-profile">
                <div class="profile-icon">P</div>
                <div>
                    <strong><?= htmlspecialchars($_SESSION['nama_lengkap']); ?></strong>
                    <small>Pasien</small>
                </div>
            </div>
        </header>

        <section class="patient-welcome">
            <div>
                <span>PATIENT PORTAL</span>

                <h1>
                    Halo, <?= htmlspecialchars($_SESSION['nama_lengkap']); ?>!
                </h1>

                <p>
                    Selamat datang di portal pasien Innovation Dental Care.
                    Pantau jadwal kontrol dan konfirmasi kehadiranmu dengan mudah.
                </p>
            </div>

            <div class="welcome-icon">🦷</div>
        </section>

        <div class="section-title">
            <h3>Menu Pasien</h3>
            <p>Pilih layanan yang ingin kamu gunakan.</p>
        </div>

        <section class="patient-menu-cards">

            <a href="jadwal.php" class="patient-card">
                <div class="card-icon card-blue">🗓️</div>

                <h4>Jadwal Kontrol</h4>

                <p>
                    Lihat informasi tanggal, waktu, dokter, dan status jadwal kontrolmu.
                </p>

                <span class="card-link">Lihat Jadwal →</span>
            </a>

            <a href="konfirmasi.php" class="patient-card">
                <div class="card-icon card-green">✓</div>

                <h4>Konfirmasi Kehadiran</h4>

                <p>
                    Konfirmasikan kehadiran pada jadwal kontrol yang telah diberikan.
                </p>

                <span class="card-link">Konfirmasi Sekarang →</span>
            </a>

        </section>

        <footer class="patient-footer">
            © <?= date('Y'); ?> Innovation Dental Care Pontianak
        </footer>

    </main>

</div>

</body>
</html>
