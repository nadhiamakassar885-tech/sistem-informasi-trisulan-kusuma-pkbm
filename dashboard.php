<?php
require_once '../config.php';

// ✅ Wajib login untuk akses dashboard
requireLogin();
date_default_timezone_set('Asia/Jayapura');

 $date = new DateTime('now');

 $formatter = new IntlDateFormatter(
    'id_ID',
    IntlDateFormatter::FULL,
    IntlDateFormatter::NONE,
    'Asia/Jayapura',
    IntlDateFormatter::GREGORIAN,
    'EEEE, dd MMMM yyyy'
);

// Get statistics with error handling
 $stats = [
    'total_pendaftar' => 0,
    'pending' => 0,
    'verified' => 0,
    'approved' => 0,
    'diterima' => 0,
    'ditolak' => 0,
    'paket_a' => 0,
    'paket_b' => 0,
    'paket_c' => 0,
    'bulan_ini' => 0,
    'hari_ini' => 0
];

 $db_error = false;

try {
    // Total Pendaftar
    if ($result = $conn->query("SELECT COUNT(*) as total FROM pendaftaran")) {
        $stats['total_pendaftar'] = $result->fetch_assoc()['total'];
    }
    
    // Status Pendaftaran
    if ($result = $conn->query("SELECT COUNT(*) as total FROM pendaftaran WHERE status_pendaftaran = 'pending'")) {
        $stats['pending'] = $result->fetch_assoc()['total'];
    }
    if ($result = $conn->query("SELECT COUNT(*) as total FROM pendaftaran WHERE status_pendaftaran = 'verified'")) {
        $stats['verified'] = $result->fetch_assoc()['total'];
    }
    if ($result = $conn->query("SELECT COUNT(*) as total FROM pendaftaran WHERE status_pendaftaran = 'approved'")) {
        $stats['approved'] = $result->fetch_assoc()['total'];
    }
    if ($result = $conn->query("SELECT COUNT(*) as total FROM pendaftaran WHERE status_pendaftaran = 'diterima'")) {
        $stats['diterima'] = $result->fetch_assoc()['total'];
    }
    if ($result = $conn->query("SELECT COUNT(*) as total FROM pendaftaran WHERE status_pendaftaran = 'ditolak'")) {
        $stats['ditolak'] = $result->fetch_assoc()['total'];
    }
    
    // Per Paket
    if ($result = $conn->query("SELECT COUNT(*) as total FROM pendaftaran WHERE paket_dipilih = 'A'")) {
        $stats['paket_a'] = $result->fetch_assoc()['total'];
    }
    if ($result = $conn->query("SELECT COUNT(*) as total FROM pendaftaran WHERE paket_dipilih = 'B'")) {
        $stats['paket_b'] = $result->fetch_assoc()['total'];
    }
    if ($result = $conn->query("SELECT COUNT(*) as total FROM pendaftaran WHERE paket_dipilih = 'C'")) {
        $stats['paket_c'] = $result->fetch_assoc()['total'];
    }
    
    // Bulan Ini
    if ($result = $conn->query("SELECT COUNT(*) as total FROM pendaftaran WHERE MONTH(tanggal_daftar) = MONTH(CURRENT_DATE()) AND YEAR(tanggal_daftar) = YEAR(CURRENT_DATE())")) {
        $stats['bulan_ini'] = $result->fetch_assoc()['total'];
    }
    
    // Hari Ini
    if ($result = $conn->query("SELECT COUNT(*) as total FROM pendaftaran WHERE DATE(tanggal_daftar) = CURRENT_DATE()")) {
        $stats['hari_ini'] = $result->fetch_assoc()['total'];
    }
    
} catch (Exception $e) {
    $db_error = true;
}

