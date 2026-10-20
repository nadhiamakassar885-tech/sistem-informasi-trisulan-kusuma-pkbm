<?php
require_once '../config.php';

echo "<h2>🔍 Debug: Check Admin User</h2>";

// Check apakah tabel admin_users ada
$tables = $conn->query("SHOW TABLES LIKE 'admin_users'");
if ($tables->num_rows === 0) {
    echo "<p style='color: red;'><strong>❌ Tabel admin_users tidak ada!</strong></p>";
    echo "<p>Database belum diimport. Silakan import db-pkt.sql terlebih dahulu.</p>";
    exit;
}

// Lihat semua admin user di database
echo "<h3>📋 Data Admin Users di Database:</h3>";
$result = $conn->query("SELECT id, username, nama_lengkap, email, role, created_at FROM admin_users");

if ($result->num_rows === 0) {
    echo "<p style='color: orange;'><strong>⚠️ Belum ada admin user di database!</strong></p>";
    echo "<p>Silakan jalankan file <strong>reset-admin.php</strong> untuk menambah admin user.</p>";
} else {
    echo "<table border='1' cellpadding='10' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Username</th><th>Nama Lengkap</th><th>Email</th><th>Role</th><th>Dibuat</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td><strong>" . $row['username'] . "</strong></td>";
        echo "<td>" . $row['nama_lengkap'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Test password verification
    echo "<h3>🔐 Test Password Verification:</h3>";
    $result = $conn->query("SELECT id, username, password FROM admin_users WHERE username = 'admin'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p><strong>Username:</strong> " . $user['username'] . "</p>";
        echo "<p><strong>Password Hash:</strong> " . $user['password'] . "</p>";
        
        if (password_verify('admin123', $user['password'])) {
            echo "<p style='color: green;'><strong>✅ Password 'admin123' COCOK dengan hash!</strong></p>";
            echo "<p>Anda bisa login sekarang dengan:</p>";
            echo "<ul>";
            echo "<li><strong>Username:</strong> admin</li>";
            echo "<li><strong>Password:</strong> admin123</li>";
            echo "</ul>";
        } else {
            echo "<p style='color: red;'><strong>❌ Password 'admin123' TIDAK COCOK!</strong></p>";
            echo "<p>Silakan jalankan file <strong>reset-admin.php</strong> untuk reset password.</p>";
        }
    }
}

echo "<hr>";
echo "<p style='margin-top: 20px;'>";
echo "<a href='check-admin.php' style='color: #667eea; text-decoration: none; margin-right: 20px;'>🔄 Refresh</a>";
echo "<a href='reset-admin.php?reset=1' style='color: #667eea; text-decoration: none; margin-right: 20px;'>⚙️ Reset Admin User</a>";
echo "<a href='login.php' style='color: #667eea; text-decoration: none;'>🔐 Kembali ke Login</a>";
echo "</p>";
?>
