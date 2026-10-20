<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle delete gambar
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Ambil nama file gambar
    $result = $conn->query("SELECT gambar FROM struktur_organisasi WHERE id = $id");
    if ($result && $row = $result->fetch_assoc()) {
        // Hapus file gambar jika ada
        if (!empty($row['gambar']) && file_exists('../uploads/struktur/' . $row['gambar'])) {
            unlink('../uploads/struktur/' . $row['gambar']);
        }
    }
    
    $conn->query("DELETE FROM struktur_organisasi WHERE id = $id");
    logActivity('Menghapus gambar struktur organisasi');
    header('Location: struktur.php?success=delete');
    exit;
}

// Handle upload gambar
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $keterangan = isset($_POST['keterangan']) ? sanitize($_POST['keterangan']) : '';
    $status = sanitize($_POST['status']);
    
    // Handle upload file
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['gambar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Buat folder jika belum ada
            if (!is_dir('../uploads/struktur')) {
                mkdir('../uploads/struktur', 0777, true);
            }
            
            // Hapus gambar lama jika ada (karena hanya boleh 1 gambar)
            $old = $conn->query("SELECT id, gambar FROM struktur_organisasi LIMIT 1");
            if ($old && $old->num_rows > 0) {
                $oldRow = $old->fetch_assoc();
                if (!empty($oldRow['gambar']) && file_exists('../uploads/struktur/' . $oldRow['gambar'])) {
                    unlink('../uploads/struktur/' . $oldRow['gambar']);
                }
                // Hapus record lama
                $conn->query("DELETE FROM struktur_organisasi WHERE id = " . $oldRow['id']);
            }
            
            $gambar = 'struktur_' . time() . '.' . $ext;
            $destination = '../uploads/struktur/' . $gambar;
            
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $destination)) {
                // Insert gambar baru
                $stmt = $conn->prepare("INSERT INTO struktur_organisasi (gambar, keterangan, status, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("sss", $gambar, $keterangan, $status);
                
                if ($stmt->execute()) {
                    logActivity('Mengupload gambar struktur organisasi');
                    header('Location: struktur.php?success=save');
                    exit;
                }
            }
        }
    }
}

 $struktur = $conn->query("SELECT * FROM struktur_organisasi LIMIT 1");
 $data = $struktur && $struktur->num_rows > 0 ? $struktur->fetch_assoc() : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Struktur Organisasi - Admin PKBM</title>
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
        
        .top-bar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        /* ============ BUTTONS ============ */
        .btn {
            padding: 11px 20px;
            border: none;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            font-weight: 600;
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
            background: linear-gradient(135deg, #e8f8ee 0%, #d4f5e0 100%);
            color: #155724;
            border: 1px solid rgba(39, 174, 96, 0.25);
            border-left: 4px solid var(--success-color);
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert i {
            font-size: 19px;
        }
        
        /* ============ INFO BOX ============ */
        .info-box {
            background: linear-gradient(135deg, #e4f4f7 0%, #d5edf3 100%);
            border: 1px solid rgba(12, 84, 96, 0.2);
            border-left: 4px solid #0c5460;
            padding: 18px 22px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            animation: slideDown 0.35s ease;
        }
        
        .info-box i {
            color: #0c5460;
            font-size: 21px;
            margin-top: 2px;
            flex-shrink: 0;
        }
        
        .info-box-content h4 {
            color: #0c5460;
            font-size: 14.5px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .info-box-content p {
            color: rgba(12, 84, 96, 0.85);
            font-size: 13px;
            line-height: 1.7;
        }
        
        /* ============ CONTENT CARD ============ */
        .content-card {
            background: var(--white);
            padding: 36px;
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
        
        .struktur-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .struktur-display {
            text-align: center;
            padding: 10px 0 0;
        }
        
        .struktur-image-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 24px;
        }
        
        .struktur-main-image {
            max-width: 100%;
            height: auto;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            cursor: zoom-in;
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }
        
        .struktur-main-image:hover {
            transform: scale(1.02);
            box-shadow: var(--shadow-lg);
        }
        
        .zoom-hint {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 14px;
            font-size: 12px;
            color: var(--text-light);
            background: var(--bg-light);
            padding: 7px 16px;
            border-radius: 50px;
            border: 1px solid var(--border-color);
        }
        
        .zoom-hint i {
            color: var(--secondary-dark);
        }
        
        .struktur-info {
            background: var(--bg-light);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--accent-color);
            padding: 22px 24px;
            border-radius: var(--radius-md);
            margin-top: 24px;
        }
        
        .struktur-keterangan {
            color: var(--text-dark);
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 0;
        }
        
        .struktur-keterangan i {
            color: var(--secondary-dark);
        }
        
        .struktur-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            padding-top: 24px;
            margin-top: 24px;
            border-top: 2px solid var(--border-color);
        }
        
        .badge {
            padding: 7px 16px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            margin-bottom: 22px;
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
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 80px rgba(0, 10, 40, 0.4);
            animation: slideUp 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .modal-content h3 {
            margin-bottom: 26px;
            color: var(--primary-color);
            font-size: 20px;
            font-weight: 700;
            padding-bottom: 18px;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 13px;
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
        .form-group {
            margin-bottom: 20px;
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 13px 16px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 13.5px;
            transition: var(--transition);
            background: var(--bg-light);
            color: var(--text-dark);
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
            resize: vertical;
            line-height: 1.7;
        }
        
        .upload-preview {
            margin-top: 16px;
            border: 2px dashed var(--secondary-color);
            border-radius: var(--radius-md);
            padding: 20px;
            text-align: center;
            background: #f0f8ff;
        }
        
        .upload-preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: var(--radius-sm);
            margin-top: 10px;
        }
        
        .upload-info {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 9px;
            display: flex;
            align-items: center;
            gap: 7px;
            background: var(--primary-light);
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            border-left: 3px solid var(--secondary-color);
        }
        
        .upload-info i {
            color: var(--secondary-dark);
            flex-shrink: 0;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 26px;
            padding-top: 22px;
            border-top: 2px solid var(--border-color);
        }
        
        .modal-actions .btn {
            padding: 13px 26px;
            font-size: 13.5px;
        }
        
        /* ============ EMPTY STATE ============ */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state .empty-icon {
            width: 96px;
            height: 96px;
            margin: 0 auto 24px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: var(--primary-color);
        }
        
        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 8px;
            color: var(--text-light);
            font-weight: 700;
        }
        
        .empty-state p {
            font-size: 13.5px;
            margin-bottom: 24px;
            color: var(--text-light);
            opacity: 0.8;
        }
        
        /* Image Modal */
        .image-modal {
            padding: 20px;
            max-width: 95%;
            max-height: 95vh;
            background: transparent;
            box-shadow: none;
            border-radius: 0;
        }
        
        .image-modal img {
            max-width: 100%;
            max-height: 90vh;
            border-radius: var(--radius-md);
            box-shadow: 0 24px 70px rgba(0,0,0,0.55);
        }
        
        /* ============ RESPONSIVE ============ */
        @media (max-width: 768px) {
            .sidebar { 
                display: none; 
            }
            
            .main-content { 
                margin-left: 0;
                padding: 18px;
            }
            
            .top-bar {
                padding: 20px;
            }
            
            .top-bar h1 {
                font-size: 18px;
            }
            
            .top-bar-actions {
                width: 100%;
                flex-direction: column;
            }
            
            .top-bar-actions .btn {
                width: 100%;
                justify-content: center;
            }
            
            .content-card {
                padding: 24px 18px;
            }
            
            .modal-content {
                padding: 26px 20px;
            }
            
            .modal-actions {
                flex-direction: column-reverse;
            }
            
            .modal-actions .btn {
                width: 100%;
                justify-content: center;
            }
            
            .struktur-actions {
                flex-direction: column;
            }
            
            .struktur-actions .btn {
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
                <a href="struktur.php" class="menu-item active">
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
                    <span class="icon-box"><i class="fas fa-sitemap"></i></span>
                    <span class="title-wrap">
                        Struktur Organisasi
                        <small>Atur gambar struktur organisasi yang tampil di website</small>
                    </span>
                </h1>
                <div class="top-bar-actions">
                    <button class="btn btn-success" onclick="showModal()">
                        <i class="fas fa-upload"></i>
                        <?php echo $data ? 'Ganti Gambar' : 'Upload Gambar'; ?>
                    </button>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert">
                <i class="fas fa-check-circle"></i>
                <span>
                    <?php 
                        if ($_GET['success'] == 'save') echo 'Gambar struktur organisasi berhasil disimpan!';
                        if ($_GET['success'] == 'delete') echo 'Gambar struktur organisasi berhasil dihapus!';
                    ?>
                </span>
            </div>
            <?php endif; ?>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <div class="info-box-content">
                    <h4>Informasi</h4>
                    <p>Upload gambar/desain struktur organisasi PKBM yang sudah Anda siapkan. Gambar ini akan ditampilkan di halaman publik website.</p>
                </div>
            </div>
            
            <div class="content-card">
                <div class="struktur-container">
                    <?php if ($data && !empty($data['gambar'])): ?>
                    <div class="struktur-display">
                        <span class="badge badge-<?php echo isset($data['status']) ? $data['status'] : 'aktif'; ?>">
                            <i class="fas fa-<?php echo (isset($data['status']) && $data['status'] == 'aktif') ? 'check-circle' : 'times-circle'; ?>"></i>
                            Status: <?php echo isset($data['status']) ? ucfirst($data['status']) : 'Aktif'; ?>
                        </span>
                        
                        <div class="struktur-image-wrapper">
                            <img src="../uploads/struktur/<?php echo htmlspecialchars($data['gambar']); ?>" 
                                 alt="Struktur Organisasi PKBM" 
                                 class="struktur-main-image"
                                 onclick="viewImage('../uploads/struktur/<?php echo htmlspecialchars($data['gambar']); ?>')">
                        </div>
                        
                        <div class="zoom-hint">
                            <i class="fas fa-search-plus"></i> Klik gambar untuk zoom
                        </div>
                        
                        <?php if (isset($data['keterangan']) && !empty($data['keterangan'])): ?>
                        <div class="struktur-info">
                            <div class="struktur-keterangan">
                                <i class="fas fa-quote-left" style="margin-right: 8px;"></i>
                                <?php echo nl2br(htmlspecialchars($data['keterangan'])); ?>
                                <i class="fas fa-quote-right" style="margin-left: 8px;"></i>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="struktur-actions">
                            <button class="btn btn-primary" onclick="showModal()">
                                <i class="fas fa-sync-alt"></i>
                                Ganti Gambar
                            </button>
                            <a href="?delete=<?php echo $data['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Yakin ingin menghapus gambar struktur organisasi ini?')">
                                <i class="fas fa-trash"></i>
                                Hapus Gambar
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-image"></i></div>
                        <h3>Belum Ada Gambar</h3>
                        <p>Silakan upload gambar struktur organisasi PKBM</p>
                        <button class="btn btn-success" onclick="showModal()">
                            <i class="fas fa-upload"></i>
                            Upload Gambar Sekarang
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal Form Upload -->
    <div class="modal" id="formModal">
        <div class="modal-content">
            <h3>
                <span class="modal-icon"><i class="fas fa-upload"></i></span>
                Upload Gambar Struktur Organisasi
            </h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>
                        <i class="fas fa-image"></i>
                        Pilih Gambar Struktur Organisasi
                    </label>
                    <input type="file" 
                           name="gambar" 
                           id="gambar" 
                           accept="image/*" 
                           onchange="previewImage(this)"
                           required>
                    <div class="upload-info">
                        <i class="fas fa-exclamation-circle"></i>
                        Format: JPG, JPEG, PNG, GIF | Maksimal: 5MB | Upload gambar/desain yang sudah Anda siapkan
                    </div>
                </div>
                
                <div class="upload-preview" id="imagePreview" style="display: none;">
                    <img id="preview" src="" alt="Preview">
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-align-left"></i>
                        Keterangan (Opsional)
                    </label>
                    <textarea name="keterangan" 
                              id="keterangan" 
                              rows="3" 
                              placeholder="Tambahkan keterangan atau deskripsi struktur organisasi (opsional)"></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-toggle-on"></i>
                        Status Tampil
                    </label>
                    <select name="status" id="status">
                        <option value="aktif">Aktif (Tampilkan di website publik)</option>
                        <option value="nonaktif">Non-aktif (Sembunyikan dari publik)</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        Simpan & Upload
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()">
                        <i class="fas fa-times"></i>
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal View Image -->
    <div class="modal" id="imageViewModal">
        <div class="modal-content image-modal">
            <img id="viewImage" src="" alt="Struktur Organisasi">
        </div>
    </div>
    
    <script>
        function showModal() {
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('formModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('formModal').classList.remove('active');
            document.querySelector('form').reset();
        }
        
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').style.display = 'block';
                    document.getElementById('preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function viewImage(src) {
            document.getElementById('viewImage').src = src;
            document.getElementById('imageViewModal').classList.add('active');
        }
        
        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
        
        // Close image modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });
        
        // Auto-hide alert after 5 seconds
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