<?php
require_once '../config.php';

// Redirect ke dashboard jika user sudah login
redirectIfLoggedIn();

 $error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        // Query ke database
        $stmt = $conn->prepare("SELECT id, nama_lengkap, username, email, password, role FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();
            
            // Verifikasi password
            if (password_verify($password, $admin['password'])) {
                // Login berhasil
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['nama_lengkap'];
                $_SESSION['admin_role'] = $admin['role'];
                
                // Update last login
                $update_stmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $admin['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Log aktivitas
                logActivity('Login ke sistem');
                
                // ✅ REDIRECT KE DASHBOARD
                header('Location: ' . ADMIN_URL . 'dashboard.php');
                exit;
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Username tidak ditemukan!';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin PKBM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            height: 100%;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #001A3D 0%, #00234F 35%, #003D82 65%, #001A3D 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* ====== AURORA BACKGROUND ====== */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 206, 209, 0.08) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: moveBackground 20s linear infinite;
            z-index: 0;
            pointer-events: none;
        }

        @keyframes moveBackground {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        /* ====== FLOATING ORBS ====== */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(70px);
            z-index: 0;
            pointer-events: none;
        }

        .orb-1 {
            width: 420px;
            height: 420px;
            background: rgba(0, 206, 209, 0.22);
            top: -120px;
            right: -80px;
            animation: orbFloat1 14s ease-in-out infinite;
        }

        .orb-2 {
            width: 340px;
            height: 340px;
            background: rgba(255, 215, 0, 0.14);
            bottom: -100px;
            left: -80px;
            animation: orbFloat2 17s ease-in-out infinite;
        }

        .orb-3 {
            width: 200px;
            height: 200px;
            background: rgba(0, 90, 167, 0.35);
            top: 55%;
            right: 22%;
            animation: orbFloat1 11s ease-in-out infinite reverse;
        }

        @keyframes orbFloat1 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(40px, 50px) scale(1.08); }
            66% { transform: translate(-30px, 25px) scale(0.95); }
        }

        @keyframes orbFloat2 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(50px, -40px) scale(1.1); }
        }

        /* ====== WRAPPER DENGAN BORDER GRADASI ANIMASI ====== */
        .login-wrapper {
            position: relative;
            display: flex;
            width: 100%;
            max-width: 1080px;
            border-radius: 24px;
            animation: slideUp 0.7s cubic-bezier(0.25, 0.8, 0.25, 1) both;
            z-index: 10;
        }

        /* Border gradasi berputar */
        .login-wrapper::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 26px;
            background: conic-gradient(
                from var(--angle, 0deg),
                #00CED1,
                #FFD700,
                #005AA7,
                #00CED1
            );
            animation: rotateBorder 6s linear infinite;
            z-index: -1;
        }

        @property --angle {
            syntax: '<angle>';
            initial-value: 0deg;
            inherits: false;
        }

        @keyframes rotateBorder {
            to { --angle: 360deg; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .login-card {
            display: flex;
            width: 100%;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0, 10, 40, 0.5),
                        0 0 80px rgba(0, 206, 209, 0.15);
        }

        /* ====== LEFT SIDE - BRAND ====== */
        .login-brand {
            flex: 1;
            background: linear-gradient(150deg, #001A3D 0%, #002D61 45%, #003D82 80%, #005AA7 100%);
            color: white;
            padding: 55px 42px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-brand::before {
            content: '';
            position: absolute;
            top: -60%;
            right: -40%;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(0, 206, 209, 0.25) 0%, transparent 70%);
            border-radius: 50%;
        }

        .login-brand::after {
            content: '';
            position: absolute;
            bottom: -40%;
            left: -30%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 215, 0, 0.14) 0%, transparent 70%);
            border-radius: 50%;
        }

        .login-brand-content {
            position: relative;
            z-index: 2;
            width: 100%;
        }

        /* Logo dengan ring berputar */
        .login-brand-icon {
            position: relative;
            width: 104px;
            height: 104px;
            margin: 0 auto 28px;
        }

        .brand-ring {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 2px dashed rgba(255, 215, 0, 0.5);
            animation: spinRing 12s linear infinite;
        }

        @keyframes spinRing {
            to { transform: rotate(360deg); }
        }

        .brand-icon-inner {
            position: absolute;
            inset: 9px;
            background: linear-gradient(135deg, rgba(0,206,209,0.35), rgba(255,255,255,0.12));
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 40px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 30px rgba(0, 206, 209, 0.35);
            animation: bounceIcon 3s ease-in-out infinite;
        }

        @keyframes bounceIcon {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .login-brand h1 {
            font-size: 34px;
            font-weight: 800;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #FFFFFF 30%, #FFE566 60%, #FFD700 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 2px 8px rgba(255,215,0,0.25));
        }

        .login-brand p {
            font-size: 15px;
            margin-bottom: 6px;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .login-brand small {
            font-size: 12px;
            opacity: 0.75;
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .brand-divider {
            width: 90px;
            height: 3px;
            margin: 26px auto;
            border-radius: 10px;
            background: linear-gradient(90deg, transparent, #FFD700, #00CED1, transparent);
        }

        .login-brand-features {
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 13px 16px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            animation: slideInLeft 0.6s ease-out forwards;
        }

        .feature-item:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateX(6px);
            border-color: rgba(255, 215, 0, 0.35);
        }

        .feature-item:nth-child(1) { animation-delay: 0.25s; opacity: 0; }
        .feature-item:nth-child(2) { animation-delay: 0.45s; opacity: 0; }
        .feature-item:nth-child(3) { animation-delay: 0.65s; opacity: 0; }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-24px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .feature-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, rgba(0,206,209,0.4), rgba(255,255,255,0.1));
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 17px;
            flex-shrink: 0;
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #FFD700;
        }

        .feature-text {
            font-size: 13px;
            font-weight: 500;
        }

        /* ====== RIGHT SIDE - FORM ====== */
        .login-form-container {
            flex: 1;
            padding: 55px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .login-form-header {
            margin-bottom: 34px;
        }

        .login-form-header .welcome-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, rgba(0,61,130,0.07), rgba(0,206,209,0.1));
            border: 1px solid rgba(0, 206, 209, 0.35);
            color: #003D82;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .login-form-header .welcome-badge i {
            color: #00B8D4;
        }

        .login-form-header h2 {
            color: #002D61;
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .login-form-header p {
            color: #7a8494;
            font-size: 14px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #333;
            font-weight: 600;
            font-size: 13.5px;
            margin-bottom: 9px;
        }

        .form-group label i {
            color: #00B8D4;
            width: 16px;
            text-align: center;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper > i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa5b5;
            font-size: 15px;
            transition: color 0.3s ease;
            pointer-events: none;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px 14px 46px;
            border: 2px solid #e4e9f0;
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        /* Input password butuh ruang untuk tombol mata */
        .form-group input.has-toggle {
            padding-right: 48px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #003D82;
            background: white;
            box-shadow: 0 0 0 4px rgba(0, 61, 130, 0.09);
            transform: translateY(-2px);
        }

        .form-group input:focus ~ i,
        .input-wrapper input:focus + i {
            color: #003D82;
        }

        .form-group input::placeholder {
            color: #a5aebc;
        }

        /* Toggle password */
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9aa5b5;
            cursor: pointer;
            font-size: 15px;
            padding: 6px;
            transition: color 0.3s ease;
            border-radius: 6px;
        }

        .toggle-password:hover {
            color: #003D82;
        }

        /* Caps lock warning */
        .capslock-warning {
            display: none;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            font-size: 12px;
            color: #b8860b;
            background: #FFF9E6;
            border: 1px solid #FFE9A0;
            padding: 7px 12px;
            border-radius: 8px;
            animation: slideDown 0.25s ease;
        }

        .capslock-warning.show {
            display: flex;
        }

        .alert {
            padding: 15px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 13.5px;
            animation: slideDown 0.35s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: linear-gradient(135deg, #fff1f0 0%, #ffe3e1 100%);
            color: #c0392b;
            border: 1px solid rgba(192, 57, 43, 0.2);
            border-left: 4px solid #e74c3c;
            font-weight: 500;
        }

        .alert-error i {
            color: #e74c3c;
            font-size: 17px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 26px;
            font-size: 13px;
        }

        .remember-forgot label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #555;
            cursor: pointer;
            font-weight: 500;
        }

        .remember-forgot input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #003D82;
            cursor: pointer;
        }

        .remember-forgot a {
            color: #003D82;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .remember-forgot a:hover {
            color: #00CED1;
        }

        .btn-login {
            width: 100%;
            padding: 15px 24px;
            background: linear-gradient(135deg, #003D82 0%, #005AA7 55%, #00CED1 130%);
            background-size: 150% 150%;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Poppins', sans-serif;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 61, 130, 0.3);
            letter-spacing: 0.3px;
        }

        /* Shine sweep */
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -80%;
            width: 50%;
            height: 100%;
            background: linear-gradient(100deg, transparent, rgba(255, 255, 255, 0.35), transparent);
            transform: skewX(-25deg);
            transition: left 0.7s ease;
            z-index: 1;
        }

        .btn-login:hover::before {
            left: 130%;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            background-position: 100% 100%;
            box-shadow: 0 16px 40px rgba(0, 61, 130, 0.4);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .btn-login span {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-footer {
            text-align: center;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #eef1f6;
            font-size: 12px;
            color: #9aa5b5;
        }

        .login-footer a {
            color: #003D82;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-footer a:hover {
            color: #00CED1;
        }

        /* Loading animation */
        .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            position: relative;
            z-index: 2;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .btn-login.loading-state .loading {
            display: inline-block;
        }

        .btn-login.loading-state span {
            display: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-wrapper::before {
                border-radius: 22px;
            }
            
            .login-card {
                flex-direction: column;
                border-radius: 22px;
            }

            .login-brand {
                padding: 38px 28px;
            }

            .login-brand-icon {
                width: 84px;
                height: 84px;
                margin-bottom: 20px;
            }

            .brand-icon-inner {
                font-size: 32px;
            }

            .login-brand h1 {
                font-size: 26px;
            }

            .brand-divider {
                margin: 18px auto;
            }

            .login-brand-features {
                display: none;
            }

            .login-form-container {
                padding: 38px 28px;
            }

            .login-form-header h2 {
                font-size: 23px;
            }

            .orb-1 { width: 260px; height: 260px; }
            .orb-2 { width: 200px; height: 200px; }
            .orb-3 { display: none; }
        }

        @media (max-width: 480px) {
            .login-form-container {
                padding: 30px 20px;
            }

            .login-form-header h2 {
                font-size: 21px;
            }

            .btn-login {
                padding: 13px 20px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Floating orbs background -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    
    <div class="login-wrapper">
        <div class="login-card">
            <!-- Left Side - Brand -->
            <div class="login-brand">
                <div class="login-brand-content">
                    <div class="login-brand-icon">
                        <div class="brand-ring"></div>
                        <div class="brand-icon-inner">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                    
                    <h1>Admin PKBM</h1>
                    <p>Sistem Manajemen Terpadu</p>
                    <small>Pusat Kegiatan Belajar Masyarakat</small>

                    <div class="brand-divider"></div>

                    <div class="login-brand-features">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="feature-text">Sistem Keamanan Terjamin</div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="feature-text">Dashboard Analitik Real-time</div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="feature-text">Manajemen User Profesional</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Form -->
            <div class="login-form-container">
                <div class="login-form-header">
                    <div class="welcome-badge">
                        <i class="fas fa-lock"></i> Area Terbatas
                    </div>
                    <h2>Selamat Datang</h2>
                    <p>Masuk ke sistem administrator</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Username</label>
                        <div class="input-wrapper">
                            <input type="text" name="username" placeholder="Masukkan username Anda" required autofocus>
                            <i class="fas fa-id-badge"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="password" id="passwordInput" placeholder="Masukkan password Anda" required>
                            <i class="fas fa-key"></i>
                            <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="Tampilkan password">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        <div class="capslock-warning" id="capslockWarning">
                            <i class="fas fa-exclamation-triangle"></i> Caps Lock aktif!
                        </div>
                    </div>

                    <div class="remember-forgot">
                        <label>
                            <input type="checkbox" name="remember">
                            Ingat saya
                        </label>
                        <a href="#" onclick="alert('Hubungi administrator untuk reset password'); return false;">
                            Lupa password?
                        </a>
                    </div>

                    <button type="submit" class="btn-login">
                        <span>
                            <i class="fas fa-sign-in-alt"></i>
                            Masuk ke Sistem
                        </span>
                        <div class="loading"></div>
                    </button>
                </form>

                <div class="login-footer">
                    <p>© 2026 <a href="#">Admin PKBM</a> - Hak Cipta Dilindungi</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add loading animation on form submit
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.querySelector('.btn-login');
            btn.classList.add('loading-state');
        });
        
        // Toggle password visibility
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('toggleIcon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Caps lock detection
        document.getElementById('passwordInput').addEventListener('keyup', function(e) {
            const warning = document.getElementById('capslockWarning');
            if (e.getModifierState && e.getModifierState('CapsLock')) {
                warning.classList.add('show');
            } else {
                warning.classList.remove('show');
            }
        });
    </script>
</body>
</html>