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
    $conn->query("DELETE FROM pengaturan_pendaftaran WHERE id = $id");
    header('Location: pengaturan_pendaftaran.php?success=delete');
    exit;
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama_periode = sanitize($_POST['nama_periode']);
    $tanggal_buka = sanitize($_POST['tanggal_buka']);
    $tanggal_tutup = sanitize($_POST['tanggal_tutup']);
    $status = sanitize($_POST['status']);
    $kuota_peserta = (int)$_POST['kuota_peserta'];
    $keterangan = sanitize($_POST['keterangan']);
    
    if ($id > 0) {
        // Update
        $stmt = $conn->prepare("UPDATE pengaturan_pendaftaran SET nama_periode=?, tanggal_buka=?, tanggal_tutup=?, status=?, kuota_peserta=?, keterangan=? WHERE id=?");
        $stmt->bind_param("ssssisi", $nama_periode, $tanggal_buka, $tanggal_tutup, $status, $kuota_peserta, $keterangan, $id);
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO pengaturan_pendaftaran (nama_periode, tanggal_buka, tanggal_tutup, status, kuota_peserta, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssis", $nama_periode, $tanggal_buka, $tanggal_tutup, $status, $kuota_peserta, $keterangan);
    }
    
    if ($stmt->execute()) {
        header('Location: pengaturan_pendaftaran.php?success=save');
        exit;
    }
}

