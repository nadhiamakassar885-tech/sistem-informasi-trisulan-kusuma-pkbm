<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

 $success = '';
 $error = '';

// ========== TAMBAH KEGIATAN ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_kegiatan') {
    $nama_kegiatan = sanitize($_POST['nama_kegiatan']);
    $deskripsi = sanitize($_POST['deskripsi'] ?? '');
    
    if (empty($nama_kegiatan)) {
        $error = 'Nama kegiatan tidak boleh kosong';
    } else {
        $stmt = $conn->prepare("INSERT INTO kegiatan (nama_kegiatan, deskripsi, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $nama_kegiatan, $deskripsi, $_SESSION['admin_id']);
        
        if ($stmt->execute()) {
            $kegiatan_id = $conn->insert_id;
            $success = 'Kegiatan berhasil ditambahkan. Silakan upload foto!';
            logActivity('Tambah kegiatan: ' . $nama_kegiatan);
            
            // Return kegiatan_id untuk auto-select modal upload
            $_SESSION['auto_open_kegiatan'] = $kegiatan_id;
        } else {
            $error = 'Gagal menambahkan kegiatan';
        }
    }
}

// ========== UPLOAD DOKUMENTASI (SIMPLE) ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload_doc') {
    $kegiatan_id = (int)$_POST['kegiatan_id'];
    $judul = sanitize($_POST['judul'] ?? '');
    
    // Jika judul kosong, gunakan nama file
    if (empty($judul) && isset($_FILES['file'])) {
        $judul = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
    }
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] != 0) {
        $error = 'File tidak dipilih atau ada error';
    } else {
        $upload_dir = '../uploads/dokumentasi/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($_FILES['file']['type'], $allowed_types)) {
            $error = 'File harus berupa gambar (JPG, PNG, GIF)';
        } elseif ($_FILES['file']['size'] > 5 * 1024 * 1024) {
            $error = 'Ukuran file maksimal 5MB';
        } else {
            $file_name = time() . '_' . basename($_FILES['file']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
                $file_path = 'dokumentasi/' . $file_name;
                
                $stmt = $conn->prepare("INSERT INTO dokumentasi (kegiatan_id, judul, file_path, uploaded_by) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $kegiatan_id, $judul, $file_path, $_SESSION['admin_id']);
                
                if ($stmt->execute()) {
                    $success = 'Foto berhasil diupload!';
                    logActivity('Upload foto: ' . $judul);
                } else {
                    $error = 'Gagal menyimpan foto ke database';
                    unlink($target_file);
                }
            } else {
                $error = 'Gagal upload file';
            }
        }
    }
}

// ========== EDIT JUDUL DOKUMENTASI ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_judul') {
    $id = (int)$_POST['dokumentasi_id'];
    $judul_baru = sanitize($_POST['judul_baru']);
    
    if (empty($judul_baru)) {
        $error = 'Judul tidak boleh kosong';
    } else {
        $stmt = $conn->prepare("UPDATE dokumentasi SET judul = ? WHERE id = ?");
        $stmt->bind_param("si", $judul_baru, $id);
        
        if ($stmt->execute()) {
            $success = 'Judul foto berhasil diubah!';
            logActivity('Edit judul foto menjadi: ' . $judul_baru);
        } else {
            $error = 'Gagal mengubah judul';
        }
    }
}

// ========== HAPUS DOKUMENTASI ==========
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $result = $conn->query("SELECT file_path FROM dokumentasi WHERE id = $id");
    if ($row = $result->fetch_assoc()) {
        $file = '../uploads/' . $row['file_path'];
        if (file_exists($file)) {
            unlink($file);
        }
    }
    $conn->query("DELETE FROM dokumentasi WHERE id = $id");
    logActivity('Hapus foto');
    header('Location: dokumentasi.php?success=delete');
    exit;
}

