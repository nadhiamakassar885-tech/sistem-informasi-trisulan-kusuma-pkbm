<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle update status pendaftaran
if (isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $id = (int)$_POST['id'];
    $status = sanitize($_POST['status_pendaftaran']);
    $catatan = sanitize($_POST['catatan_admin']);
    
    $stmt = $conn->prepare("UPDATE pendaftaran SET status_pendaftaran=?, catatan_admin=? WHERE id=?");
    $stmt->bind_param("ssi", $status, $catatan, $id);
    
    if ($stmt->execute()) {
        logActivity('Update status pendaftaran');
        header('Location: pendaftaran.php?success=update');
        exit;
    }
}

// Handle edit data pendaftar
if (isset($_POST['action']) && $_POST['action'] == 'edit_data') {
    $id = (int)$_POST['id'];
    $nama = sanitize($_POST['nama_lengkap']);
    $nik = sanitize($_POST['nik']);
    $tempat = sanitize($_POST['tempat_lahir']);
    $tgl = sanitize($_POST['tanggal_lahir']);
    $jk = sanitize($_POST['jenis_kelamin']);
    $agama = sanitize($_POST['agama']);
    $alamat = sanitize($_POST['alamat']);
    $rt = sanitize($_POST['rt']);
    $rw = sanitize($_POST['rw']);
    $desa = sanitize($_POST['desa']);
    $kecamatan = sanitize($_POST['kecamatan']);
    $kota = sanitize($_POST['kota']);
    $prov = sanitize($_POST['provinsi']);
    $nohp = sanitize($_POST['no_hp']);
    $email = sanitize($_POST['email']);
    $pend = sanitize($_POST['pendidikan_terakhir']);
    $tahun = sanitize($_POST['tahun_lulus']);
    $asal = sanitize($_POST['asal_sekolah']);
    $paket = sanitize($_POST['paket_dipilih']);
    
    $stmt = $conn->prepare("UPDATE pendaftaran SET nama_lengkap=?, nik=?, tempat_lahir=?, tanggal_lahir=?, jenis_kelamin=?, agama=?, alamat=?, rt=?, rw=?, desa=?, kecamatan=?, kota=?, provinsi=?, no_hp=?, email=?, pendidikan_terakhir=?, tahun_lulus=?, asal_sekolah=?, paket_dipilih=? WHERE id=?");
    $stmt->bind_param("sssssssssssssssssssi", $nama, $nik, $tempat, $tgl, $jk, $agama, $alamat, $rt, $rw, $desa, $kecamatan, $kota, $prov, $nohp, $email, $pend, $tahun, $asal, $paket, $id);
    
    if ($stmt->execute()) {
        logActivity('Edit data pendaftar: ' . $nama);
        header('Location: pendaftaran.php?success=edit');
        exit;
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Hapus file dokumen yang diupload
    $row = $conn->query("SELECT * FROM pendaftaran WHERE id = $id")->fetch_assoc();
    if ($row) {
        $upload_dir = '../uploads/pendaftaran/';
        if (!empty($row['ijazah_file']) && file_exists($upload_dir . $row['ijazah_file'])) {
            unlink($upload_dir . $row['ijazah_file']);
        }
        if (!empty($row['kk_file']) && file_exists($upload_dir . $row['kk_file'])) {
            unlink($upload_dir . $row['kk_file']);
        }
        if (!empty($row['akta_file']) && file_exists($upload_dir . $row['akta_file'])) {
            unlink($upload_dir . $row['akta_file']);
        }
        if (!empty($row['foto_file']) && file_exists($upload_dir . $row['foto_file'])) {
            unlink($upload_dir . $row['foto_file']);
        }
    }
    
    $conn->query("DELETE FROM pendaftaran WHERE id = $id");
    logActivity('Menghapus data pendaftar');
    header('Location: pendaftaran.php?success=delete');
    exit;
}

// Filter
 $filter_status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
 $filter_paket = isset($_GET['paket']) ? sanitize($_GET['paket']) : '';
 $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

 $where = [];
if ($filter_status) $where[] = "status_pendaftaran = '$filter_status'";
if ($filter_paket) $where[] = "paket_dipilih = '$filter_paket'";
if ($search) $where[] = "(nama_lengkap LIKE '%$search%' OR nis LIKE '%$search%' OR no_pendaftaran LIKE '%$search%')";

 $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Query TANPA JOIN ke tabel pembayaran
 $pendaftaran = $conn->query("
    SELECT * 
    FROM pendaftaran 
    $where_sql
    ORDER BY tanggal_daftar DESC
");

// Statistics TANPA pembayaran
 $stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM pendaftaran")->fetch_assoc()['count'],
    'pending' => $conn->query("SELECT COUNT(*) as count FROM pendaftaran WHERE status_pendaftaran='pending'")->fetch_assoc()['count'],
    'diterima' => $conn->query("SELECT COUNT(*) as count FROM pendaftaran WHERE status_pendaftaran='diterima'")->fetch_assoc()['count'],
    'ditolak' => $conn->query("SELECT COUNT(*) as count FROM pendaftaran WHERE status_pendaftaran='ditolak'")->fetch_assoc()['count']
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pendaftaran - Admin PKBM</title>
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
        
        .date-info {
            font-size: 12.5px;
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
        
        /* ============ ALERT ============ */
        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 13px;
            background: linear-gradient(135deg, #e8f8ee 0%, #d4f5e0 100%);
            color: #155724;
            border: 1px solid rgba(39, 174, 96, 0.25);
            border-left: 4px solid var(--success-color);
            font-size: 13.5px;
            font-weight: 500;
            animation: slideDown 0.35s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert i { font-size: 19px; }
        
        /* ============ STATS ============ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
        
        .stat-total::before { background: linear-gradient(90deg, var(--primary-color), #005AA7); }
        .stat-pending::before { background: linear-gradient(90deg, var(--warning-color), #f7b731); }
        .stat-approved::before { background: linear-gradient(90deg, var(--success-color), #2ecc71); }
        .stat-rejected::before { background: linear-gradient(90deg, var(--danger-color), #ff6b6b); }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-card .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .stat-card .icon {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            transition: var(--transition);
        }
        
        .stat-total .icon { background: var(--primary-light); color: var(--primary-color); }
        .stat-pending .icon { background: #FEF5E7; color: var(--warning-color); }
        .stat-approved .icon { background: #EAF9F0; color: var(--success-color); }
        .stat-rejected .icon { background: #FDEDEB; color: var(--danger-color); }
        
        .stat-card:hover .icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .stat-card .arrow {
            font-size: 12px;
            color: var(--text-light);
            opacity: 0;
            transition: var(--transition);
            transform: translateX(-6px);
        }
        
        .stat-card:hover .arrow {
            opacity: 1;
            transform: translateX(0);
        }
        
        .stat-card .number {
            font-size: 34px;
            font-weight: 800;
            margin-bottom: 4px;
            color: var(--primary-dark);
            line-height: 1.1;
            letter-spacing: -0.5px;
        }
        
        .stat-card .label {
            color: var(--text-light);
            font-size: 13px;
            font-weight: 500;
        }
        
        /* ============ FILTER BAR ============ */
        .filter-bar {
            background: var(--white);
            padding: 20px 22px;
            border-radius: var(--radius-lg);
            margin-bottom: 22px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }
        
        .filter-bar .search-wrapper {
            position: relative;
            flex: 1;
            min-width: 200px;
        }
        
        .filter-bar .search-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa5b5;
            font-size: 13px;
            pointer-events: none;
        }
        
        .filter-bar select,
        .filter-bar input {
            padding: 11px 15px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-family: inherit;
            transition: var(--transition);
            background: var(--bg-light);
            color: var(--text-dark);
        }
        
        .filter-bar input {
            width: 100%;
            padding-left: 40px;
        }
        
        .filter-bar select {
            min-width: 150px;
            cursor: pointer;
        }
        
        .filter-bar select:focus,
        .filter-bar input:focus {
            outline: none;
            border-color: var(--secondary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(0,206,209,0.12);
        }
        
        .filter-bar button {
            padding: 11px 22px;
            background: linear-gradient(135deg, var(--primary-color), #005AA7);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(0,61,130,0.25);
        }
        
        .filter-bar button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0,61,130,0.35);
        }
        
        /* ============ TABLE ============ */
        .content-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .table-wrapper { overflow-x: auto; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        
        table thead {
            background: linear-gradient(135deg, var(--primary-deep) 0%, var(--primary-color) 60%, #005AA7 100%);
        }
        
        table th {
            padding: 16px 14px;
            text-align: left;
            font-weight: 600;
            font-size: 11.5px;
            color: rgba(255,255,255,0.95);
            text-transform: uppercase;
            letter-spacing: 1px;
            white-space: nowrap;
        }
        
        table th i {
            color: var(--secondary-color);
            margin-right: 6px;
        }
        
        table tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: var(--transition);
        }
        
        table tbody tr:last-child { border-bottom: none; }
        
        table tbody tr:hover {
            background: rgba(0,206,209,0.045);
        }
        
        table td {
            padding: 14px;
            font-size: 13px;
            color: var(--text-light);
        }
        
        table td strong {
            color: var(--primary-color);
            font-weight: 700;
        }
        
        .badge {
            padding: 5px 13px;
            border-radius: 50px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            border: 1px solid transparent;
        }
        
        .badge-pending { background: #FFF6DC; color: #9c7400; border-color: #f3dd9a; }
        .badge-diterima { background: #EAF9F0; color: #1d8a4b; border-color: #b8e8cb; }
        .badge-ditolak { background: #FDEDEB; color: #c0392b; border-color: #f5c6c0; }
        .badge-diproses { background: #E4F4F7; color: #14697c; border-color: #b3e2ec; }
        .badge-verified { background: #E9F3FD; color: #1a5fa8; border-color: #bcdcf8; }
        .badge-approved { background: #EAF9F0; color: #1d8a4b; border-color: #b8e8cb; }
        .badge-rejected { background: #FDEDEB; color: #c0392b; border-color: #f5c6c0; }
        .badge-paket { background: var(--primary-light); color: var(--primary-color); border-color: #c9d9ef; }
        
        .btn {
            padding: 8px 13px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 11.5px;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            text-decoration: none;
            font-family: inherit;
        }
        
        .btn-primary { background: linear-gradient(135deg, var(--primary-color), #005AA7); color: white; }
        .btn-success { background: linear-gradient(135deg, var(--success-color), #2ecc71); color: white; }
        .btn-warning { background: linear-gradient(135deg, var(--warning-color), #f7b731); color: white; }
        .btn-danger { background: linear-gradient(135deg, var(--danger-color), #ff6b6b); color: white; }
        .btn-info { background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark)); color: white; }
        
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 14px rgba(0,0,0,0.2);
        }
        
        .action-btns {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
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
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal.active { 
            display: flex;
            animation: fadeIn 0.25s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background: var(--white);
            padding: 36px;
            border-radius: var(--radius-lg);
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 80px rgba(0, 10, 40, 0.4);
            animation: modalUp 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        @keyframes modalUp {
            from { opacity: 0; transform: translateY(30px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .modal-content h3 {
            margin-bottom: 26px;
            color: var(--primary-color);
            font-size: 20px;
            font-weight: 700;
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
        
        .form-group { margin-bottom: 20px; }
        
        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 13px;
            color: var(--text-dark);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 13.5px;
            font-family: inherit;
            transition: var(--transition);
            background: var(--bg-light);
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(0,206,209,0.12);
        }
        
        .form-group textarea {
            min-height: 85px;
            resize: vertical;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            padding: 14px 16px;
            background: var(--bg-light);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            border-left: 3px solid var(--secondary-color);
            transition: var(--transition);
        }
        
        .detail-item:hover {
            border-left-color: var(--accent-color);
            transform: translateX(3px);
        }
        
        .detail-label {
            font-size: 11px;
            color: var(--text-light);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 600;
        }
        
        .detail-value {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .dokumen-section {
            margin-top: 26px;
            padding: 24px;
            background: linear-gradient(135deg, #f0f8ff 0%, #eaf5fc 100%);
            border-radius: var(--radius-md);
            border: 1px solid #cfe5f5;
            border-left: 4px solid var(--secondary-color);
        }
        
        .dokumen-section h4 {
            margin-bottom: 18px;
            color: var(--primary-color);
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dokumen-section h4 i { color: var(--secondary-dark); }
        
        .dokumen-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-top: 16px;
        }
        
        .dokumen-item {
            background: white;
            padding: 16px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        
        .dokumen-item:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        
        .dokumen-item h5 {
            color: var(--primary-color);
            margin-bottom: 12px;
            font-size: 13.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .dokumen-preview {
            width: 100%;
            height: 190px;
            background: var(--bg-light);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            overflow: hidden;
            border: 1px dashed var(--border-color);
        }
        
        .dokumen-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .dokumen-preview i {
            font-size: 46px;
            color: #c3ccd8;
        }
        
        .dokumen-actions {
            display: flex;
            gap: 8px;
        }
        
        /* ============ RESPONSIVE ============ */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 18px; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 14px; }
            .detail-grid { grid-template-columns: 1fr; }
            .dokumen-grid { grid-template-columns: 1fr; }
            .top-bar { padding: 20px; }
            .top-bar h1 { font-size: 18px; }
            .modal-content { padding: 26px 20px; }
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
                <a href="dashboard.php" class="menu-item"><i class="fas fa-chart-line"></i><span>Dashboard</span></a>
                <a href="profil.php" class="menu-item"><i class="fas fa-building"></i><span>Profil PKBM</span></a>
                <a href="program.php" class="menu-item"><i class="fas fa-book-open"></i><span>Program Paket</span></a>
                <a href="pendaftaran.php" class="menu-item active"><i class="fas fa-users"></i><span>Data Pendaftar</span></a>
                <a href="informasi.php" class="menu-item"><i class="fas fa-bullhorn"></i><span>Informasi</span></a>
                <a href="struktur.php" class="menu-item"><i class="fas fa-sitemap"></i><span>Struktur Organisasi</span></a>
                <a href="dokumen.php" class="menu-item"><i class="fas fa-folder-open"></i><span>Dokumen</span></a>
                <a href="dokumentasi.php" class="menu-item"><i class="fas fa-images"></i><span>Dokumentasi</span></a>
                <a href="kontak.php" class="menu-item"><i class="fas fa-phone-alt"></i><span>Kontak & Lokasi</span></a>
                <a href="kelola.admin.php" class="menu-item"><i class="fas fa-user-shield"></i><span>Kelola Admin</span></a>
                <a href="logout.php" class="menu-item menu-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <h1>
                    <span class="icon-box"><i class="fas fa-users"></i></span>
                    <span class="title-wrap">
                        Kelola Data Pendaftar & Dokumen
                        <small>Verifikasi pendaftar dan kelola dokumen persyaratan</small>
                    </span>
                </h1>
                <div class="date-info">
                    <i class="fas fa-users"></i> <?php echo $stats['total']; ?> total pendaftar
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert">
                <i class="fas fa-check-circle"></i>
                <span>
                    <?php 
                        if ($_GET['success'] == 'update') echo 'Status pendaftaran berhasil diupdate!';
                        if ($_GET['success'] == 'edit') echo 'Data pendaftar berhasil diupdate!';
                        if ($_GET['success'] == 'delete') echo 'Data pendaftar berhasil dihapus!';
                    ?>
                </span>
            </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card stat-total">
                    <div class="stat-top">
                        <div class="icon"><i class="fas fa-users"></i></div>
                        <i class="fas fa-arrow-right arrow"></i>
                    </div>
                    <div class="number"><?php echo $stats['total']; ?></div>
                    <div class="label">Total Pendaftar</div>
                </div>
                <div class="stat-card stat-pending">
                    <div class="stat-top">
                        <div class="icon"><i class="fas fa-clock"></i></div>
                        <i class="fas fa-arrow-right arrow"></i>
                    </div>
                    <div class="number"><?php echo $stats['pending']; ?></div>
                    <div class="label">Pending</div>
                </div>
                <div class="stat-card stat-approved">
                    <div class="stat-top">
                        <div class="icon"><i class="fas fa-check-circle"></i></div>
                        <i class="fas fa-arrow-right arrow"></i>
                    </div>
                    <div class="number"><?php echo $stats['diterima']; ?></div>
                    <div class="label">Diterima</div>
                </div>
                <div class="stat-card stat-rejected">
                    <div class="stat-top">
                        <div class="icon"><i class="fas fa-times-circle"></i></div>
                        <i class="fas fa-arrow-right arrow"></i>
                    </div>
                    <div class="number"><?php echo $stats['ditolak']; ?></div>
                    <div class="label">Ditolak</div>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <form method="GET" class="filter-bar">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Cari nama/NIS..." value="<?php echo $search; ?>">
                </div>
                <select name="status">
                    <option value="">Semua Status</option>
                    <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="diproses" <?php echo $filter_status == 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                    <option value="verified" <?php echo $filter_status == 'verified' ? 'selected' : ''; ?>>Verified</option>
                    <option value="approved" <?php echo $filter_status == 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="diterima" <?php echo $filter_status == 'diterima' ? 'selected' : ''; ?>>Diterima</option>
                    <option value="ditolak" <?php echo $filter_status == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                </select>
                <select name="paket">
                    <option value="">Semua Paket</option>
                    <option value="A" <?php echo $filter_paket == 'A' ? 'selected' : ''; ?>>Paket A</option>
                    <option value="B" <?php echo $filter_paket == 'B' ? 'selected' : ''; ?>>Paket B</option>
                    <option value="C" <?php echo $filter_paket == 'C' ? 'selected' : ''; ?>>Paket C</option>
                </select>
                <button type="submit"><i class="fas fa-filter"></i> Filter</button>
                <a href="pendaftaran.php" class="btn btn-warning"><i class="fas fa-sync"></i> Reset</a>
            </form>
            
            <!-- Table -->
            <div class="content-card">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-id-card"></i>NIS</th>
                                <th><i class="fas fa-hashtag"></i>No. Pendaftaran</th>
                                <th><i class="fas fa-user"></i>Nama Lengkap</th>
                                <th><i class="fas fa-book"></i>Paket</th>
                                <th><i class="fas fa-phone"></i>No. HP</th>
                                <th><i class="fas fa-info-circle"></i>Status</th>
                                <th><i class="fas fa-calendar"></i>Tanggal Daftar</th>
                                <th><i class="fas fa-cogs"></i>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pendaftaran->num_rows > 0): ?>
                                <?php while ($row = $pendaftaran->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo $row['nis']; ?></strong></td>
                                    <td><?php echo $row['no_pendaftaran']; ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                    <td><span class="badge badge-paket">Paket <?php echo $row['paket_dipilih']; ?></span></td>
                                    <td><?php echo $row['no_hp']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $row['status_pendaftaran']; ?>">
                                            <?php echo ucfirst($row['status_pendaftaran']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal_daftar'])); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn btn-info" onclick='viewDetail(<?php echo json_encode($row); ?>)'>
                                                <i class="fas fa-eye"></i> Detail
                                            </button>
                                            <button class="btn btn-success" onclick='viewDokumen(<?php echo json_encode($row); ?>)'>
                                                <i class="fas fa-file-alt"></i> Dokumen
                                            </button>
                                            <button class="btn btn-primary" onclick='updateStatus(<?php echo json_encode($row); ?>)'>
                                                <i class="fas fa-cog"></i> Status
                                            </button>
                                            <a href="../download_formulir.php?nis=<?php echo $row['nis']; ?>" class="btn btn-warning" target="_blank">
                                                <i class="fas fa-print"></i> Kartu
                                            </a>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Yakin hapus data ini? File dokumen juga akan terhapus!')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 50px;">
                                        <i class="fas fa-inbox" style="font-size: 52px; color: #d3dae4; margin-bottom: 14px; display: block;"></i>
                                        <span style="color: var(--text-light); font-weight: 500;">Tidak ada data pendaftar</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal Detail -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <h3><span class="modal-icon"><i class="fas fa-user"></i></span> Detail Pendaftar</h3>
            <div id="detailContent"></div>
            <button class="btn btn-danger" onclick="closeModal('detailModal')">
                <i class="fas fa-times"></i> Tutup
            </button>
        </div>
    </div>
    
    <!-- Modal Dokumen -->
    <div class="modal" id="dokumenModal">
        <div class="modal-content">
            <h3><span class="modal-icon"><i class="fas fa-folder-open"></i></span> Dokumen Pendaftaran</h3>
            <div id="dokumenContent"></div>
            <button class="btn btn-danger" onclick="closeModal('dokumenModal')">
                <i class="fas fa-times"></i> Tutup
            </button>
        </div>
    </div>
    
    <!-- Modal Update Status -->
    <div class="modal" id="statusModal">
        <div class="modal-content">
            <h3><span class="modal-icon"><i class="fas fa-edit"></i></span> Update Status Pendaftaran</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="status_id">
                
                <div class="form-group">
                    <label><i class="fas fa-user" style="color:var(--secondary-dark);"></i> Nama Pendaftar</label>
                    <input type="text" id="status_nama" readonly>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-info-circle" style="color:var(--secondary-dark);"></i> Status Pendaftaran</label>
                    <select name="status_pendaftaran" required>
                        <option value="pending">Pending</option>
                        <option value="diproses">Diproses</option>
                        <option value="verified">Verified</option>
                        <option value="approved">Approved</option>
                        <option value="diterima">Diterima</option>
                        <option value="ditolak">Ditolak</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-comment" style="color:var(--secondary-dark);"></i> Catatan Admin</label>
                    <textarea name="catatan_admin" placeholder="Catatan untuk pendaftar"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success" style="padding: 11px 22px; font-size: 13px;">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                    <button type="button" class="btn btn-danger" style="padding: 11px 22px; font-size: 13px;" onclick="closeModal('statusModal')">
                        <i class="fas fa-times"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function viewDetail(data) {
            const content = `
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">NIS</div>
                        <div class="detail-value">${data.nis}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">No. Pendaftaran</div>
                        <div class="detail-value">${data.no_pendaftaran}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Nama Lengkap</div>
                        <div class="detail-value">${data.nama_lengkap}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">NIK</div>
                        <div class="detail-value">${data.nik}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">No. KK</div>
                        <div class="detail-value">${data.no_kk || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Tempat, Tanggal Lahir</div>
                        <div class="detail-value">${data.tempat_lahir}, ${data.tanggal_lahir}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Jenis Kelamin</div>
                        <div class="detail-value">${data.jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Agama</div>
                        <div class="detail-value">${data.agama}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">No. HP</div>
                        <div class="detail-value">${data.no_hp}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value">${data.email || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Nama Ayah</div>
                        <div class="detail-value">${data.nama_ayah || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Nama Ibu</div>
                        <div class="detail-value">${data.nama_ibu || '-'}</div>
                    </div>
                </div>
                <div class="detail-item" style="margin-top: 15px;">
                    <div class="detail-label">Alamat Lengkap</div>
                    <div class="detail-value">${data.alamat}, RT ${data.rt}/RW ${data.rw}, ${data.desa}, ${data.kecamatan}, ${data.kota}, ${data.provinsi}</div>
                </div>
                <div class="detail-grid" style="margin-top: 15px;">
                    <div class="detail-item">
                        <div class="detail-label">Pendidikan Terakhir</div>
                        <div class="detail-value">${data.pendidikan_terakhir}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Program Dipilih</div>
                        <div class="detail-value">Paket ${data.paket_dipilih}</div>
                    </div>
                </div>
            `;
            document.getElementById('detailContent').innerHTML = content;
            document.getElementById('detailModal').classList.add('active');
        }
        
        function viewDokumen(data) {
            const uploadPath = '../uploads/pendaftaran/';
            
            function renderDokumen(title, filename) {
                if (!filename) {
                    return `
                        <div class="dokumen-item">
                            <h5><i class="fas fa-file" style="color:#c3ccd8;"></i> ${title}</h5>
                            <div class="dokumen-preview">
                                <i class="fas fa-file-excel"></i>
                            </div>
                            <p style="text-align: center; color: #9aa5b5; font-size: 12px;">Tidak ada file</p>
                        </div>
                    `;
                }
                
                const ext = filename.split('.').pop().toLowerCase();
                const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(ext);
                
                return `
                    <div class="dokumen-item">
                        <h5><i class="fas fa-file" style="color:var(--secondary-dark);"></i> ${title}</h5>
                        <div class="dokumen-preview">
                            ${isImage ? 
                                `<img src="${uploadPath}${filename}" alt="${title}">` :
                                `<i class="fas fa-file-pdf" style="color:#e74c3c;"></i>`
                            }
                        </div>
                        <div class="dokumen-actions">
                            <a href="${uploadPath}${filename}" target="_blank" class="btn btn-info">
                                <i class="fas fa-eye"></i> Lihat
                            </a>
                            <a href="${uploadPath}${filename}" download class="btn btn-success">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    </div>
                `;
            }
            
            const content = `
                <div class="detail-grid" style="margin-bottom: 20px;">
                    <div class="detail-item">
                        <div class="detail-label">NIS</div>
                        <div class="detail-value">${data.nis}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Nama Lengkap</div>
                        <div class="detail-value">${data.nama_lengkap}</div>
                    </div>
                </div>
                
                <div class="dokumen-section">
                    <h4><i class="fas fa-folder-open"></i> Dokumen yang Diupload</h4>
                    <div class="dokumen-grid">
                        ${renderDokumen('Ijazah Terakhir', data.ijazah_file)}
                        ${renderDokumen('Kartu Keluarga', data.kk_file)}
                        ${renderDokumen('Akta Kelahiran', data.akta_file)}
                        ${renderDokumen('Pas Foto 3x4', data.foto_file)}
                    </div>
                </div>
            `;
            
            document.getElementById('dokumenContent').innerHTML = content;
            document.getElementById('dokumenModal').classList.add('active');
        }
        
        function updateStatus(data) {
            document.getElementById('status_id').value = data.id;
            document.getElementById('status_nama').value = data.nama_lengkap;
            document.querySelector('select[name="status_pendaftaran"]').value = data.status_pendaftaran;
            document.getElementById('statusModal').classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active'); 
        }
        
        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal(this.id);
                }
            });
        });
    </script>
</body>
</html>