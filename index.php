
<?php
session_start();

// Jika sudah login, arahkan sesuai role
if (isset($_SESSION['id_user'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'pasien') {
        header("Location: pasien/dashboard.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Innovation Dental Care Pontianak</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<header class="navbar">
    <div class="container">

        <a href="index.php" class="brand">
            <img src="assets/images/Logo-IDC.png" alt="Logo Innovation Dental Care">
            <span>INNOVATION<br>DENTAL CARE</span>
        </a>

        <nav>
            <a href="#beranda">Beranda</a>
            <a href="#tentang">Tentang</a>
            <a href="#layanan">Layanan</a>
            <a href="#dokter">Jadwal Dokter</a>
            <a href="auth/login.php" class="btn">Login</a>
        </nav>

    </div>
</header>

<!-- HERO -->
<section class="hero hero-modern" id="beranda">
    <div class="container hero-content">

        <div class="hero-text">
            <span class="hero-label">WELCOME TO INNOVATION DENTAL CARE</span>

            <h1>
                Senyum Sehat,<br>
                <span>Percaya Diri Setiap Hari!</span>
            </h1>

            <p>
                Selamat datang di Innovation Dental Care Pontianak.
                Kami hadir untuk membantu menjaga kesehatan gigi
                dan menciptakan senyum yang lebih indah.
            </p>

            <div class="hero-info">
                <span>✦ Perawatan Berkualitas</span>
                <span>✦ Senyum Lebih Percaya Diri</span>
            </div>
        </div>

        <div class="hero-visual">
            <div class="hero-circle"></div>

            <img
                src="assets/images/dokter.png"
                alt="Dokter gigi Innovation Dental Care"
                class="doctor-image"
            >

            <div class="floating-bubble bubble-one">✦</div>
            <div class="floating-bubble bubble-two">♡</div>

            <div class="hero-caption">
                <span>💙</span>
                <div>
                    <strong>Senyum Sehat</strong>
                    <small>Dimulai dari perawatan yang tepat</small>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- TENTANG KAMI -->
<section class="section about-section" id="tentang">
    <div class="container about-content">

        <div class="about-image">
            <div class="about-image-decoration"></div>
            <img
                src="assets/images/klinik.png"
                alt="Klinik Innovation Dental Care"
            >
            <div class="about-image-label">
                Innovation Dental Care Pontianak
            </div>
        </div>

        <div class="about-text">
            <span class="section-label">KENALI KAMI</span>

            <h2>Tentang Kami</h2>

            <p class="about-subtitle">
                Mengenal Innovation Dental Care Pontianak
            </p>

            <p>
                Innovation Dental Care Pontianak merupakan klinik
                perawatan gigi yang menyediakan layanan untuk
                membantu pasien menjaga kesehatan dan keindahan
                senyum.
            </p>

            <p>
                Kami juga menyediakan layanan kontrol berkala
                bagi pasien yang sedang menjalani perawatan gigi,
                sehingga perkembangan perawatan dapat dipantau
                dengan lebih teratur.
            </p>
        </div>

    </div>
</section>

<!-- LAYANAN -->
<section class="section service-section" id="layanan">
    <div class="container">

        <div class="section-title">
            <span class="section-label">LAYANAN KAMI</span>
            <h2>Perawatan untuk Senyum Lebih Sehat</h2>
            <p>Kami membantu menjaga kesehatan dan keindahan senyum Anda.</p>
        </div>

        <div class="grid">

            <div class="card card-blue service-card">
                <div class="service-icon">🦷</div>
                <h3>Perawatan Behel</h3>
                <p>
                    Perawatan dan kontrol berkala untuk membantu
                    merapikan susunan gigi.
                </p>
            </div>

            <div class="card card-green service-card">
                <div class="service-icon">✨</div>
                <h3>Perawatan Gigi</h3>
                <p>
                    Perawatan gigi untuk membantu menjaga
                    kesehatan dan kebersihan gigi.
                </p>
            </div>

            <div class="card card-pink service-card">
                <div class="service-icon">📅</div>
                <h3>Kontrol Berkala</h3>
                <p>
                    Pemantauan perkembangan perawatan gigi
                    sesuai jadwal kontrol pasien.
                </p>
            </div>

        </div>
    </div>
</section>

<!-- JADWAL DOKTER -->
<section class="section doctor-section" id="dokter">
    <div class="container">

        <div class="section-title">
            <span class="section-label">TIM DOKTER KAMI</span>
            <h2>Jadwal Praktik Dokter</h2>
            <p>Kenali jadwal praktik dokter Innovation Dental Care.</p>
        </div>

        <div class="grid">

            <div class="card card-blue doctor-card">
                <div class="doctor-icon">👩‍⚕️</div>
                <h3>drg. Trisnawati</h3>
                <p>Senin dan Rabu</p>
                <strong>18.00 – 21.00 WIB</strong>
            </div>

            <div class="card card-green doctor-card">
                <div class="doctor-icon">👩‍⚕️</div>
                <h3>drg. Serina</h3>
                <p>Selasa dan Jumat</p>
                <strong>18.00 – 21.00 WIB</strong>
            </div>

            <div class="card card-yellow doctor-card">
                <div class="doctor-icon">👩‍⚕️</div>
                <h3>drg. Teisya</h3>
                <p>Kamis dan Sabtu</p>
                <strong>18.00 – 21.00 WIB</strong>
            </div>

        </div>

        <p class="schedule-note">
            Jam praktik klinik: Senin–Sabtu, pukul 18.00–21.00 WIB.
            Minggu libur.
        </p>

    </div>
</section>

<!-- LOGIN -->
<section class="section login-section">
    <div class="container">

        <div class="login-banner">
            <div>
                <span class="section-label">AREA PASIEN</span>
                <h2>Sudah Terdaftar sebagai Pasien?</h2>
                <p>
                    Masuk ke akun untuk melihat jadwal kontrol
                    dan melakukan konfirmasi kehadiran.
                </p>
            </div>

            <a href="auth/login.php" class="btn login-banner-btn">
                Login Pasien →
            </a>
        </div>

    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <h3>Innovation Dental Care Pontianak</h3>
        <p>Wujudkan senyum sehat dan percaya diri.</p>
        <p>&copy; <?= date('Y'); ?> Innovation Dental Care. All Rights Reserved.</p>
    </div>
</footer>

</body>
</html>
