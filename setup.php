<?php
require_once 'config.php';

$sql = [
    "CREATE TABLE IF NOT EXISTS pendaftaran (
        id INT PRIMARY KEY AUTO_INCREMENT,
        no_pendaftaran VARCHAR(50) UNIQUE NOT NULL,
        nama_lengkap VARCHAR(100) NOT NULL,
        paket_dipilih VARCHAR(50),
        tanggal_daftar DATETIME DEFAULT CURRENT_TIMESTAMP,
        status_pendaftaran ENUM('pending', 'diproses', 'diterima', 'ditolak') DEFAULT 'pending'
    )",
    
    "CREATE TABLE IF NOT EXISTS informasi (
        id INT PRIMARY KEY AUTO_INCREMENT,
        judul VARCHAR(200),
        isi TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS dokumen (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nama_dokumen VARCHAR(200),
        file_path VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )"
];

$success = true;
foreach ($sql as $query) {
    if (!$conn->query($query)) {
        $success = false;
        echo "Error: " . $conn->error . "<br>";
    }
}

if ($success) {
    echo "✓ Database tables created successfully!";
} else {
    echo "✗ Some tables failed to create.";
}
?>
