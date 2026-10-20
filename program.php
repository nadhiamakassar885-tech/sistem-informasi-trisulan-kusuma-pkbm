<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM program_paket WHERE id = $id");
    header('Location: program.php?success=delete');
    exit;
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $jenis_paket = sanitize($_POST['jenis_paket']);
    $nama_program = sanitize($_POST['nama_program']);
    $deskripsi = sanitize($_POST['deskripsi']);
    $syarat_pendaftaran = sanitize($_POST['syarat_pendaftaran']);
    $biaya = sanitize($_POST['biaya']);
    $durasi = sanitize($_POST['durasi']);
    $jadwal = sanitize($_POST['jadwal']);
    $status = sanitize($_POST['status']);
    
    if ($id > 0) {
        // Update
        $stmt = $conn->prepare("UPDATE program_paket SET jenis_paket=?, nama_program=?, deskripsi=?, syarat_pendaftaran=?, biaya=?, durasi=?, jadwal=?, status=? WHERE id=?");
        $stmt->bind_param("ssssssssi", $jenis_paket, $nama_program, $deskripsi, $syarat_pendaftaran, $biaya, $durasi, $jadwal, $status, $id);
        $success_msg = "Program berhasil diperbarui!";
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO program_paket (jenis_paket, nama_program, deskripsi, syarat_pendaftaran, biaya, durasi, jadwal, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $jenis_paket, $nama_program, $deskripsi, $syarat_pendaftaran, $biaya, $durasi, $jadwal, $status);
        $success_msg = "Program berhasil ditambahkan!";
    }
    
    if ($stmt->execute()) {
        header('Location: program.php?success=save');
        exit;
    }
}

 $programs = $conn->query("SELECT * FROM program_paket ORDER BY jenis_paket ASC, id ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Program - Admin PKBM</title>
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
        
        .top-bar-right {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        /* ============ BUTTONS ============ */
        .btn {
            padding: 11px 20px;
            border: none;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            font-family: inherit;
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, var(--primary-color), #005AA7); 
            color: white;
            box-shadow: 0 4px 12px rgba(0,61,130,0.25);
        }
        
        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0,61,130,0.35);
        }
        
        .btn-success { 
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark)); 
            color: white;
            box-shadow: 0 4px 14px rgba(0,206,209,0.35);
        }
        
        .btn-success:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,206,209,0.45);
        }
        
        .btn-danger { 
            background: linear-gradient(135deg, var(--danger-color), #ff6b6b); 
            color: white;
            box-shadow: 0 4px 12px rgba(231,76,60,0.3);
        }
        
        .btn-danger:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(231,76,60,0.4);
        }
        
        .btn-warning { 
            background: var(--gold-gradient); 
            color: var(--primary-dark);
            box-shadow: 0 4px 14px rgba(255,215,0,0.4);
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,215,0,0.55);
        }
        
        /* ============ ALERT ============ */
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
        
        .alert i {
            font-size: 19px;
        }
        
        /* ============ CONTENT CARD & TABLE ============ */
        .content-card {
            background: var(--white);
            padding: 32px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            margin-bottom: 22px;
            position: relative;
            overflow: hidden;
        }
        
        .content-card.has-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
        }
        
        .table-wrapper { overflow-x: auto; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }
        
        table th {
            background: linear-gradient(135deg, var(--primary-deep) 0%, var(--primary-color) 60%, #005AA7 100%);
            padding: 15px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 11.5px;
            color: rgba(255,255,255,0.95);
            text-transform: uppercase;
            letter-spacing: 1px;
            white-space: nowrap;
        }
        
        table th:first-child { border-radius: var(--radius-sm) 0 0 var(--radius-sm); }
        table th:last-child { border-radius: 0 var(--radius-sm) var(--radius-sm) 0; }
        
        table th i {
            color: var(--secondary-color);
            margin-right: 7px;
        }
        
        table td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            font-size: 13px;
            color: var(--text-light);
        }
        
        table tbody tr {
            transition: var(--transition);
        }
        
        table tbody tr:last-child td { border-bottom: none; }
        
        table tbody tr:hover {
            background: rgba(0,206,209,0.045);
        }
        
        table td strong {
            color: var(--primary-color);
            font-weight: 700;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid transparent;
        }
        
        .badge-aktif {
            background: #EAF9F0;
            color: #1d8a4b;
            border-color: #b8e8cb;
        }
        
        .badge-nonaktif {
            background: #FDEDEB;
            color: #c0392b;
            border-color: #f5c6c0;
        }
        
        .badge-paket {
            background: var(--primary-light);
            color: var(--primary-color);
            border-color: #c9d9ef;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .action-buttons .btn {
            padding: 8px 14px;
            font-size: 11.5px;
        }
        
        /* ============ MODAL ============ */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 20, 45, 0.75);
            backdrop-filter: blur(5px);
            z-index: 9999;
            animation: fadeIn 0.25s ease;
            padding: 20px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: var(--white);
            padding: 36px;
            border-radius: var(--radius-lg);
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 0 25px 80px rgba(0, 10, 40, 0.4);
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .modal-content h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 26px;
            display: flex;
            align-items: center;
            gap: 13px;
            padding-bottom: 18px;
            border-bottom: 2px solid var(--border-color);
            position: relative;
        }
        
        .modal-content h3::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 70px;
            height: 2px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
        }
        
        .modal-content h3 .modal-icon {
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
        }
        
        /* ============ FORM ============ */
        .form-group { margin-bottom: 20px; }
        
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
        
        .form-group label span {
            color: var(--danger-color);
            font-weight: 700;
        }
        
        .form-group input,
        .form-group select,
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
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(0,206,209,0.12);
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
            line-height: 1.7;
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #a5aebc;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 20px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 26px;
            justify-content: flex-end;
            padding-top: 22px;
            border-top: 2px solid var(--border-color);
        }
        
        .helper-text {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 8px;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.6;
            background: var(--primary-light);
            padding: 11px 14px;
            border-radius: var(--radius-sm);
            border-left: 3px solid var(--secondary-color);
        }
        
        .helper-text i {
            color: var(--secondary-dark);
            font-size: 13px;
            margin-top: 2px;
            flex-shrink: 0;
        }
        
        .helper-text strong {
            color: var(--primary-color);
        }
        
        /* ============ INFO BOX ============ */
        .info-box {
            background: linear-gradient(135deg, #f0f8ff 0%, #eaf5fc 100%);
            border: 1px solid #cfe5f5;
            border-left: 4px solid var(--secondary-color);
            padding: 24px 28px;
            border-radius: var(--radius-lg);
        }
        
        .info-box h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-box h3 i {
            color: var(--secondary-dark);
        }
        
        .info-box p {
            color: var(--text-light);
            font-size: 13.5px;
            line-height: 1.8;
            margin: 0;
        }
        
        /* ============ EMPTY STATE ============ */
        .empty-state {
            text-align: center;
            padding: 56px 20px;
        }
        
        .empty-state .empty-icon {
            width: 84px;
            height: 84px;
            margin: 0 auto 20px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            color: var(--primary-color);
        }
        
        .empty-state p {
            margin-bottom: 22px;
            font-size: 14px;
            color: var(--text-light);
            font-weight: 500;
        }
        
        /* ============ RESPONSIVE ============ */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 18px; }
            .content-card { padding: 24px 18px; }
            .top-bar { padding: 20px; }
            .top-bar h1 { font-size: 18px; }
            .top-bar-right { width: 100%; }
            .top-bar-right .btn { flex: 1; justify-content: center; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .modal-content { padding: 26px 20px; }
            .modal-buttons { flex-direction: column-reverse; }
            .modal-buttons .btn { width: 100%; justify-content: center; }
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
                <a href="profil.php" class="menu-item">
                    <i class="fas fa-building"></i>
                    <span>Profil PKBM</span>
                </a>
                <a href="program.php" class="menu-item active">
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
                    <span class="icon-box"><i class="fas fa-book-open"></i></span>
                    <span class="title-wrap">
                        Kelola Program Paket
                        <small>Tambahkan, ubah, atau hapus program pendidikan</small>
                    </span>
                </h1>
                <div class="top-bar-right">
                    <button class="btn btn-success" onclick="showModal()">
                        <i class="fas fa-plus"></i>
                        Tambah Program
                    </button>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>
                    <?php 
                        if ($_GET['success'] == 'save') echo 'Program berhasil disimpan!';
                        if ($_GET['success'] == 'delete') echo 'Program berhasil dihapus!';
                    ?>
                </span>
            </div>
            <?php endif; ?>
            
            <div class="content-card has-gradient">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-layer-group"></i>Paket</th>
                                <th><i class="fas fa-book"></i>Nama Program</th>
                                <th><i class="fas fa-money-bill-wave"></i>Biaya</th>
                                <th><i class="fas fa-clock"></i>Durasi</th>
                                <th><i class="fas fa-toggle-on"></i>Status</th>
                                <th><i class="fas fa-cogs"></i>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($programs->num_rows > 0) {
                                while ($row = $programs->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><span class="badge badge-paket">Paket <?php echo strtoupper($row['jenis_paket']); ?></span></td>
                                <td><strong><?php echo $row['nama_program']; ?></strong></td>
                                <td><?php echo $row['biaya'] ?? '-'; ?></td>
                                <td><?php echo $row['durasi'] ?? '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo ($row['status'] === 'aktif') ? 'badge-aktif' : 'badge-nonaktif'; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-primary" onclick='editProgram(<?php echo htmlspecialchars(json_encode($row)); ?>)'>
                                            <i class="fas fa-edit"></i>
                                            Edit
                                        </button>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus program ini?')">
                                            <i class="fas fa-trash"></i>
                                            Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            } else {
                            ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                                        <p>Belum ada program yang ditambahkan</p>
                                        <button class="btn btn-success" onclick="showModal()">
                                            <i class="fas fa-plus"></i>
                                            Tambah Program Pertama
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="content-card info-box">
                <h3>
                    <i class="fas fa-info-circle"></i>
                    Informasi
                </h3>
                <p>
                    Data program yang Anda inputkan di sini akan secara otomatis tampil di halaman depan website dan halaman detail program untuk pengguna umum. Pastikan semua informasi sudah lengkap dan akurat sebelum mengaktifkan program.
                </p>
            </div>
        </main>
    </div>
    
    <!-- Modal Form -->
    <div class="modal" id="formModal">
        <div class="modal-content">
            <h3 id="modalTitle">
                <span class="modal-icon"><i class="fas fa-plus-circle"></i></span>
                <span id="modalTitleText">Tambah Program Baru</span>
            </h3>
            <form method="POST">
                <input type="hidden" name="id" id="programId" value="0">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-layer-group"></i>
                            Jenis Paket <span>*</span>
                        </label>
                        <select name="jenis_paket" id="jenisPaket" required>
                            <option value="">-- Pilih Paket --</option>
                            <option value="A">Paket A (Setara SD/MI)</option>
                            <option value="B">Paket B (Setara SMP/MTs)</option>
                            <option value="C">Paket C (Setara SMA/MA)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class="fas fa-book"></i>
                            Nama Program <span>*</span>
                        </label>
                        <input type="text" name="nama_program" id="namaProgram" placeholder="Contoh: Kesetaraan Paket A" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-align-left"></i>
                        Deskripsi Program
                    </label>
                    <textarea name="deskripsi" id="deskripsi" placeholder="Jelaskan secara singkat tentang program ini..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-clipboard-list"></i>
                        Syarat & Ketentuan Pendaftaran
                    </label>
                    <textarea name="syarat_pendaftaran" id="syarat" placeholder="Tulis setiap syarat dalam baris baru (tekan Enter untuk baris baru)&#10;Contoh:&#10;Mengisi Formulir Pendaftaran&#10;Fotocopy Ijazah Terakhir atau Surat Kelulusan&#10;Fotocopy Kartu Keluarga&#10;Fotocopy Akte Kelahiran (Jika ada)&#10;Pas Foto ukuran 3 x 4 (1 Lembar)"></textarea>
                    <div class="helper-text">
                        <i class="fas fa-lightbulb"></i>
                        <span><strong>Petunjuk:</strong> Ketik setiap syarat dalam baris terpisah (tekan Enter setelah setiap syarat). Sistem akan otomatis memberi nomor urut 1, 2, 3, dst di website.</span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-money-bill-wave"></i>
                            Biaya Program
                        </label>
                        <input type="text" name="biaya" id="biaya" placeholder="Rp 2.500.000 / bulan">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class="fas fa-clock"></i>
                            Durasi Program
                        </label>
                        <input type="text" name="durasi" id="durasi" placeholder="12 bulan / 2 tahun">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-calendar-alt"></i>
                        Jadwal Belajar
                    </label>
                    <textarea name="jadwal" id="jadwal" placeholder="Senin-Jumat pukul 18:00-20:00, Sabtu pukul 09:00-12:00"></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-toggle-on"></i>
                        Status Program <span>*</span>
                    </label>
                    <select name="status" id="status" required>
                        <option value="aktif">✅ Aktif</option>
                        <option value="nonaktif">❌ Non-aktif</option>
                    </select>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn btn-danger" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                        Batal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        Simpan Program
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showModal() {
            document.getElementById('modalTitleText').textContent = 'Tambah Program Baru';
            document.querySelector('#modalTitle .modal-icon i').className = 'fas fa-plus-circle';
            document.getElementById('programId').value = '0';
            document.querySelector('form').reset();
            document.getElementById('formModal').classList.add('active');
        }
        
        function editProgram(data) {
            document.getElementById('modalTitleText').textContent = 'Edit Program';
            document.querySelector('#modalTitle .modal-icon i').className = 'fas fa-edit';
            document.getElementById('programId').value = data.id || '0';
            document.getElementById('jenisPaket').value = data.jenis_paket || '';
            document.getElementById('namaProgram').value = data.nama_program || '';
            document.getElementById('deskripsi').value = data.deskripsi || '';
            document.getElementById('syarat').value = data.syarat_pendaftaran || '';
            document.getElementById('biaya').value = data.biaya || '';
            document.getElementById('durasi').value = data.durasi || '';
            document.getElementById('jadwal').value = data.jadwal || '';
            document.getElementById('status').value = data.status || 'aktif';
            document.getElementById('formModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('formModal').classList.remove('active');
        }
        
        // Close modal ketika klik di luar area modal
        document.getElementById('formModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Close modal dengan tombol ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('formModal').classList.contains('active')) {
                closeModal();
            }
        });
        
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
    </script>
</body>
</html>