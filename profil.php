<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_pkbm = sanitize($_POST['nama_pkbm']);
    $visi = sanitize($_POST['visi']);
    $misi = sanitize($_POST['misi']);
    $sejarah = sanitize($_POST['sejarah']);
    $alamat = sanitize($_POST['alamat']);
    $telepon = sanitize($_POST['telepon']);
    $email = sanitize($_POST['email']);
    $website = sanitize($_POST['website']);
    
    $stmt = $conn->prepare("UPDATE profil_pkbm SET nama_pkbm=?, visi=?, misi=?, sejarah=?, alamat=?, telepon=?, email=?, website=? WHERE id=1");
    $stmt->bind_param("ssssssss", $nama_pkbm, $visi, $misi, $sejarah, $alamat, $telepon, $email, $website);
    
    if ($stmt->execute()) {
        $success = "Profil berhasil diperbarui!";
        logActivity('Memperbarui profil PKBM');
    } else {
        $error = "Gagal memperbarui profil!";
    }
}

 $profil = $conn->query("SELECT * FROM profil_pkbm LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Profil - Admin PKBM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #003D82;
            --primary-dark: #002D61;
            --primary-deep: #001A3D;
            --primary-light: #E8F0FA;
            --secondary-color: #00CED1;
            --secondary-dark: #00B8D4;
            --accent-color: #FFD700;
            --gold-gradient: linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #FFD700 100%);
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --text-dark: #1a1a2e;
            --text-light: #5a6377;
            --bg-light: #f4f6f9;
            --white: #ffffff;
            --border-color: #e5e9f0;
            --shadow-sm: 0 2px 8px rgba(13, 38, 76, 0.06);
            --shadow-md: 0 8px 24px rgba(13, 38, 76, 0.08);
            --shadow-lg: 0 16px 40px rgba(13, 38, 76, 0.12);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        html { scroll-behavior: smooth; }
        
        body {
            font-family: 'Poppins', 'Segoe UI', Tahoma, sans-serif;
            background: var(--bg-light);
            font-size: 14px;
            color: var(--text-dark);
        }
        
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* ============ SIDEBAR (KONSISTEN) ============ */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--primary-deep) 0%, var(--primary-dark) 50%, var(--primary-color) 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }
        
        .sidebar-header {
            padding: 32px 20px;
            background: rgba(0,0,0,0.25);
            text-align: center;
            border-bottom: 1px solid rgba(0,206,209,0.3);
            position: relative;
        }
        
        .sidebar-header::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 25%;
            right: 25%;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--accent-color), var(--secondary-color), transparent);
            border-radius: 10px;
        }
        
        .sidebar-logo {
            width: 62px;
            height: 62px;
            margin: 0 auto 14px;
            background: linear-gradient(135deg, rgba(0,206,209,0.3), rgba(255,255,255,0.1));
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: var(--accent-color);
            box-shadow: 0 6px 20px rgba(0,206,209,0.25);
        }
        
        .sidebar-header h2 {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: 0.3px;
        }
        
        .sidebar-header p {
            font-size: 12.5px;
            color: rgba(255,255,255,0.65);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }
        
        .sidebar-header p::before {
            content: '';
            width: 7px;
            height: 7px;
            background: #2ecc71;
            border-radius: 50%;
            box-shadow: 0 0 8px #2ecc71;
        }
        
        .sidebar-menu {
            padding: 20px 14px;
        }
        
        .sidebar-menu::before {
            content: 'MENU UTAMA';
            display: block;
            font-size: 10px;
            letter-spacing: 2.5px;
            color: rgba(255,255,255,0.35);
            padding: 0 12px 12px;
            font-weight: 600;
        }
        
        .menu-item {
            padding: 13px 16px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 13px;
            transition: var(--transition);
            font-size: 13.5px;
            font-weight: 500;
            border-radius: var(--radius-sm);
            margin-bottom: 4px;
            position: relative;
        }
        
        .menu-item i { 
            width: 20px; 
            text-align: center; 
            font-size: 15px;
            transition: var(--transition);
        }
        
        .menu-item:hover {
            background: rgba(255,255,255,0.08);
            color: white;
            transform: translateX(4px);
        }
        
        .menu-item:hover i {
            color: var(--secondary-color);
        }
        
        .menu-item.active {
            background: linear-gradient(90deg, rgba(0,206,209,0.2), rgba(0,206,209,0.05));
            color: white;
            font-weight: 600;
        }
        
        .menu-item.active::before {
            content: '';
            position: absolute;
            left: -14px;
            top: 8px;
            bottom: 8px;
            width: 4px;
            background: linear-gradient(180deg, var(--accent-color), var(--secondary-color));
            border-radius: 0 4px 4px 0;
        }
        
        .menu-item.active i {
            color: var(--accent-color);
        }
        
        .menu-item.menu-logout {
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 16px;
            color: rgba(255,150,150,0.85);
        }
        
        .menu-item.menu-logout:hover {
            background: rgba(231, 76, 60, 0.15);
            color: #ff9a9a;
        }
        
        /* ============ MAIN CONTENT ============ */
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 30px;
        }
        
        .top-bar {
            background: var(--white);
            padding: 24px 30px;
            border-radius: var(--radius-lg);
            margin-bottom: 28px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            position: relative;
            overflow: hidden;
        }
        
        .top-bar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
        }
        
        .top-bar h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 14px;
            letter-spacing: -0.3px;
        }
        
        .top-bar h1 .icon-box {
            width: 46px;
            height: 46px;
            background: linear-gradient(135deg, var(--primary-color), #005AA7);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 19px;
            flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(0,61,130,0.28);
        }
        
        .top-bar h1 .title-wrap small {
            display: block;
            font-size: 12.5px;
            font-weight: 500;
            color: var(--text-light);
            margin-top: 2px;
        }
        
        .top-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn-logout {
            padding: 10px 18px;
            background: linear-gradient(135deg, var(--danger-color), #ff6b6b);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-size: 12.5px;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(231,76,60,0.3);
        }
        
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(231,76,60,0.4);
        }
        
        /* ============ ALERTS ============ */
        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 13px;
            font-size: 13.5px;
            font-weight: 500;
            animation: slideDown 0.35s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-success {
            background: linear-gradient(135deg, #e8f8ee 0%, #d4f5e0 100%);
            color: #155724;
            border: 1px solid rgba(39, 174, 96, 0.25);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-error {
            background: #fff5f5;
            color: #721c24;
            border: 1px solid rgba(231, 76, 60, 0.3);
            border-left: 4px solid var(--danger-color);
        }
        
        .alert i {
            font-size: 19px;
            flex-shrink: 0;
        }
        
        /* ============ CONTENT CARD ============ */
        .content-card {
            background: var(--white);
            padding: 40px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }
        
        .content-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color), var(--secondary-color));
        }
        
        .form-section {
            margin-bottom: 38px;
            padding-bottom: 38px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .form-section:last-of-type {
            border-bottom: none;
            margin-bottom: 30px;
            padding-bottom: 0;
        }
        
        .form-section h3 {
            font-size: 17px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 26px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 13px;
            position: relative;
        }
        
        .form-section h3::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 70px;
            height: 2px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
        }
        
        .form-section h3 .section-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary-color), #005AA7);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 17px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0,61,130,0.25);
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 9px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 13px;
        }
        
        .form-group label i {
            color: var(--secondary-dark);
            font-size: 13px;
            width: 16px;
            text-align: center;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 13px 16px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 13.5px;
            transition: var(--transition);
            color: var(--text-dark);
            background: var(--bg-light);
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(0,206,209,0.12);
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
            line-height: 1.7;
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #a5aebc;
        }
        
        .form-actions {
            display: flex;
            gap: 14px;
            padding-top: 24px;
            border-top: 2px solid var(--border-color);
        }
        
        .btn-submit {
            padding: 14px 34px;
            background: linear-gradient(135deg, var(--primary-color), #005AA7 60%, var(--secondary-color) 140%);
            background-size: 150% 150%;
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: inherit;
            position: relative;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0,61,130,0.3);
        }
        
        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -80%;
            width: 50%;
            height: 100%;
            background: linear-gradient(100deg, transparent, rgba(255,255,255,0.35), transparent);
            transform: skewX(-25deg);
            transition: left 0.7s ease;
        }
        
        .btn-submit:hover::before { left: 130%; }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 26px rgba(0,61,130,0.4);
        }
        
        .btn-submit i {
            font-size: 15px;
            position: relative;
            z-index: 1;
        }
        
        .btn-submit span {
            position: relative;
            z-index: 1;
        }
        
        .btn-reset {
            padding: 14px 34px;
            background: var(--white);
            color: var(--primary-color);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: inherit;
        }
        
        .btn-reset:hover {
            background: var(--primary-light);
            border-color: var(--secondary-color);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0 22px;
        }
        
        .char-counter {
            font-size: 11.5px;
            color: var(--text-light);
            text-align: right;
            margin-top: 5px;
        }
        
        /* ============ RESPONSIVE ============ */
        @media (max-width: 1024px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar { 
                display: none; 
            }
            
            .main-content { 
                margin-left: 0;
                padding: 18px;
            }
            
            .content-card {
                padding: 26px 20px;
            }
            
            .top-bar {
                padding: 20px;
            }
            
            .top-bar h1 {
                font-size: 18px;
            }
            
            .top-actions {
                width: 100%;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-submit,
            .btn-reset {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h2>Admin PKBM</h2>
                <p><?php echo $_SESSION['admin_name']; ?></p>
            </div>
            
            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="profil.php" class="menu-item active">
                    <i class="fas fa-building"></i>
                    <span>Profil PKBM</span>
                </a>
                <a href="program.php" class="menu-item">
                    <i class="fas fa-book-open"></i>
                    <span>Program Paket</span>
                </a>
                <a href="pendaftaran.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Data Pendaftar</span>
                </a>
                <a href="informasi.php" class="menu-item">
                    <i class="fas fa-bullhorn"></i>
                    <span>Informasi</span>
                </a>
                <a href="struktur.php" class="menu-item">
                    <i class="fas fa-sitemap"></i>
                    <span>Struktur Organisasi</span>
                </a>
                <a href="dokumen.php" class="menu-item">
                    <i class="fas fa-folder-open"></i>
                    <span>Dokumen</span>
                </a>
                <a href="dokumentasi.php" class="menu-item">
                    <i class="fas fa-images"></i>
                    <span>Dokumentasi</span>
                </a>
                <a href="kontak.php" class="menu-item">
                    <i class="fas fa-phone-alt"></i>
                    <span>Kontak & Lokasi</span>
                </a>
                <a href="kelola.admin.php" class="menu-item">
                    <i class="fas fa-user-shield"></i>
                    <span>Kelola Admin</span>
                </a>
                <a href="logout.php" class="menu-item menu-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <h1>
                    <span class="icon-box"><i class="fas fa-building"></i></span>
                    <span class="title-wrap">
                        Kelola Profil PKBM
                        <small>Perbarui informasi umum dan kontak lembaga</small>
                    </span>
                </h1>
                <div class="top-actions">
                    <a href="logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
            
            <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
            <?php endif; ?>
            
            <div class="content-card">
                <form method="POST" id="profilForm">
                    <div class="form-section">
                        <h3>
                            <span class="section-icon"><i class="fas fa-info-circle"></i></span>
                            Informasi Umum
                        </h3>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-graduation-cap"></i>
                                Nama PKBM
                            </label>
                            <input 
                                type="text" 
                                name="nama_pkbm" 
                                value="<?php echo htmlspecialchars($profil['nama_pkbm'] ?? ''); ?>" 
                                placeholder="Masukkan nama PKBM"
                                required
                            >
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-eye"></i>
                                Visi
                            </label>
                            <textarea 
                                name="visi" 
                                placeholder="Masukkan visi PKBM"
                                required
                            ><?php echo htmlspecialchars($profil['visi'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-bullseye"></i>
                                Misi
                            </label>
                            <textarea 
                                name="misi" 
                                placeholder="Masukkan misi PKBM (pisahkan dengan enter untuk setiap poin)"
                                required
                            ><?php echo htmlspecialchars($profil['misi'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-history"></i>
                                Sejarah
                            </label>
                            <textarea 
                                name="sejarah" 
                                placeholder="Masukkan sejarah singkat PKBM"
                            ><?php echo htmlspecialchars($profil['sejarah'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>
                            <span class="section-icon"><i class="fas fa-address-card"></i></span>
                            Informasi Kontak
                        </h3>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-map-marker-alt"></i>
                                Alamat Lengkap
                            </label>
                            <textarea 
                                name="alamat" 
                                placeholder="Masukkan alamat lengkap PKBM"
                                required
                            ><?php echo htmlspecialchars($profil['alamat'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-phone"></i>
                                    Telepon
                                </label>
                                <input 
                                    type="text" 
                                    name="telepon" 
                                    value="<?php echo htmlspecialchars($profil['telepon'] ?? ''); ?>"
                                    placeholder="Contoh: 0821-1115-8525"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-envelope"></i>
                                    Email
                                </label>
                                <input 
                                    type="email" 
                                    name="email" 
                                    value="<?php echo htmlspecialchars($profil['email'] ?? ''); ?>"
                                    placeholder="Contoh: info@pkbm.com"
                                >
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-globe"></i>
                                Website
                            </label>
                            <input 
                                type="url" 
                                name="website" 
                                value="<?php echo htmlspecialchars($profil['website'] ?? ''); ?>"
                                placeholder="Contoh: https://www.pkbm.com"
                            >
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i>
                            <span>Simpan Perubahan</span>
                        </button>
                        <button type="reset" class="btn-reset">
                            <i class="fas fa-undo"></i>
                            Reset Form
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script>
        // Auto-hide success/error alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'all 0.3s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
        
        // Confirm before reset
        document.querySelector('.btn-reset')?.addEventListener('click', function(e) {
            if (!confirm('Apakah Anda yakin ingin mereset form? Semua perubahan yang belum disimpan akan hilang.')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>