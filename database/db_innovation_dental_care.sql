
CREATE DATABASE IF NOT EXISTS db_innovation_dental_care
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE db_innovation_dental_care;

-- 1. Tabel users
CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'pasien') NOT NULL DEFAULT 'pasien',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabel pasien
CREATE TABLE pasien (
    id_pasien INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL UNIQUE,
    no_telepon VARCHAR(20) NOT NULL,
    tanggal_mulai_perawatan DATE,
    jenis_perawatan VARCHAR(50) NOT NULL DEFAULT 'Behel',
    status_pasien ENUM('aktif', 'tidak_aktif')
        NOT NULL DEFAULT 'aktif',

    CONSTRAINT fk_pasien_users
    FOREIGN KEY (id_user)
    REFERENCES users(id_user)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Tabel dokter
CREATE TABLE dokter (
    id_dokter INT AUTO_INCREMENT PRIMARY KEY,
    nama_dokter VARCHAR(100) NOT NULL,
    hari_praktik ENUM(
        'Senin',
        'Selasa',
        'Rabu',
        'Kamis',
        'Jumat',
        'Sabtu'
    ) NOT NULL,
    jam_mulai TIME NOT NULL DEFAULT '18:00:00',
    jam_selesai TIME NOT NULL DEFAULT '21:00:00',
    status_dokter ENUM('aktif', 'tidak_aktif')
        NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB;

-- 4. Tabel jadwal_kontrol
CREATE TABLE jadwal_kontrol (
    id_jadwal INT AUTO_INCREMENT PRIMARY KEY,
    id_pasien INT NOT NULL,
    id_dokter INT NOT NULL,
    tanggal_kontrol DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    status_konfirmasi ENUM(
        'menunggu',
        'hadir',
        'mengajukan_perubahan'
    ) NOT NULL DEFAULT 'menunggu',
    status_kontrol ENUM(
        'terjadwal',
        'selesai',
        'dibatalkan'
    ) NOT NULL DEFAULT 'terjadwal',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_jadwal_pasien
    FOREIGN KEY (id_pasien)
    REFERENCES pasien(id_pasien)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

    CONSTRAINT fk_jadwal_dokter
    FOREIGN KEY (id_dokter)
    REFERENCES dokter(id_dokter)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 5. Tabel pengajuan_perubahan
CREATE TABLE pengajuan_perubahan (
    id_pengajuan INT AUTO_INCREMENT PRIMARY KEY,
    id_jadwal INT NOT NULL,
    tanggal_usulan DATE NOT NULL,
    jam_mulai_usulan TIME NOT NULL,
    jam_selesai_usulan TIME NOT NULL,
    alasan TEXT NOT NULL,
    status_pengajuan ENUM(
        'menunggu',
        'disetujui',
        'ditolak'
    ) NOT NULL DEFAULT 'menunggu',
    catatan_admin TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pengajuan_jadwal
    FOREIGN KEY (id_jadwal)
    REFERENCES jadwal_kontrol(id_jadwal)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- Data awal jadwal praktik dokter
INSERT INTO dokter
(nama_dokter, hari_praktik, jam_mulai, jam_selesai)
VALUES
('drg. Trisnawati', 'Senin', '18:00:00', '21:00:00'),
('drg. Trisnawati', 'Rabu', '18:00:00', '21:00:00'),
('drg. Serina', 'Selasa', '18:00:00', '21:00:00'),
('drg. Serina', 'Jumat', '18:00:00', '21:00:00'),
('drg. Teisya', 'Kamis', '18:00:00', '21:00:00'),
('drg. Teisya', 'Sabtu', '18:00:00', '21:00:00');