$periodes = $conn->query("SELECT * FROM pengaturan_pendaftaran ORDER BY tanggal_buka DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Pendaftaran - Admin PKBM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #003D82;
            --secondary-color: #00CED1;
            --accent-color: #FFD700;
            --text-dark: #1a1a1a;
            --text-light: #4a4a4a;
            --bg-light: #f8f9fa;
            --white: #ffffff;
            --border-color: #e0e0e0;
            --success-bg: #d4edda;
            --success-border: #c3e6cb;
            --success-text: #155724;
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-light);
        }
        
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: var(--primary-color);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 30px 20px;
            background: rgba(0,0,0,0.2);
            text-align: center;
            border-bottom: 2px solid var(--secondary-color);
        }
        
        .sidebar-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .sidebar-header p {
            font-size: 13px;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            padding: 15px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .menu-item i {
            width: 20px;
            text-align: center;
        }
        
        .menu-item:hover,
        .menu-item.active {
            background: rgba(0,206,209,0.1);
            color: var(--secondary-color);
            border-left: 4px solid var(--secondary-color);
        }
        
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 30px;
        }
        
        .top-bar {
            background: var(--white);
            padding: 25px 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }
        
        .top-bar h1 {
            font-size: 26px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .top-bar-right {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { background: #005AA7; transform: translateY(-2px); }
        
        .btn-success { background: var(--secondary-color); color: white; }
        .btn-success:hover { background: #00A8AD; transform: translateY(-2px); }
        
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; transform: translateY(-2px); }
        
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            animation: slideDown 0.3s ease;
            background: var(--success-bg);
            color: var(--success-text);
            border: 1px solid var(--success-border);
            border-left: 4px solid #28a745;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .content-card {
            background: var(--white);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background: var(--bg-light);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            color: var(--primary-color);
            border-bottom: 2px solid var(--border-color);
        }
        
        table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            color: var(--text-light);
        }
        
        table tr:hover {
            background: var(--bg-light);
        }
        
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-aktif { background: var(--success-bg); color: var(--success-text); }
        .badge-nonaktif { background: #f8d7da; color: #721c24; }
        .badge-berlangsung { background: #d1ecf1; color: #0c5460; }
        .badge-akan-datang { background: #fff3cd; color: #856404; }
        .badge-selesai { background: #e2e3e5; color: #383d41; }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: var(--white);
            padding: 40px;
            border-radius: 8px;
            max-width: 700px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-content h3 {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--secondary-color);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0,61,130,0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 2px solid var(--bg-light);
        }
        
        .info-box {
            background: #E8F4F8;
            border-left: 4px solid var(--primary-color);
            padding: 20px 25px;
            border-radius: 8px;
        }
        
        .info-box h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .info-box p {
            color: var(--text-light);
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 20px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> Admin PKBM</h2>
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
                <a href="pengaturan_pendaftaran.php" class="menu-item active">
                    <i class="fas fa-calendar-check"></i>
                    <span>Periode Pendaftaran</span>
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
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <h1>
                    <i class="fas fa-calendar-check"></i>
                    Pengaturan Periode Pendaftaran
                </h1>
                <div class="top-bar-right">
                    <button class="btn btn-success" onclick="showModal()">
                        <i class="fas fa-plus"></i>
                        Tambah Periode
                    </button>
                    <a href="logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="alert">
                <i class="fas fa-check-circle"></i>
                <span>
                    <?php 
                        if ($_GET['success'] == 'save') echo 'Periode pendaftaran berhasil disimpan!';
                        if ($_GET['success'] == 'delete') echo 'Periode pendaftaran berhasil dihapus!';
                    ?>
                </span>
            </div>
            <?php endif; ?>
            
            <div class="content-card">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Periode</th>
                            <th>Tanggal Buka</th>
                            <th>Tanggal Tutup</th>
                            <th>Kuota</th>
                            <th>Status Periode</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $periodes->fetch_assoc()): 
                            $today = date('Y-m-d');
                            $status_periode = '';
                            if ($today < $row['tanggal_buka']) {
                                $status_periode = '<span class="badge badge-akan-datang">Akan Datang</span>';
                            } elseif ($today >= $row['tanggal_buka'] && $today <= $row['tanggal_tutup']) {
                                $status_periode = '<span class="badge badge-berlangsung">Sedang Berlangsung</span>';
                            } else {
                                $status_periode = '<span class="badge badge-selesai">Selesai</span>';
                            }
                        ?>
                        <tr>
                            <td><strong><?php echo $row['nama_periode']; ?></strong></td>
                            <td><?php echo date('d M Y', strtotime($row['tanggal_buka'])); ?></td>
                            <td><?php echo date('d M Y', strtotime($row['tanggal_tutup'])); ?></td>
                            <td><?php echo $row['kuota_peserta']; ?> peserta</td>
                            <td><?php echo $status_periode; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $row['status']; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-primary" onclick='editPeriode(<?php echo json_encode($row); ?>)'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Yakin hapus periode ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="content-card info-box">
                <h3><i class="fas fa-info-circle"></i> Informasi</h3>
                <p><strong>Status Periode:</strong> Menunjukkan apakah pendaftaran belum dibuka, sedang berlangsung, atau sudah selesai berdasarkan tanggal saat ini.</p>
                <p style="margin-top: 10px;"><strong>Status Aktif/Nonaktif:</strong> Hanya periode dengan status "Aktif" yang akan ditampilkan di website pengunjung.</p>
                <p style="margin-top: 10px;"><strong>Kuota:</strong> Batasi jumlah pendaftar (masukkan 0 untuk unlimited).</p>
            </div>
        </main>
    </div>
    
    <!-- Modal Form -->
    <div class="modal" id="formModal">
        <div class="modal-content">
            <h3 id="modalTitle"><i class="fas fa-plus-circle"></i> Tambah Periode Pendaftaran</h3>
            <form method="POST">
                <input type="hidden" name="id" id="periodeId" value="0">
                
                <div class="form-group">
                    <label><i class="fas fa-heading"></i> Nama Periode</label>
                    <input type="text" name="nama_periode" id="namaPeriode" placeholder="Contoh: PPDB 2026/2027" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Tanggal Buka</label>
                        <input type="date" name="tanggal_buka" id="tanggalBuka" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar-times"></i> Tanggal Tutup</label>
                        <input type="date" name="tanggal_tutup" id="tanggalTutup" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-users"></i> Kuota Peserta</label>
                        <input type="number" name="kuota_peserta" id="kuota" placeholder="0 = unlimited" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-toggle-on"></i> Status</label>
                        <select name="status" id="status" required>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Keterangan</label>
                    <textarea name="keterangan" id="keterangan" rows="3" placeholder="Keterangan periode pendaftaran..."></textarea>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn btn-danger" onclick="closeModal()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Tambah Periode Pendaftaran';
            document.getElementById('periodeId').value = '0';
            document.querySelector('form').reset();
            document.getElementById('formModal').classList.add('active');
        }
        
        function editPeriode(data) {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Periode Pendaftaran';
            document.getElementById('periodeId').value = data.id;
            document.getElementById('namaPeriode').value = data.nama_periode;
            document.getElementById('tanggalBuka').value = data.tanggal_buka;
            document.getElementById('tanggalTutup').value = data.tanggal_tutup;
            document.getElementById('kuota').value = data.kuota_peserta;
            document.getElementById('status').value = data.status;
            document.getElementById('keterangan').value = data.keterangan || '';
            document.getElementById('formModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('formModal').classList.remove('active');
        }
        
        document.getElementById('formModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'all 0.3s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    </script>
</body>
</html>