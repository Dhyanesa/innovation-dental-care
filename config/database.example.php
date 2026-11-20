
<?php

// Contoh konfigurasi database untuk pengguna lain

$host = "localhost";
$username = "root";
$password = " ";
$database = "db_innovation_dental_care";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

?>
