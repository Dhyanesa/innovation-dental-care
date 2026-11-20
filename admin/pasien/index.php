
<?php
session_start();

// Cek hak akses admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

// Koneksi database
require_once __DIR__ . "/../../config/database.php";

// Ambil data pasien
$query = "SELECT
            pasien.id_pasien,
            users.nama_lengkap,
            users.username,
            users.email,
            pasien.no_telepon,
            pasien.jenis_perawatan,
            pasien.status_pasien
          FROM pasien
          INNER JOIN users
          ON pasien.id_user = users.id_user
          ORDER BY pasien.id_pasien DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Gagal mengambil data pasien: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Pasien - Innovation Dental Care</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background: #f5f7fa;
        }

        h1 {
            color: #243b53;
        }

        .container {
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px 5px 15px 0;
        }

        .btn-tambah {
            background: #287a78;
            color: white;
        }

        .btn-kembali {
            background: #64748b;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background: #287a78;
            color: white;
        }

        tr:nth-child(even) {
            background: #f2f7f7;
        }

        .kosong {
            text-align: center;
            padding: 20px;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Data Pasien</h1>
    <p>Daftar pasien Innovation Dental Care</p>

    <a href="../dashboard.php" class="btn btn-kembali">
        Kembali ke Dashboard
    </a>

    <a href="tambah.php" class="btn btn-tambah">
        + Tambah Pasien
    </a>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Pasien</th>
                <th>Username</th>
                <th>Email</th>
                <th>No. Telepon</th>
                <th>Jenis Perawatan</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>

        <?php
        $no = 1;

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
        ?>

            <tr>
                <td><?php echo $no++; ?></td>

                <td>
                    <?php echo htmlspecialchars($row['nama_lengkap']); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($row['username']); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($row['email']); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($row['no_telepon']); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($row['jenis_perawatan']); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($row['status_pasien']); ?>
                </td>
            </tr>

        <?php
            }
        } else {
        ?>

            <tr>
                <td colspan="7" class="kosong">
                    Belum ada data pasien.
                </td>
            </tr>

        <?php } ?>

        </tbody>
    </table>

</div>

</body>
</html>
