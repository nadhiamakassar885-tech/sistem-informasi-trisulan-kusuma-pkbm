<?php
require_once 'config.php';

// Cek apakah ada parameter reset=1
if (!isset($_GET['reset']) || $_GET['reset'] !== '1') {
    echo "<h2>⚠️ Parameter tidak valid</h2>";
    echo "<p>Akses: <a href='reset-admin.php?reset=1'>reset-admin.php?reset=1</a></p>";
    echo "<p><a href='admin/check-admin.php'>→ Check Admin User</a></p>";
    exit;
}

// Hash password
$password_hash = password_hash('admin123', PASSWORD_BCRYPT);

// Cek apakah admin user sudah ada
$result = $conn->query("SELECT id FROM admin_users WHERE username = 'admin'");

if ($result->num_rows > 0) {
    // Update existing admin user
    $query = "UPDATE admin_users SET password = '$password_hash', nama_lengkap = 'Administrator', email = 'admin@pkbm.local', role = 'superadmin' WHERE username = 'admin'";
} else {
    // Insert new admin user
    $query = "INSERT INTO admin_users (username, password, nama_lengkap, email, role) VALUES ('admin', '$password_hash', 'Administrator', 'admin@pkbm.local', 'superadmin')";
}

if ($conn->query($query)) {
    echo "<h2 style='color: green;'>✅ Admin User Berhasil Direset!</h2>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<p><strong>Username:</strong> <code>admin</code></p>";
    echo "<p><strong>Password:</strong> <code>admin123</code></p>";
    echo "</div>";
    echo "<p>🔗 <a href='admin/login.php' style='color: #667eea; text-decoration: none;'>→ Pergi ke Login</a></p>";
    echo "<p style='color: red; margin-top: 20px;'><strong>⚠️ PENTING:</strong> Hapus atau disable file ini setelah login!</p>";
    echo "<p><code>reset-admin.php</code> - Hapus file ini dari server untuk keamanan.</p>";
} else {
    echo "<h2 style='color: red;'>❌ Gagal Direset</h2>";
    echo "<p><strong>Error:</strong> " . $conn->error . "</p>";
    echo "<p>Database mungkin belum diimport. Silakan:</p>";
    echo "<ol>";
    echo "<li>Buka phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
    echo "<li>Import file <strong>db-pkt.sql</strong></li>";
    echo "<li>Refresh halaman ini</li>";
    echo "</ol>";
}

echo "<hr style='margin-top: 30px;'>";
echo "<p style='font-size: 12px; color: #999;'>";
echo "<a href='admin/check-admin.php'>→ Check Admin User Status</a>";
echo "</p>";
?>
