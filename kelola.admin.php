<?php
require_once '../config.php';

// ✅ Wajib login untuk akses
requireLogin();

 $success_msg = '';
 $error_msg = '';

// ============ CREATE ADMIN ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambahDataAdmin'])) {
    $username = sanitize($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Untuk sistem yang simple: nama = username, email kosong, role selalu 'admin'
    $nama_admin = $username;
    $email = '';
    $role = 'admin';
    
    // Validasi
    if (empty($username) || empty($password)) {
        $error_msg = '❌ Username dan password tidak boleh kosong!';
    } elseif (strlen($username) < 3) {
        $error_msg = '❌ Username minimal 3 karakter!';
    } elseif (strlen($password) < 6) {
        $error_msg = '❌ Password harus minimal 6 karakter!';
    } elseif ($password !== $confirm_password) {
        $error_msg = '❌ Password dan konfirmasi password tidak cocok!';
    } else {
        // Cek username sudah ada
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_msg = '❌ Username sudah terdaftar!';
        } else {
            $hashed_pwd = password_hash($password, PASSWORD_DEFAULT);
            $created_at = date('Y-m-d H:i:s');
            
            // Insert dengan prepared statement
            $insert_stmt = $conn->prepare("INSERT INTO admin_users (username, password, nama_lengkap, email, role, created_at) 
                                          VALUES (?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("ssssss", $username, $hashed_pwd, $nama_admin, $email, $role, $created_at);
            
            if ($insert_stmt->execute()) {
                $success_msg = '✅ Admin baru <strong>' . htmlspecialchars($username) . '</strong> berhasil ditambahkan!';
                logActivity('Menambah admin baru: ' . $username);
            } else {
                $error_msg = '❌ Gagal menambahkan admin: ' . $insert_stmt->error;
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}

// ============ UPDATE ADMIN ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editDataAdmin'])) {
    $id = (int)$_POST['id_admin'];
    $new_username = sanitize($_POST['username'] ?? '');
    
    // Jangan bisa ubah admin diri sendiri
    if ($id == $_SESSION['admin_id']) {
        $error_msg = '❌ Anda tidak bisa mengubah akun sendiri!';
    } elseif (empty($new_username)) {
        $error_msg = '❌ Username tidak boleh kosong!';
    } elseif (strlen($new_username) < 3) {
        $error_msg = '❌ Username minimal 3 karakter!';
    } else {
        // Get current username
        $get_stmt = $conn->prepare("SELECT username FROM admin_users WHERE id = ?");
        $get_stmt->bind_param("i", $id);
        $get_stmt->execute();
        $current = $get_stmt->get_result();
        $current_data = $current->fetch_assoc();
        $get_stmt->close();
        
        // Jika username berubah, cek duplikat
        $can_update = true;
        if ($new_username !== $current_data['username']) {
            $check_stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
            $check_stmt->bind_param("s", $new_username);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error_msg = '❌ Username sudah digunakan!';
                $can_update = false;
            }
            $check_stmt->close();
        }
        
        if ($can_update) {
            // Admin hanya bisa update username (sederhana)
            $update_stmt = $conn->prepare("UPDATE admin_users SET username = ?, nama_lengkap = ? WHERE id = ?");
            $update_stmt->bind_param("ssi", $new_username, $new_username, $id);
            
            if ($update_stmt->execute()) {
                $success_msg = '✅ Data admin berhasil diperbarui!';
                logActivity('Mengubah data admin');
            } else {
                $error_msg = '❌ Gagal memperbarui admin: ' . $update_stmt->error;
            }
            $update_stmt->close();
        }
    }
}

// ============ CHANGE PASSWORD ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changePassword'])) {
    $id = (int)$_POST['id_admin'];
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Jangan bisa ubah password diri sendiri
    if ($id == $_SESSION['admin_id']) {
        $error_msg = '❌ Anda tidak bisa mengubah password akun sendiri!';
    } elseif (empty($new_password)) {
        $error_msg = '❌ Password baru tidak boleh kosong!';
    } elseif (strlen($new_password) < 6) {
        $error_msg = '❌ Password minimal 6 karakter!';
    } elseif ($new_password !== $confirm_password) {
        $error_msg = '❌ Password tidak cocok!';
    } else {
        $hashed_pwd = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update dengan prepared statement
        $pwd_stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
        $pwd_stmt->bind_param("si", $hashed_pwd, $id);
        
        if ($pwd_stmt->execute()) {
            $success_msg = '✅ Password admin berhasil diubah!';
            logActivity('Mengubah password admin');
        } else {
            $error_msg = '❌ Gagal mengubah password: ' . $pwd_stmt->error;
        }
        $pwd_stmt->close();
    }
}

// ============ DELETE ADMIN ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteDataAdmin'])) {
    $id = (int)$_POST['id_admin'];
    
    // Validasi ID
    if (empty($id) || $id <= 0) {
        $error_msg = '❌ ID admin tidak valid!';
    }
    // Jangan bisa hapus diri sendiri
    elseif ($id == $_SESSION['admin_id']) {
        $error_msg = '❌ Anda tidak bisa menghapus akun sendiri!';
    } else {
        // Get admin info terlebih dahulu
        $get_delete_stmt = $conn->prepare("SELECT id, username, nama_lengkap FROM admin_users WHERE id = ?");
        if (!$get_delete_stmt) {
            $error_msg = '❌ Database error: ' . $conn->error;
        } else {
            $get_delete_stmt->bind_param("i", $id);
            $get_delete_stmt->execute();
            $get_result = $get_delete_stmt->get_result();
            
            if ($get_result->num_rows > 0) {
                $admin_to_delete = $get_result->fetch_assoc();
                
                // Delete dengan prepared statement
                $delete_stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
                if (!$delete_stmt) {
                    $error_msg = '❌ Database error: ' . $conn->error;
                } else {
                    $delete_stmt->bind_param("i", $id);
                    
                    if ($delete_stmt->execute()) {
                        // Check jika delete berhasil
                        if ($delete_stmt->affected_rows > 0) {
                            $success_msg = '✅ Admin <strong>' . htmlspecialchars($admin_to_delete['username']) . '</strong> berhasil dihapus!';
                            logActivity('Menghapus admin: ' . $admin_to_delete['username']);
                        } else {
                            $error_msg = '❌ Admin gagal dihapus (tidak ada perubahan)!';
                        }
                    } else {
                        $error_msg = '❌ Gagal menghapus admin: ' . $delete_stmt->error;
                    }
                    $delete_stmt->close();
                }
            } else {
                $error_msg = '❌ Admin dengan ID ' . $id . ' tidak ditemukan!';
            }
            $get_delete_stmt->close();
        }
    }
}

