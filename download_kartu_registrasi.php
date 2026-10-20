<?php
require_once 'config.php';

if (!isset($_GET['nis'])) {
    header("Location: pendaftaran.php");
    exit;
}

$nis = sanitize($_GET['nis']);
$pendaftaran = $conn->query("SELECT * FROM pendaftaran WHERE nis = '$nis'")->fetch_assoc();

if (!$pendaftaran) {
    header("Location: pendaftaran.php");
    exit;
}

$success = isset($_GET['success']) ? true : false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Registrasi - PKBM Trisula Kusuma</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #003D82;
            --secondary-color: #00CED1;
            --accent-color: #FFD700;
            --text-dark: #1a1a1a;
            --bg-light: #f8f9fa;
            --white: #ffffff;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-light);
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .success-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: scaleIn 0.5s ease;
        }
        
        .success-icon i {
            color: white;
            font-size: 40px;
        }
        
        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
        
        .success-card h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .success-card p {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
        }
        
        .nis-display {
            background: linear-gradient(135deg, var(--primary-color), #005AA7);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .nis-display h3 {
            font-size: 16px;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .nis-number {
            font-size: 48px;
            font-weight: bold;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }
        
        .registration-card {
            background: white;
            padding: 0;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), #005AA7);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .card-header img {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
        }
        
        .card-header h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .card-header h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .card-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .card-body {
            padding: 40px;
        }
        
        .info-section {
            margin-bottom: 30px;
        }
        
        .info-section h4 {
            background: var(--secondary-color);
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .info-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
        }
        
        .info-value {
            color: var(--text-dark);
        }
        
        .photo-box {
            width: 120px;
            height: 160px;
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            overflow: hidden;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
        }
        
        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .photo-box i {
            font-size: 48px;
            color: #ccc;
        }
        
        .btn-container {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #005AA7;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,61,130,0.3);
        }
        
        .btn-success {
            background: #4CAF50;
            color: white;
        }
        
        .btn-success:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(76,175,80,0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .instructions {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 20px;
            border-radius: 6px;
            margin-top: 30px;
        }
        
        .instructions h4 {
            color: #1976D2;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .instructions ol {
            margin-left: 20px;
            color: #555;
        }
        
        .instructions li {
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .success-card,
            .btn-container,
            .instructions,
            .no-print {
                display: none !important;
            }
            
            .registration-card {
                box-shadow: none;
                border: 2px solid #000;
            }
        }
        
        @media (max-width: 768px) {
            .btn-container {
                flex-direction: column;
            }
            
            .info-row {
                grid-template-columns: 1fr;
                gap: 5px;
            }
            
            .nis-number {
                font-size: 32px;
                letter-spacing: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
        <div class="success-card no-print">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1>Pendaftaran Berhasil!</h1>
            <p>Terima kasih telah mendaftar di PKBM Trisula Kusuma</p>
            
            <div class="nis-display">
                <h3>Nomor Induk Siswa (NIS) Anda:</h3>
                <div class="nis-number"><?php echo $pendaftaran['nis']; ?></div>
            </div>
            
            <p style="margin-top: 20px; font-size: 14px;">
                <i class="fas fa-info-circle"></i> 
                Silakan simpan NIS Anda dengan baik. NIS ini akan digunakan untuk proses selanjutnya.
            </p>
        </div>
        <?php endif; ?>
        
        <div class="registration-card" id="printCard">
            <div class="card-header">
                <img src="admin/gambar/logo_pkbm-removebg-preview.png" alt="Logo PKBM">
                <h2>YAYASAN HARANA DJAGAD DHITA</h2>
                <h3>PKBM TRISULA KUSUMA</h3>
                <p>KARTU REGISTRASI PESERTA DIDIK BARU</p>
            </div>
            
            <div class="card-body">
                <div class="photo-box">
                    <?php if (!empty($pendaftaran['foto_file'])): ?>
                        <img src="uploads/pendaftaran/<?php echo $pendaftaran['foto_file']; ?>" alt="Foto">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                
                <div class="info-section">
                    <h4><i class="fas fa-id-card"></i> Informasi Pendaftaran</h4>
                    <div class="info-row">
                        <div class="info-label">No. Pendaftaran</div>
                        <div class="info-value"><strong><?php echo $pendaftaran['no_pendaftaran']; ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">NIS</div>
                        <div class="info-value"><strong style="color: var(--primary-color); font-size: 20px;"><?php echo $pendaftaran['nis']; ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Program Paket</div>
                        <div class="info-value">
                            <strong>
                                <?php 
                                $paket = $pendaftaran['paket_dipilih'];
                                echo "Paket $paket";
                                if ($paket == 'A') echo ' (Setara SD)';
                                else if ($paket == 'B') echo ' (Setara SMP)';
                                else if ($paket == 'C') echo ' (Setara SMA)';
                                ?>
                            </strong>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Tanggal Daftar</div>
                        <div class="info-value"><?php echo date('d F Y', strtotime($pendaftaran['tanggal_daftar'])); ?></div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h4><i class="fas fa-user"></i> Data Pribadi</h4>
                    <div class="info-row">
                        <div class="info-label">Nama Lengkap</div>
                        <div class="info-value"><strong><?php echo strtoupper($pendaftaran['nama_lengkap']); ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">NIK</div>
                        <div class="info-value"><?php echo $pendaftaran['nik']; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Jenis Kelamin</div>
                        <div class="info-value"><?php echo $pendaftaran['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Tempat, Tanggal Lahir</div>
                        <div class="info-value"><?php echo $pendaftaran['tempat_lahir'] . ', ' . date('d-m-Y', strtotime($pendaftaran['tanggal_lahir'])); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Agama</div>
                        <div class="info-value"><?php echo $pendaftaran['agama']; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Alamat</div>
                        <div class="info-value">
                            <?php 
                            echo $pendaftaran['alamat'] . ' RT ' . $pendaftaran['rt'] . '/RW ' . $pendaftaran['rw'];
                            if (!empty($pendaftaran['dusun'])) echo ', ' . $pendaftaran['dusun'];
                            echo ', ' . $pendaftaran['desa'] . ', ' . $pendaftaran['kecamatan'] . ', ' . $pendaftaran['kota'];
                            ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">No. HP/WA</div>
                        <div class="info-value"><?php echo $pendaftaran['no_hp']; ?></div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h4><i class="fas fa-graduation-cap"></i> Pendidikan Terakhir</h4>
                    <div class="info-row">
                        <div class="info-label">Pendidikan</div>
                        <div class="info-value"><?php echo $pendaftaran['pendidikan_terakhir']; ?></div>
                    </div>
                    <?php if (!empty($pendaftaran['asal_sekolah'])): ?>
                    <div class="info-row">
                        <div class="info-label">Asal Sekolah</div>
                        <div class="info-value"><?php echo $pendaftaran['asal_sekolah']; ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($pendaftaran['tahun_lulus'])): ?>
                    <div class="info-row">
                        <div class="info-label">Tahun Lulus</div>
                        <div class="info-value"><?php echo $pendaftaran['tahun_lulus']; ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="info-section">
                    <h4><i class="fas fa-users"></i> Data Orang Tua</h4>
                    <div class="info-row">
                        <div class="info-label">Nama Ayah</div>
                        <div class="info-value"><?php echo $pendaftaran['nama_ayah']; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Nama Ibu</div>
                        <div class="info-value"><?php echo $pendaftaran['nama_ibu']; ?></div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h4><i class="fas fa-clipboard-check"></i> Status Pendaftaran</h4>
                    <div class="info-row">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span style="background: #FFA500; color: white; padding: 4px 12px; border-radius: 4px; font-size: 13px;">
                                <?php echo strtoupper($pendaftaran['status_pendaftaran']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="btn-container no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Cetak Kartu Registrasi
            </button>
            <a href="pendaftaran.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Kembali ke Beranda
            </a>
        </div>
        
        <div class="instructions no-print">
            <h4><i class="fas fa-exclamation-triangle"></i> Langkah Selanjutnya:</h4>
            <ol>
                <li><strong>Simpan NIS Anda:</strong> <?php echo $pendaftaran['nis']; ?> - NIS ini sangat penting untuk proses selanjutnya</li>
                <li><strong>Cetak kartu registrasi</strong> dengan klik tombol "Cetak" di atas</li>
                <li><strong>Hubungi admin</strong> di 0821 1115 8525 atau email pkbmtrisulakusuma@gmail.com untuk konfirmasi</li>
                <li><strong>Tunggu verifikasi</strong> dari pihak PKBM (maksimal 3x24 jam)</li>
            </ol>
        </div>
    </div>
    
    <script>
        // Auto focus untuk print dialog jika baru saja daftar
        <?php if ($success): ?>
        setTimeout(function() {
            if (confirm('Pendaftaran berhasil! Apakah Anda ingin mencetak kartu registrasi sekarang?')) {
                window.print();
            }
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>