// ========== HAPUS KEGIATAN ==========
if (isset($_GET['delete_kegiatan'])) {
    $id = (int)$_GET['delete_kegiatan'];
    
    // Hapus semua foto di kegiatan ini
    $result = $conn->query("SELECT file_path FROM dokumentasi WHERE kegiatan_id = $id");
    while ($row = $result->fetch_assoc()) {
        $file = '../uploads/' . $row['file_path'];
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    $conn->query("DELETE FROM dokumentasi WHERE kegiatan_id = $id");
    $conn->query("DELETE FROM kegiatan WHERE id = $id");
    logActivity('Hapus kegiatan');
    header('Location: dokumentasi.php?success=delete');
    exit;
}

// ========== GET KEGIATAN ==========
 $kegiatan_result = $conn->query("SELECT k.*, COUNT(d.id) as total_dokumentasi, a.nama_lengkap as creator FROM kegiatan k LEFT JOIN dokumentasi d ON k.id = d.kegiatan_id LEFT JOIN admin_users a ON k.created_by = a.id GROUP BY k.id ORDER BY k.created_at DESC");

 $kegiatan_list = [];
if ($kegiatan_result) {
    while ($row = $kegiatan_result->fetch_assoc()) {
        $kegiatan_list[] = $row;
    }
}

 $auto_open_kegiatan = isset($_SESSION['auto_open_kegiatan']) ? $_SESSION['auto_open_kegiatan'] : null;
if ($auto_open_kegiatan) {
    unset($_SESSION['auto_open_kegiatan']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Dokumentasi - Admin PKBM</title>
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
        
        .btn-success { 
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark));
            color: white;
            box-shadow: 0 4px 14px rgba(0,206,209,0.35);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,206,209,0.45);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #f7b731);
            color: white;
            box-shadow: 0 4px 12px rgba(243,156,18,0.3);
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(243,156,18,0.4);
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
            background: linear-gradient(135deg, #e8f8ee 0%, #d4f5e0 100%);
            color: #155724;
            border: 1px solid rgba(39, 174, 96, 0.25);
            border-left: 4px solid var(--success-color);
        }
        
        .alert-error {
            background: #fff5f5;
            color: #721c24;
            border: 1px solid rgba(231, 76, 60, 0.3);
            border-left-color: var(--danger-color);
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert i {
            font-size: 19px;
        }
        
        /* ============ KEGIATAN SECTION ============ */
        .kegiatan-section {
            margin-bottom: 28px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            background: var(--white);
        }
        
        .kegiatan-section:last-child {
            margin-bottom: 0;
        }
        
        .kegiatan-header {
            background: linear-gradient(135deg, var(--primary-deep) 0%, var(--primary-color) 55%, #005AA7 100%);
            color: white;
            padding: 22px 26px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            position: relative;
            overflow: hidden;
        }
        
        .kegiatan-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-color), var(--secondary-color));
        }
        
        .kegiatan-title {
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
            min-width: 0;
        }
        
        .kegiatan-title .kg-icon {
            width: 46px;
            height: 46px;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--accent-color);
            flex-shrink: 0;
            backdrop-filter: blur(8px);
        }
        
        .kegiatan-title h3 {
            font-size: 17px;
            font-weight: 700;
            margin: 0;
            line-height: 1.35;
        }
        
        .kegiatan-info {
            font-size: 12px;
            opacity: 0.85;
            margin-top: 4px;
            font-weight: 400;
        }
        
        .kegiatan-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .kegiatan-count {
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.28);
            padding: 7px 16px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            backdrop-filter: blur(8px);
            white-space: nowrap;
        }
        
        .kegiatan-count i {
            color: var(--accent-color);
            margin-right: 4px;
        }
        
        .kegiatan-body {
            padding: 24px;
            background: var(--white);
        }
        
        /* ============ GALLERY ============ */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 18px;
        }
        
        .gallery-item {
            position: relative;
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            background: var(--white);
            border: 1px solid var(--border-color);
        }
        
        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--secondary-color);
        }
        
        .gallery-image {
            position: relative;
            overflow: hidden;
            height: 190px;
            background: var(--bg-light);
            cursor: pointer;
        }
        
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .gallery-item:hover img {
            transform: scale(1.06);
        }
        
        .gallery-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent, rgba(0, 26, 61, 0.6));
            opacity: 0;
            transition: var(--transition);
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding: 15px;
        }
        
        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }
        
        .gallery-overlay span {
            color: white;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(0, 45, 97, 0.75);
            backdrop-filter: blur(6px);
            padding: 7px 14px;
            border-radius: 50px;
            border: 1px solid rgba(255,255,255,0.25);
        }
        
        .gallery-info {
            padding: 14px 16px 12px;
            background: var(--white);
        }
        
        .gallery-info h4 {
            font-size: 13.5px;
            margin-bottom: 8px;
            color: var(--primary-color);
            font-weight: 700;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 38px;
            line-height: 1.45;
        }
        
        .gallery-info p {
            font-size: 11.5px;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
        }
        
        .gallery-info p i {
            font-size: 10px;
            color: var(--secondary-dark);
            width: 12px;
            text-align: center;
        }
        
        .gallery-actions {
            padding: 10px 14px;
            background: var(--bg-light);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 7px 13px;
            font-size: 11px;
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: none;
            font-family: inherit;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .btn-sm.btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #f7b731);
            color: white;
            cursor: pointer;
        }
        
        .btn-sm.btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(243,156,18,0.35);
        }
        
        .btn-sm.btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #ff6b6b);
            color: white;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn-sm.btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(231,76,60,0.35);
        }
        
        /* Empty kegiatan */
        .kegiatan-empty {
            text-align: center;
            padding: 40px 20px;
        }
        
        .kegiatan-empty .ke-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 14px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: var(--primary-color);
        }
        
        .kegiatan-empty p {
            color: var(--text-light);
            font-size: 13.5px;
            font-weight: 500;
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
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 26px;
            padding-bottom: 18px;
            border-bottom: 2px solid var(--border-color);
            position: relative;
        }
        
        .modal-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 70px;
            height: 2px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
        }
        
        .modal-header h3 {
            margin: 0;
            color: var(--primary-color);
            font-size: 19px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 13px;
        }
        
        .modal-header h3 .modal-icon {
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
        
        .modal-close {
            background: var(--bg-light);
            border: 1px solid var(--border-color);
            width: 38px;
            height: 38px;
            border-radius: 50%;
            font-size: 16px;
            cursor: pointer;
            color: var(--text-light);
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .modal-close:hover {
            background: var(--danger-color);
            border-color: var(--danger-color);
            color: white;
            transform: rotate(90deg);
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
        
        .form-group label .required {
            color: var(--danger-color);
            font-weight: 700;
        }
        
        .form-group label small {
            color: var(--text-light);
            font-size: 11.5px;
            font-weight: 500;
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
            background: var(--bg-light);
            color: var(--text-dark);
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(0,206,209,0.12);
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #a5aebc;
        }
        
        .form-group textarea {
            resize: vertical;
            line-height: 1.7;
            min-height: 85px;
        }
        
        .form-group input[readonly] {
            background: var(--primary-light);
            color: var(--primary-color);
            font-weight: 600;
            cursor: not-allowed;
            border-color: #c9d9ef;
        }
        
        .form-group input[type="file"] {
            display: none;
        }
        
        .file-input-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 36px 16px;
            background: var(--bg-light);
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            font-weight: 600;
            color: var(--primary-color);
            font-size: 13.5px;
        }
        
        .file-input-label:hover {
            border-color: var(--secondary-color);
            background: #f0f8ff;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        .file-input-label i {
            font-size: 40px;
            color: var(--secondary-dark);
        }
        
        .file-input-label small {
            color: var(--text-light);
            font-weight: 500;
            font-size: 11.5px;
        }
        
        .preview-image {
            margin-top: 15px;
            border-radius: var(--radius-md);
            overflow: hidden;
            display: none;
            border: 2px solid var(--success-color);
        }
        
        .preview-image img {
            width: 100%;
            max-height: 200px;
            object-fit: cover;
            display: block;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 26px;
            padding-top: 22px;
            border-top: 2px solid var(--border-color);
        }
        
        .modal-actions button {
            flex: 1;
            padding: 13px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .modal-actions .btn-success {
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark));
            color: white;
            box-shadow: 0 4px 14px rgba(0,206,209,0.35);
        }
        
        .modal-actions .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,206,209,0.45);
        }
        
        .modal-actions .btn-cancel {
            background: var(--white);
            color: var(--primary-color);
            border: 2px solid var(--border-color);
        }
        
        .modal-actions .btn-cancel:hover {
            background: var(--primary-light);
            border-color: var(--secondary-color);
        }
        
        /* ============ EMPTY STATE ============ */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
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
        
        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: var(--text-light);
            font-weight: 700;
        }
        
        .empty-state p {
            font-size: 13.5px;
            color: var(--text-light);
            opacity: 0.8;
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
            
            .kegiatan-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .kegiatan-actions {
                width: 100%;
            }
            
            .kegiatan-actions .btn {
                flex: 1;
                justify-content: center;
            }
            
            .gallery-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .modal-content {
                padding: 26px 20px;
            }
            
            .modal-actions {
                flex-direction: column-reverse;
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
                <a href="struktur.php" class="menu-item">
                    <i class="fas fa-sitemap"></i>
                    <span>Struktur Organisasi</span>
                </a>
                <a href="dokumen.php" class="menu-item">
                    <i class="fas fa-folder-open"></i>
                    <span>Dokumen</span>
                </a>
                <a href="dokumentasi.php" class="menu-item active">
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
                    <span class="icon-box"><i class="fas fa-images"></i></span>
                    <span class="title-wrap">
                        Kelola Dokumentasi Kegiatan
                        <small>Atur kegiatan dan galeri foto yang tampil di website</small>
                    </span>
                </h1>
                <div class="top-bar-actions">
                    <button class="btn btn-success" onclick="showModalKegiatan()">
                        <i class="fas fa-plus"></i>
                        Tambah Kegiatan
                    </button>
                </div>
            </div>
            
            <?php if ($success): ?>
            <div class="alert">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($kegiatan_list)): ?>
                <?php foreach ($kegiatan_list as $kegiatan): ?>
                <div class="kegiatan-section" data-kegiatan-id="<?php echo $kegiatan['id']; ?>">
                    <div class="kegiatan-header">
                        <div class="kegiatan-title">
                            <div class="kg-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div>
                                <h3><?php echo htmlspecialchars($kegiatan['nama_kegiatan']); ?></h3>
                                <?php if ($kegiatan['deskripsi']): ?>
                                <div class="kegiatan-info"><i class="fas fa-align-left"></i> <?php echo htmlspecialchars(substr($kegiatan['deskripsi'], 0, 80)); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="kegiatan-actions">
                            <div class="kegiatan-count">
                                <i class="fas fa-images"></i> <?php echo $kegiatan['total_dokumentasi']; ?> Foto
                            </div>
                            <button class="btn btn-success" onclick="showModalDokumentasi(<?php echo $kegiatan['id']; ?>, '<?php echo htmlspecialchars(addslashes($kegiatan['nama_kegiatan'])); ?>')">
                                <i class="fas fa-plus"></i> Upload
                            </button>
                            <a href="?delete_kegiatan=<?php echo $kegiatan['id']; ?>" class="btn btn-danger" onclick="return confirm('Hapus kegiatan dan semua fotonya?')">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </div>
                    </div>
                    
                    <div class="kegiatan-body">
                        <?php 
                        $dok_result = $conn->query("SELECT d.*, a.nama_lengkap as uploader FROM dokumentasi d LEFT JOIN admin_users a ON d.uploaded_by = a.id WHERE d.kegiatan_id = {$kegiatan['id']} ORDER BY d.created_at DESC");
                        $dokumentasi_list = [];
                        while ($dok = $dok_result->fetch_assoc()) {
                            $dokumentasi_list[] = $dok;
                        }
                        
                        if (!empty($dokumentasi_list)): 
                        ?>
                        <div class="gallery-grid">
                            <?php foreach ($dokumentasi_list as $dok): ?>
                            <div class="gallery-item">
                                <div class="gallery-image" onclick="showModalEditJudul(<?php echo $dok['id']; ?>, '<?php echo htmlspecialchars(addslashes($dok['judul'])); ?>')">
                                    <img src="../uploads/<?php echo htmlspecialchars($dok['file_path']); ?>" alt="<?php echo htmlspecialchars($dok['judul']); ?>">
                                    <div class="gallery-overlay">
                                        <span><i class="fas fa-edit"></i> Edit Judul</span>
                                    </div>
                                </div>
                                <div class="gallery-info">
                                    <h4><?php echo htmlspecialchars($dok['judul']); ?></h4>
                                    <p>
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($dok['uploader'] ?? 'Admin'); ?>
                                    </p>
                                    <p>
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('d/m/Y', strtotime($dok['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="gallery-actions">
                                    <button class="btn-sm btn-warning" onclick="showModalEditJudul(<?php echo $dok['id']; ?>, '<?php echo htmlspecialchars(addslashes($dok['judul'])); ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="?delete=<?php echo $dok['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Hapus foto ini?')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="kegiatan-empty">
                            <div class="ke-icon"><i class="fas fa-image"></i></div>
                            <p>Belum ada dokumentasi untuk kegiatan ini</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="kegiatan-section">
                <div class="kegiatan-body">
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
                        <h3>Belum Ada Kegiatan</h3>
                        <p>Mulai dengan menambahkan kegiatan baru</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Modal Tambah Kegiatan -->
    <div class="modal" id="modalKegiatan">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <span class="modal-icon"><i class="fas fa-plus-circle"></i></span>
                    Tambah Kegiatan Baru
                </h3>
                <button class="modal-close" onclick="closeModalKegiatan()" aria-label="Tutup">×</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="add_kegiatan">
                
                <div class="form-group">
                    <label>Nama Kegiatan <span class="required">*</span></label>
                    <input type="text" name="nama_kegiatan" placeholder="Contoh: Wisuda 2024, Pelatihan Komputer" required>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi Kegiatan</label>
                    <textarea name="deskripsi" placeholder="Jelaskan singkat tentang kegiatan ini (opsional)"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn-success">
                        <i class="fas fa-save"></i> Tambah & Lanjut Upload
                    </button>
                    <button type="button" class="btn-cancel" onclick="closeModalKegiatan()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Upload Dokumentasi -->
    <div class="modal" id="modalDokumentasi">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <span class="modal-icon"><i class="fas fa-upload"></i></span>
                    Upload Foto
                </h3>
                <button class="modal-close" onclick="closeModalDokumentasi()" aria-label="Tutup">×</button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_doc">
                <input type="hidden" name="kegiatan_id" id="kegiatanId">
                
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt" style="color:var(--secondary-dark);"></i> Kegiatan</label>
                    <input type="text" id="kegiatanNama" readonly>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-heading" style="color:var(--secondary-dark);"></i> Judul Foto <small>(opsional - auto dari nama file jika kosong)</small></label>
                    <input type="text" name="judul" placeholder="Biarkan kosong untuk auto-generate dari nama file">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-paperclip" style="color:var(--secondary-dark);"></i> File Foto (JPG, PNG, GIF) <span class="required">*</span></label>
                    <input type="file" name="file" id="fileInput" accept="image/*" onchange="previewImage(this)" required>
                    <label for="fileInput" class="file-input-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Klik untuk memilih foto</span>
                        <small>Format: JPG, PNG, GIF (Max 5MB)</small>
                    </label>
                    <div class="preview-image" id="imagePreview">
                        <img id="previewImg" src="" alt="Preview">
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn-success">
                        <i class="fas fa-upload"></i> Upload Foto
                    </button>
                    <button type="button" class="btn-cancel" onclick="closeModalDokumentasi()">
                        <i class="fas fa-times"></i> Selesai
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Edit Judul Foto -->
    <div class="modal" id="modalEditJudul">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <span class="modal-icon"><i class="fas fa-edit"></i></span>
                    Edit Judul Foto
                </h3>
                <button class="modal-close" onclick="closeModalEditJudul()" aria-label="Tutup">×</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="edit_judul">
                <input type="hidden" name="dokumentasi_id" id="dokumentasiId">
                
                <div class="form-group">
                    <label><i class="fas fa-heading" style="color:var(--secondary-dark);"></i> Judul Baru <span class="required">*</span></label>
                    <input type="text" name="judul_baru" id="judulBaru" placeholder="Masukkan judul baru" required>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn-success">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                    <button type="button" class="btn-cancel" onclick="closeModalEditJudul()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Modal Kegiatan
        function showModalKegiatan() {
            document.getElementById('modalKegiatan').classList.add('active');
        }
        
        function closeModalKegiatan() {
            document.getElementById('modalKegiatan').classList.remove('active');
            document.querySelector('#modalKegiatan form').reset();
        }
        
        // Modal Dokumentasi
        function showModalDokumentasi(kegiatanId, kegiatanNama) {
            document.getElementById('kegiatanId').value = kegiatanId;
            document.getElementById('kegiatanNama').value = kegiatanNama;
            document.getElementById('modalDokumentasi').classList.add('active');
            document.getElementById('fileInput').value = '';
            document.getElementById('imagePreview').style.display = 'none';
        }
        
        function closeModalDokumentasi() {
            document.getElementById('modalDokumentasi').classList.remove('active');
        }
        
        // Modal Edit Judul
        function showModalEditJudul(dokumentasiId, judulLama) {
            document.getElementById('dokumentasiId').value = dokumentasiId;
            document.getElementById('judulBaru').value = judulLama;
            document.getElementById('modalEditJudul').classList.add('active');
            document.getElementById('judulBaru').focus();
            document.getElementById('judulBaru').select();
        }
        
        function closeModalEditJudul() {
            document.getElementById('modalEditJudul').classList.remove('active');
        }
        
        // Preview Image
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
        
        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
        
        // Auto-hide alert
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'all 0.3s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
        
        // Auto-open upload modal jika baru tambah kegiatan
        <?php if ($auto_open_kegiatan): ?>
        document.addEventListener('DOMContentLoaded', function() {
            let kegiatan = document.querySelector('[data-kegiatan-id="<?php echo $auto_open_kegiatan; ?>"]');
            if (kegiatan) {
                // Kegiatan yang baru ditambahkan ditemukan langsung via data attribute
                const buttons = kegiatan.querySelectorAll('button');
                if (buttons.length > 0) {
                    buttons[0].click();
                }
            } else {
                // Fallback: cari yang baru (paling atas)
                const kegiatanSelectors = document.querySelectorAll('.kegiatan-section');
                if (kegiatanSelectors.length > 0) {
                    const buttons = kegiatanSelectors[0].querySelectorAll('button');
                    if (buttons.length > 0) {
                        buttons[0].click();
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>