// ============ GET DAFTAR ADMIN ============
 $admins = [];
 $result = $conn->query("SELECT id, username, nama_lengkap, email, role, created_at, last_login FROM admin_users ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Admin - PKBM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
        
        body { 
            font-family: 'Poppins', 'Segoe UI', Tahoma, sans-serif;
            background: var(--bg-light);
            font-size: 14px;
            color: var(--text-dark);
        }
        
        .admin-wrapper { display: flex; min-height: 100vh; }
        
        /* ============ SIDEBAR (KONSISTEN) ============ */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, var(--primary-deep) 0%, var(--primary-dark) 50%, var(--primary-color) 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 999;
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
            margin: 0;
        }
        
        .sidebar-header p::before {
            content: '';
            width: 7px;
            height: 7px;
            background: #2ecc71;
            border-radius: 50%;
            box-shadow: 0 0 8px #2ecc71;
            flex-shrink: 0;
        }
        
        .sidebar-menu { padding: 20px 14px; }
        
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
            border-left: none;
        }
        
        .menu-item i { 
            width: 20px; 
            text-align: center; 
            font-size: 15px;
            transition: var(--transition);
        }
        
        .menu-item:hover,
        .menu-item.active {
            background: rgba(255,255,255,0.08);
            color: white;
        }
        
        .menu-item:hover {
            transform: translateX(4px);
        }
        
        .menu-item:hover i { color: var(--secondary-color); }
        
        .menu-item.active {
            background: linear-gradient(90deg, rgba(0,206,209,0.2), rgba(0,206,209,0.05));
            font-weight: 600;
            padding-left: 16px;
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
        
        .menu-item.active i { color: var(--accent-color); }
        
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
            margin: 0;
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
        
        /* ============ ALERTS (Bootstrap override) ============ */
        .alert {
            border-radius: var(--radius-md);
            border: none;
            margin-bottom: 24px;
            font-size: 13.5px;
            font-weight: 500;
            padding: 16px 20px;
            animation: slideDown 0.35s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-success {
            background: linear-gradient(135deg, #e8f8ee 0%, #d4f5e0 100%);
            color: #155724;
            border-left: 4px solid var(--success-color);
        }
        
        .alert-danger {
            background: #fff5f5;
            color: #721c24;
            border-left: 4px solid var(--danger-color);
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #FFF9E6 0%, #FFF3C4 100%);
            color: #9c7400;
            border-left: 4px solid var(--warning-color);
        }
        
        .alert-info {
            background: linear-gradient(135deg, #f0f8ff 0%, #eaf5fc 100%);
            color: #14697c;
            border-left: 4px solid var(--secondary-color);
        }
        
        /* ============ CARD (Bootstrap override) ============ */
        .card {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-bottom: 0;
            position: relative;
            background: var(--white);
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
            z-index: 1;
        }
        
        .card-header {
            background: var(--white) !important;
            color: var(--primary-color) !important;
            padding: 24px 30px;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
            font-size: 17px;
            flex-wrap: wrap;
            gap: 12px;
            position: relative;
        }
        
        .card-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 30px;
            width: 70px;
            height: 2px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
        }
        
        .card-header h5 {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 16px;
            font-weight: 700;
        }
        
        .card-header h5 .card-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), #005AA7);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(0,61,130,0.25);
        }
        
        /* ============ BUTTONS (Bootstrap override) ============ */
        .btn {
            border-radius: var(--radius-sm);
            font-weight: 600;
            transition: var(--transition);
            border: none;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-color), #FFA500) !important;
            color: var(--primary-deep) !important;
            box-shadow: 0 4px 14px rgba(255,215,0,0.35);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,215,0,0.5);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #ff6b6b) !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(231,76,60,0.3);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(231,76,60,0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #f7b731) !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(243,156,18,0.3);
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(243,156,18,0.4);
        }
        
        .btn-secondary {
            background: var(--bg-light) !important;
            color: var(--primary-color) !important;
            border: 2px solid var(--border-color) !important;
        }
        
        .btn-secondary:hover {
            background: var(--primary-light) !important;
            border-color: var(--secondary-color) !important;
        }
        
        .btn-sm {
            padding: 8px 14px;
            font-size: 12px;
        }
        
        /* ============ TABLE (Bootstrap override) ============ */
        .table { margin-bottom: 0; }
        
        .table thead {
            background: linear-gradient(135deg, var(--primary-deep) 0%, var(--primary-color) 60%, #005AA7 100%);
            color: white;
        }
        
        .table thead th {
            padding: 16px 16px;
            font-weight: 600;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.95);
            border: none;
            background: transparent;
        }
        
        .table th, .table td {
            vertical-align: middle;
            padding: 15px 16px;
            border: none;
        }
        
        .table tbody tr {
            transition: var(--transition);
            border-bottom: 1px solid var(--border-color);
        }
        
        .table tbody tr:last-child { border-bottom: none; }
        
        .table tbody tr:hover {
            background-color: rgba(0,206,209,0.045);
            transform: none;
        }
        
        .table td {
            font-size: 13px;
            color: var(--text-light);
        }
        
        .table td strong {
            color: var(--primary-color);
            font-weight: 700;
        }
        
        .text-success {
            color: var(--success-color) !important;
            font-weight: 600;
        }
        
        /* ============ MODAL (Bootstrap override) ============ */
        .modal-content {
            border-radius: var(--radius-lg);
            border: none;
            box-shadow: 0 25px 80px rgba(0, 10, 40, 0.4);
            overflow: hidden;
        }
        
        .modal-header {
            background: var(--white);
            color: var(--primary-color);
            border-bottom: 2px solid var(--border-color);
            padding: 22px 28px;
            position: relative;
        }
        
        .modal-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 28px;
            width: 70px;
            height: 2px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
        }
        
        .modal-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 17px;
            font-weight: 700;
        }
        
        .modal-title .modal-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), #005AA7);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .modal-body {
            padding: 28px;
            font-size: 13.5px;
        }
        
        .btn-close-white { filter: none; }
        
        /* ============ FORM (Bootstrap override) ============ */
        .form-control {
            border-radius: var(--radius-sm);
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            font-family: 'Poppins', sans-serif;
            font-size: 13.5px;
            transition: var(--transition);
            background: var(--bg-light);
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(0,206,209,0.12);
            background: var(--white);
        }
        
        .form-control:disabled {
            background: var(--bg-light);
            opacity: 0.7;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        /* Misc */
        code {
            background: var(--primary-light);
            padding: 5px 12px;
            border-radius: 50px;
            color: var(--primary-color);
            font-weight: 700;
            font-size: 12px;
            border: 1px solid #c9d9ef;
        }
        
        .login-now-badge {
            color: var(--success-color);
            font-size: 11px;
            font-weight: 600;
        }
        
        .delete-warning-icon {
            font-size: 56px;
            color: var(--danger-color);
            margin: 16px 0;
            display: block;
        }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 18px; }
            .top-bar { padding: 20px; }
            .top-bar h1 { font-size: 18px; }
            .card-header { padding: 20px; flex-direction: column; align-items: stretch; }
            .card-header .btn { width: 100%; }
            .modal-body { padding: 22px 18px; }
            .modal-header { padding: 18px 20px; }
            .d-flex.gap-2 { flex-direction: column-reverse; }
            .d-flex.gap-2 .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h2>Admin PKBM</h2>
                <p><?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
            </div>

            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-item"><i class="fas fa-chart-line"></i><span>Dashboard</span></a>
                <a href="profil.php" class="menu-item"><i class="fas fa-building"></i><span>Profil PKBM</span></a>
                <a href="program.php" class="menu-item"><i class="fas fa-book-open"></i><span>Program Paket</span></a>
                <a href="pendaftaran.php" class="menu-item"><i class="fas fa-users"></i><span>Data Pendaftar</span></a>
                <a href="informasi.php" class="menu-item"><i class="fas fa-bullhorn"></i><span>Informasi</span></a>
                <a href="struktur.php" class="menu-item"><i class="fas fa-sitemap"></i><span>Struktur Organisasi</span></a>
                <a href="dokumen.php" class="menu-item"><i class="fas fa-folder-open"></i><span>Dokumen</span></a>
                <a href="dokumentasi.php" class="menu-item"><i class="fas fa-images"></i><span>Dokumentasi</span></a>
                <a href="kontak.php" class="menu-item"><i class="fas fa-phone-alt"></i><span>Kontak & Lokasi</span></a>
                <a href="kelola.admin.php" class="menu-item active"><i class="fas fa-user-shield"></i><span>Kelola Admin</span></a>
                <a href="logout.php" class="menu-item menu-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </nav>
        </aside>
        
        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- PAGE HEADER -->
            <div class="top-bar">
                <h1>
                    <span class="icon-box"><i class="fas fa-users-cog"></i></span>
                    <span class="title-wrap">
                        Manajemen Administrator
                        <small>Tambah, ubah, dan hapus akun admin sistem</small>
                    </span>
                </h1>
            </div>
            
            <!-- ALERTS -->
            <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- MAIN CARD -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <span class="card-icon"><i class="fas fa-table"></i></span>
                        Daftar Admin
                    </h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                        <i class="fas fa-plus me-1"></i> Tambah Admin
                    </button>
                </div>
                
                <div class="card-body p-0">
                    <!-- MODAL TAMBAH ADMIN - SIMPLE -->
                    <div class="modal fade" id="addAdminModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><span class="modal-icon"><i class="fas fa-user-plus"></i></span>Tambah Admin Baru</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Tambah Admin:</strong> Isi username dan password untuk admin baru
                                    </div>
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label class="form-label">Username <span style="color:red;">*</span></label>
                                            <input name="username" type="text" class="form-control" placeholder="Minimal 3 karakter" minlength="3" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Password <span style="color:red;">*</span></label>
                                            <input name="password" type="password" class="form-control" placeholder="Minimal 6 karakter" minlength="6" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Konfirmasi Password <span style="color:red;">*</span></label>
                                            <input name="confirm_password" type="password" class="form-control" placeholder="Ketik ulang password" minlength="6" required>
                                        </div>
                                        
                                        <div class="d-flex gap-2 justify-content-end">
                                            <button type="submit" name="tambahDataAdmin" class="btn btn-primary btn-sm">
                                                <i class="fas fa-save me-1"></i>Simpan
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                                                <i class="fas fa-times me-1"></i>Batal
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- TABLE -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag" style="color:var(--secondary-color);margin-right:6px;"></i>No</th>
                                    <th><i class="fas fa-user" style="color:var(--secondary-color);margin-right:6px;"></i>Nama Admin</th>
                                    <th><i class="fas fa-id-badge" style="color:var(--secondary-color);margin-right:6px;"></i>Username</th>
                                    <th><i class="fas fa-clock" style="color:var(--secondary-color);margin-right:6px;"></i>Last Login</th>
                                    <th class="text-center"><i class="fas fa-cogs" style="color:var(--secondary-color);margin-right:6px;"></i>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($admins)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox" style="font-size:48px;color:#d3dae4;display:block;margin-bottom:12px;"></i>
                                        Tidak ada data admin
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($admins as $admin): ?>
                                    <tr>
                                        <td><strong><?php echo $no++; ?></strong></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($admin['nama_lengkap']); ?></strong>
                                            <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                            <br><small class="text-success login-now-badge">✓ Anda (Login Sekarang)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($admin['username']); ?></code></td>
                                        <td>
                                            <small>
                                                <?php echo $admin['last_login'] ? date('d/m/Y H:i', strtotime($admin['last_login'])) : '<span class="text-muted">Belum login</span>'; ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#edit<?php echo $admin['id']; ?>" title="Edit Username">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#password<?php echo $admin['id']; ?>" title="Ubah Password">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#delete<?php echo $admin['id']; ?>" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php else: ?>
                                            <span class="text-muted" title="Akun Anda Sendiri">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    
                                    <!-- MODAL EDIT -->
                                    <div class="modal fade" id="edit<?php echo $admin['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><span class="modal-icon"><i class="fas fa-pen"></i></span>Edit Admin</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="alert alert-warning mb-3">
                                                        <i class="fas fa-exclamation-circle me-2"></i>
                                                        Hanya bisa mengubah username
                                                    </div>
                                                    <form method="POST">
                                                        <input type="hidden" name="id_admin" value="<?php echo $admin['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Username Lama</label>
                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin['username']); ?>" disabled>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Username Baru <span style="color:red;">*</span></label>
                                                            <input name="username" type="text" class="form-control" value="<?php echo htmlspecialchars($admin['username']); ?>" minlength="3" required>
                                                        </div>
                                                        
                                                        <div class="d-flex gap-2 justify-content-end">
                                                            <button type="submit" name="editDataAdmin" class="btn btn-primary btn-sm">
                                                                <i class="fas fa-save me-1"></i>Simpan
                                                            </button>
                                                            <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                                                                <i class="fas fa-times me-1"></i>Batal
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- MODAL CHANGE PASSWORD -->
                                    <div class="modal fade" id="password<?php echo $admin['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><span class="modal-icon"><i class="fas fa-key"></i></span>Ubah Password</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="POST">
                                                        <input type="hidden" name="id_admin" value="<?php echo $admin['id']; ?>">
                                                        <p class="mb-3"><strong>Admin:</strong> <?php echo htmlspecialchars($admin['username']); ?></p>
                                                        <div class="mb-3">
                                                            <label class="form-label">Password Baru <span style="color:red;">*</span></label>
                                                            <input name="new_password" type="password" class="form-control" placeholder="Minimal 6 karakter" minlength="6" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Konfirmasi Password <span style="color:red;">*</span></label>
                                                            <input name="confirm_password" type="password" class="form-control" placeholder="Ketik ulang password" minlength="6" required>
                                                        </div>
                                                        <div class="alert alert-warning mb-3">
                                                            <i class="fas fa-info-circle me-2"></i>
                                                            Admin harus login dengan password baru berikutnya.
                                                        </div>
                                                        <div class="d-flex gap-2 justify-content-end">
                                                            <button type="submit" name="changePassword" class="btn btn-primary btn-sm">
                                                                <i class="fas fa-check me-1"></i>Ubah
                                                            </button>
                                                            <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                                                                <i class="fas fa-times me-1"></i>Batal
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- MODAL DELETE -->
                                    <div class="modal fade" id="delete<?php echo $admin['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><span class="modal-icon"><i class="fas fa-trash"></i></span>Hapus Admin</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-center">
                                                    <form method="POST">
                                                        <input type="hidden" name="id_admin" value="<?php echo $admin['id']; ?>">
                                                        <i class="fas fa-exclamation-triangle delete-warning-icon"></i>
                                                        <h6 style="font-weight:700;">Yakin ingin menghapus admin ini?</h6>
                                                        <p class="text-muted"><strong><?php echo htmlspecialchars($admin['username']); ?></strong></p>
                                                        <div class="alert alert-danger" style="font-size:12.5px;">
                                                            ⚠️ Tindakan ini tidak bisa dibatalkan!
                                                        </div>
                                                        <div class="mt-4 d-flex gap-2 justify-content-center">
                                                            <button type="submit" name="deleteDataAdmin" class="btn btn-danger btn-sm">
                                                                <i class="fas fa-check me-1"></i>Ya, Hapus
                                                            </button>
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                                                                <i class="fas fa-times me-1"></i>Batal
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-close alerts
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    </script>
</body>
</html>