<?php
require_once 'config.php';

// Validasi parameter NIS
if (!isset($_GET['nis']) || empty($_GET['nis'])) {
    die("<h2>Error</h2><p>Parameter NIS tidak ditemukan. <a href='pendaftaran.php'>Kembali ke halaman pendaftaran</a></p>");
}

$nis = sanitize($_GET['nis']);

// Ambil data pendaftaran berdasarkan NIS
$stmt = $conn->prepare("SELECT * FROM pendaftaran WHERE nis = ?");
$stmt->bind_param("s", $nis);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<h2>Error</h2><p>Data pendaftaran dengan NIS <strong>$nis</strong> tidak ditemukan. <a href='pendaftaran.php'>Kembali ke halaman pendaftaran</a></p>");
}

$data = $result->fetch_assoc();
$stmt->close();

// Ambil profil PKBM
$profil = $conn->query("SELECT * FROM profil_pkbm LIMIT 1")->fetch_assoc();

// Format tanggal
function formatTanggal($date) {
    if (!$date) return '-';
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $timestamp = strtotime($date);
    return date('d', $timestamp) . ' ' . $bulan[date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

// Format paket
$paket_label = [
    'A' => 'Paket A (Setara SD)',
    'B' => 'Paket B (Setara SMP)',
    'C' => 'Paket C (Setara SMA)'
];
$paket_text = isset($paket_label[$data['paket_dipilih']]) ? $paket_label[$data['paket_dipilih']] : $data['paket_dipilih'];
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
            --border-color: #e0e0e0;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: var(--bg-light);
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .success-alert {
            background: #d4edda;
            color: #155724;
            padding: 20px 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 2px solid #c3e6cb;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .success-alert i {
            font-size: 32px;
        }
        
        .success-alert-content h3 {
            margin-bottom: 5px;
            font-size: 18px;
        }
        
        .success-alert-content p {
            font-size: 14px;
            margin-bottom: 0;
        }
        
        .card-wrapper {
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
            border: 2px solid var(--primary-color);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #005AA7 100%);
            color: var(--white);
            padding: 30px;
            text-align: center;
        }
        
        .card-header .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            background: var(--white);
            border-radius: 50%;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .card-header .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .card-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
            font-weight: 700;
        }
        
        .card-header h2 {
            font-size: 18px;
            font-weight: 600;
            opacity: 0.95;
        }
        
        .card-header .subtitle {
            font-size: 12px;
            margin-top: 5px;
            opacity: 0.9;
        }
        
        .card-body {
            padding: 40px;
        }
        
        .card-title {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--secondary-color);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid var(--primary-color);
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-size: 16px;
            color: var(--text-dark);
            font-weight: 600;
        }
        
        .info-value.highlight {
            color: var(--primary-color);
            font-size: 20px;
        }
        
        .next-steps {
            background: #e3f2fd;
            border: 2px solid #2196F3;
            border-radius: 8px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .next-steps h3 {
            color: #1976D2;
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .next-steps h3 i {
            font-size: 24px;
        }
        
        .next-steps ol {
            margin-left: 20px;
            color: #1565C0;
        }
        
        .next-steps li {
            margin-bottom: 12px;
            line-height: 1.8;
        }
        
        .next-steps li strong {
            color: #0d47a1;
        }
        
        .contact-info {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
        }
        
        .contact-info h4 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .contact-info p {
            color: #856404;
            margin-bottom: 5px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            flex: 1;
            min-width: 200px;
            padding: 15px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background: #005AA7;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,61,130,0.3);
        }
        
        .btn-secondary {
            background: var(--secondary-color);
            color: var(--white);
        }
        
        .btn-secondary:hover {
            background: #00b8bb;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,206,209,0.3);
        }
        
        .btn-success {
            background: #28a745;
            color: var(--white);
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40,167,69,0.3);
        }
        
        .nis-highlight {
            background: linear-gradient(135deg, var(--primary-color), #005AA7);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        
        .nis-highlight h4 {
            font-size: 14px;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .nis-highlight .nis-number {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 6px;
            font-family: 'Courier New', monospace;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .success-alert,
            .action-buttons,
            .no-print {
                display: none !important;
            }
            
            .card-wrapper {
                box-shadow: none;
                border: 2px solid #000;
            }
            
            .card-body {
                padding: 30px;
            }
            
            @page {
                margin: 1cm;
            }
        }
        
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .card-body {
                padding: 25px 20px;
            }
            
            .nis-highlight .nis-number {
                font-size: 24px;
                letter-spacing: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        <div class="success-alert">
            <i class="fas fa-check-circle"></i>
            <div class="success-alert-content">
                <h3>Pendaftaran Berhasil!</h3>
                <p>Terima kasih telah mendaftar di PKBM Trisula Kusuma. Silakan simpan kartu registrasi Anda.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card-wrapper" id="kartu-registrasi">
            <div class="card-header">
                <div class="logo">
                    <img src="admin/gambar/logo_pkbm-removebg-preview.png" alt="Logo PKBM">
                </div>
                <h1>YAYASAN HARANA DJAGAD DHITA</h1>
                <h2>PUSAT KEGIATAN BELAJAR MASYARAKAT (PKBM)</h2>
                <h2>TRISULA KUSUMA</h2>
                <div class="subtitle">Izin Penyelenggaraan Pendidikan Masyarakat No. 04/PKBM/DPMPTSP/VIII/2021</div>
            </div>
            
            <div class="card-body">
                <div class="card-title">KARTU REGISTRASI PESERTA DIDIK BARU</div>
                
                <div class="nis-highlight">
                    <h4>NOMOR INDUK SISWA (NIS)</h4>
                    <div class="nis-number"><?php echo htmlspecialchars($data['nis']); ?></div>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">No. Pendaftaran</div>
                        <div class="info-value highlight"><?php echo htmlspecialchars($data['no_pendaftaran']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Tanggal Pendaftaran</div>
                        <div class="info-value"><?php echo formatTanggal($data['tanggal_daftar']); ?></div>
                    </div>
                    
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <div class="info-label">Nama Lengkap</div>
                        <div class="info-value highlight"><?php echo strtoupper(htmlspecialchars($data['nama_lengkap'])); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">NIK</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['nik']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Tempat, Tanggal Lahir</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['tempat_lahir']) . ', ' . formatTanggal($data['tanggal_lahir']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Paket yang Dipilih</div>
                        <div class="info-value"><?php echo htmlspecialchars($paket_text); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">No. HP/WhatsApp</div>
                        <div class="info-value"><?php echo htmlspecialchars($data['no_hp'] ?? '-'); ?></div>
                    </div>
                    
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <div class="info-label">Alamat</div>
                        <div class="info-value"><?php 
                            echo htmlspecialchars($data['alamat']) . ' RT ' . htmlspecialchars($data['rt']) . '/RW ' . htmlspecialchars($data['rw']);
                            if (!empty($data['dusun'])) echo ', ' . htmlspecialchars($data['dusun']);
                            echo ', ' . htmlspecialchars($data['desa']) . ', ' . htmlspecialchars($data['kecamatan']) . ', ' . htmlspecialchars($data['kota']);
                        ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Status Pendaftaran</div>
                        <div class="info-value" style="color: #FFA500;"><?php echo strtoupper($data['status_pendaftaran']); ?></div>
                    </div>
                </div>
                
                <div class="next-steps">
                    <h3>
                        <i class="fas fa-tasks"></i>
                        Langkah Selanjutnya
                    </h3>
                    <ol>
                        <li><strong>Simpan NIS Anda:</strong> <?php echo htmlspecialchars($data['nis']); ?> - NIS ini sangat penting untuk proses selanjutnya</li>
                        <li><strong>Cetak atau download kartu registrasi ini</strong> sebagai bukti pendaftaran Anda</li>
                        <li><strong>Hubungi admin PKBM</strong> untuk konfirmasi dan informasi lebih lanjut</li>
                        <li><strong>Tunggu proses verifikasi</strong> dari pihak PKBM (maksimal 3x24 jam)</li>
                        <li><strong>Anda akan dihubungi</strong> oleh admin melalui nomor yang telah didaftarkan</li>
                    </ol>
                    
                    <div class="contact-info">
                        <h4><i class="fas fa-phone-alt"></i> Informasi Kontak PKBM Trisula Kusuma:</h4>
                        <p><strong>Alamat:</strong> <?php echo htmlspecialchars($profil['alamat'] ?? 'Jalan Bail\'tahyat, Ohoi Mun Ngurditwain, Kec. Kei Besar Utara Barat, Kabupaten Maluku Tenggara'); ?></p>
                        <p><strong>Telepon/WhatsApp:</strong> 0821 1115 8525</p>
                        <p><strong>Email:</strong> pkbmtrisulakusuma@gmail.com</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="action-buttons no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i>
                Print Kartu Registrasi
            </button>
            <button onclick="downloadCard()" class="btn btn-secondary">
                <i class="fas fa-download"></i>
                Download PDF
            </button>
            <a href="pendaftaran.php" class="btn btn-success">
                <i class="fas fa-home"></i>
                Kembali ke Beranda
            </a>
        </div>
    </div>
    
    <script>
        function downloadCard() {
            // Menggunakan window.print() untuk download sebagai PDF
            // Browser akan memberikan opsi untuk save as PDF
            window.print();
        }
        
        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        // Optional: Auto print dialog
        window.onload = function() { 
            setTimeout(() => {
                if (confirm('Pendaftaran berhasil! Apakah Anda ingin mencetak kartu registrasi sekarang?')) {
                    window.print();
                }
            }, 1000);
        };
        <?php endif; ?>
    </script>
</body>
</html>