// Hitung persentase
 $total_verified = $stats['verified'] + $stats['approved'] + $stats['diterima'];
 $percentage_success = $stats['total_pendaftar'] > 0 ? round(($total_verified / $stats['total_pendaftar']) * 100, 1) : 0;
 $percentage_pending = $stats['total_pendaftar'] > 0 ? round(($stats['pending'] / $stats['total_pendaftar']) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PKBM Trisula Kusuma</title>
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
            --info-color: #3498db;
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
        
        /* ============ SIDEBAR (KONSISTEN DENGAN HALAMAN ADMIN LAIN) ============ */
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
            flex-wrap: wrap;
        }
        
        .date-info {
            font-size: 12px;
            color: var(--text-light);
            background: var(--bg-light);
            border: 1px solid var(--border-color);
            padding: 8px 16px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }
        
        .date-info i {
            color: var(--secondary-dark);
        }
        
        .btn {
            padding: 10px 18px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 12.5px;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
        }
        
        .btn-logout {
            background: linear-gradient(135deg, var(--danger-color), #ff6b6b);
            color: white;
            box-shadow: 0 4px 12px rgba(231,76,60,0.3);
        }
        
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(231,76,60,0.4);
        }
        
        .btn-add {
            background: var(--gold-gradient);
            color: var(--primary-dark);
            box-shadow: 0 4px 14px rgba(255,215,0,0.4);
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,215,0,0.55);
        }
        
        /* ============ ALERT INFO ============ */
        .alert-info {
            background: linear-gradient(135deg, #e4f4f7 0%, #d5edf3 100%);
            border: 1px solid rgba(12, 84, 96, 0.2);
            border-left: 4px solid #0c5460;
            padding: 18px 22px;
            border-radius: var(--radius-md);
            color: #0c5460;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 28px;
            font-size: 13.5px;
            font-weight: 500;
            animation: slideDown 0.35s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-info i {
            font-size: 22px;
            flex-shrink: 0;
        }
        
        /* ============ STATS PREMIUM ============ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }
        
        .stat-card {
            background: var(--white);
            padding: 26px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .stat-card.blue::before { background: linear-gradient(90deg, var(--primary-color), #005AA7); }
        .stat-card.yellow::before { background: linear-gradient(90deg, var(--warning-color), #f7b731); }
        .stat-card.green::before { background: linear-gradient(90deg, var(--success-color), #2ecc71); }
        .stat-card.red::before { background: linear-gradient(90deg, var(--danger-color), #ff6b6b); }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
            transition: var(--transition);
        }
        
        .stat-card.blue .stat-icon { background: linear-gradient(135deg, var(--primary-color), #005AA7); }
        .stat-card.yellow .stat-icon { background: linear-gradient(135deg, var(--warning-color), #f7b731); }
        .stat-card.green .stat-icon { background: linear-gradient(135deg, var(--success-color), #2ecc71); }
        .stat-card.red .stat-icon { background: linear-gradient(135deg, var(--danger-color), #ff6b6b); }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .stat-arrow {
            font-size: 12px;
            color: var(--text-light);
            opacity: 0;
            transition: var(--transition);
            transform: translateX(-6px);
        }
        
        .stat-card:hover .stat-arrow {
            opacity: 1;
            transform: translateX(0);
        }
        
        .stat-body {
            position: relative;
            z-index: 1;
        }
        
        .stat-number {
            font-size: 34px;
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 4px;
            line-height: 1.1;
            letter-spacing: -0.5px;
        }
        
        .stat-label {
            font-size: 13px;
            color: var(--text-light);
            font-weight: 500;
        }
        
        /* ============ SECTION GRID ============ */
        .section-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 22px;
            margin-bottom: 28px;
        }
        
        .content-card {
            background: var(--white);
            padding: 28px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }
        
        .content-card h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--border-color);
            position: relative;
        }
        
        .content-card h3::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
        }
        
        .content-card h3 .card-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--primary-color), #005AA7);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 15px;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0,61,130,0.25);
        }
        
        /* Progress */
        .progress-item {
            margin-bottom: 22px;
        }
        
        .progress-item:last-child {
            margin-bottom: 0;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 9px;
        }
        
        .progress-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .progress-value {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary-color);
            background: var(--primary-light);
            padding: 3px 12px;
            border-radius: 50px;
        }
        
        .progress-bar {
            height: 10px;
            background: var(--bg-light);
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 10px;
            transition: width 1s ease;
        }
        
        .progress-fill.green {
            background: linear-gradient(90deg, var(--success-color), #2ecc71);
        }
        
        .progress-fill.yellow {
            background: linear-gradient(90deg, var(--warning-color), #f7b731);
        }
        
        /* Info Grid (Persentase) */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .info-item {
            padding: 22px 16px;
            background: var(--bg-light);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            text-align: center;
            transition: var(--transition);
        }
        
        .info-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-sm);
        }
        
        .info-item .icon {
            font-size: 26px;
            margin-bottom: 10px;
        }
        
        .info-item.blue .icon { color: var(--primary-color); }
        .info-item.cyan .icon { color: var(--secondary-dark); }
        .info-item.green .icon { color: var(--success-color); }
        .info-item.yellow .icon { color: var(--warning-color); }
        
        .info-item h4 {
            font-size: 26px;
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 4px;
            letter-spacing: -0.5px;
        }
        
        .info-item p {
            font-size: 12.5px;
            color: var(--text-light);
            font-weight: 500;
        }
        
        .divider-top {
            margin-top: 26px;
            padding-top: 26px;
            border-top: 2px solid var(--border-color);
        }
        
        /* Quick Stats */
        .quick-stats {
            display: grid;
            gap: 14px;
        }
        
        .quick-stat-item {
            padding: 16px 18px;
            background: var(--bg-light);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--primary-color);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: var(--transition);
        }
        
        .quick-stat-item:hover {
            transform: translateX(4px);
            box-shadow: var(--shadow-sm);
            background: var(--white);
        }
        
        .quick-stat-item:nth-child(1) { border-left-color: var(--primary-color); }
        .quick-stat-item:nth-child(2) { border-left-color: var(--success-color); }
        .quick-stat-item:nth-child(3) { border-left-color: var(--warning-color); }
        
        .quick-stat-icon {
            width: 46px;
            height: 46px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            flex-shrink: 0;
            transition: var(--transition);
        }
        
        .quick-stat-item:hover .quick-stat-icon {
            transform: scale(1.08) rotate(4deg);
        }
        
        .quick-stat-icon.blue { background: linear-gradient(135deg, var(--primary-color), #005AA7); }
        .quick-stat-icon.green { background: linear-gradient(135deg, var(--success-color), #2ecc71); }
        .quick-stat-icon.orange { background: linear-gradient(135deg, var(--warning-color), #f7b731); }
        
        .quick-stat-info h4 {
            font-size: 23px;
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 2px;
            line-height: 1.1;
        }
        
        .quick-stat-info p {
            font-size: 12.5px;
            color: var(--text-light);
            font-weight: 500;
        }
        
        .stack-gap {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }
        
        /* ============ RESPONSIVE ============ */
        @media (max-width: 1200px) {
            .section-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 18px; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 14px; }
            .top-bar { padding: 20px; }
            .top-bar h1 { font-size: 18px; }
            .top-actions { width: 100%; }
            .info-grid { grid-template-columns: 1fr; }
            .content-card { padding: 22px 18px; }
        }
        
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
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
                <a href="dashboard.php" class="menu-item active">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
                <a href="profil.php" class="menu-item">
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
                    <span class="icon-box"><i class="fas fa-chart-line"></i></span>
                    <span class="title-wrap">
                        Dashboard Admin
                        <small>Selamat datang kembali, <?php echo $_SESSION['admin_name']; ?></small>
                    </span>
                </h1>
                <div class="top-actions">
                    <div class="date-info">
                        <i class="far fa-calendar-alt"></i> <?php echo $formatter->format($date); ?> &middot; <?php echo $date->format('H:i'); ?> WIT
                    </div>
                    <a href="../pendaftaran.php" class="btn btn-add" target="_blank">
                        <i class="fas fa-plus-circle"></i> Form Pendaftaran
                    </a>
                    <a href="logout.php" class="btn btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            
            <?php if ($db_error): ?>
            <div class="alert-info">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Perhatian!</strong> Database tables belum dibuat. Silakan buat tabel terlebih dahulu di database.
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Main Stats -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <i class="fas fa-arrow-right stat-arrow"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-number"><?php echo $stats['total_pendaftar']; ?></div>
                        <div class="stat-label">Total Pendaftar</div>
                    </div>
                </div>
                
                <div class="stat-card yellow">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <i class="fas fa-arrow-right stat-arrow"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-number"><?php echo $stats['pending']; ?></div>
                        <div class="stat-label">Menunggu Verifikasi</div>
                    </div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <i class="fas fa-arrow-right stat-arrow"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-number"><?php echo $total_verified; ?></div>
                        <div class="stat-label">Terverifikasi</div>
                    </div>
                </div>
                
                <div class="stat-card red">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <i class="fas fa-arrow-right stat-arrow"></i>
                    </div>
                    <div class="stat-body">
                        <div class="stat-number"><?php echo $stats['ditolak']; ?></div>
                        <div class="stat-label">Ditolak</div>
                    </div>
                </div>
            </div>
            
            <!-- Section Grid -->
            <div class="section-grid">
                <!-- Left: Program Paket -->
                <div class="content-card">
                    <h3>
                        <span class="card-icon"><i class="fas fa-graduation-cap"></i></span>
                        Pendaftar Per Program Paket
                    </h3>
                    
                    <div class="progress-item">
                        <div class="progress-header">
                            <span class="progress-label">Paket A (Setara SD)</span>
                            <span class="progress-value"><?php echo $stats['paket_a']; ?> Peserta</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $stats['total_pendaftar'] > 0 ? ($stats['paket_a']/$stats['total_pendaftar']*100) : 0; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="progress-item">
                        <div class="progress-header">
                            <span class="progress-label">Paket B (Setara SMP)</span>
                            <span class="progress-value"><?php echo $stats['paket_b']; ?> Peserta</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill green" style="width: <?php echo $stats['total_pendaftar'] > 0 ? ($stats['paket_b']/$stats['total_pendaftar']*100) : 0; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="progress-item">
                        <div class="progress-header">
                            <span class="progress-label">Paket C (Setara SMA)</span>
                            <span class="progress-value"><?php echo $stats['paket_c']; ?> Peserta</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill yellow" style="width: <?php echo $stats['total_pendaftar'] > 0 ? ($stats['paket_c']/$stats['total_pendaftar']*100) : 0; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="divider-top">
                        <div class="info-grid">
                            <div class="info-item blue">
                                <div class="icon"><i class="fas fa-percentage"></i></div>
                                <h4><?php echo $percentage_success; ?>%</h4>
                                <p>Tingkat Keberhasilan</p>
                            </div>
                            <div class="info-item yellow">
                                <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                                <h4><?php echo $percentage_pending; ?>%</h4>
                                <p>Pending Review</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right: Quick Stats -->
                <div class="stack-gap">
                    <div class="content-card">
                        <h3>
                            <span class="card-icon"><i class="fas fa-chart-bar"></i></span>
                            Statistik Cepat
                        </h3>
                        
                        <div class="quick-stats">
                            <div class="quick-stat-item">
                                <div class="quick-stat-icon blue">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="quick-stat-info">
                                    <h4><?php echo $stats['hari_ini']; ?></h4>
                                    <p>Pendaftar Hari Ini</p>
                                </div>
                            </div>
                            
                            <div class="quick-stat-item">
                                <div class="quick-stat-icon green">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="quick-stat-info">
                                    <h4><?php echo $stats['bulan_ini']; ?></h4>
                                    <p>Pendaftar Bulan Ini</p>
                                </div>
                            </div>
                            
                            <div class="quick-stat-item">
                                <div class="quick-stat-icon orange">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <div class="quick-stat-info">
                                    <h4><?php echo $stats['verified']; ?></h4>
                                    <p>Terverifikasi</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="content-card">
                        <h3>
                            <span class="card-icon"><i class="fas fa-list-check"></i></span>
                            Status Detail
                        </h3>
                        
                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-label">Verified</span>
                                <span class="progress-value"><?php echo $stats['verified']; ?></span>
                            </div>
                        </div>
                        
                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-label">Approved</span>
                                <span class="progress-value"><?php echo $stats['approved']; ?></span>
                            </div>
                        </div>
                        
                        <div class="progress-item">
                            <div class="progress-header">
                                <span class="progress-label">Diterima</span>
                                <span class="progress-value"><?php echo $stats['diterima']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Animate progress bars on load
        window.addEventListener('load', function() {
            document.querySelectorAll('.progress-fill').forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });
    </script>
</body> 
</html>