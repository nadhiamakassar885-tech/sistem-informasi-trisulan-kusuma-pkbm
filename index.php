<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'config.php';
    
    if (!$conn) {
        throw new Exception("Database tidak terhubung");
    }
    
    $result = $conn->query("SELECT * FROM profil_pkbm LIMIT 1");
    if (!$result) {
        throw new Exception("Query profil gagal: " . $conn->error);
        
    }
    
    $profil = $result->fetch_assoc();
    
    if (!$profil) {
        $profil = ['nama_pkbm' => 'PKBM', 'visi' => 'Pendidikan Nonformal Berkualitas'];
    }
    
    $result = $conn->query("SELECT * FROM informasi WHERE status = 'published' ORDER BY tanggal_publikasi DESC LIMIT 3");
    if (!$result) {
        throw new Exception("Query informasi gagal: " . $conn->error);
    }
    $informasi = $result;
    
    $result = $conn->query("SELECT * FROM program_paket WHERE status = 'aktif' ORDER BY jenis_paket");
    if (!$result) {
        throw new Exception("Query program gagal: " . $conn->error);
    }
    $programs = $result;
    
} catch (Exception $e) {
    die("<h2>❌ Error Database</h2>" .
        "<p><strong>" . $e->getMessage() . "</strong></p>" .
        "<p><strong>Solusi:</strong></p>" .
        "<ol>" .
        "<li>Buka phpMyAdmin: <a href='http://localhost/phpmyadmin'>http://localhost/phpmyadmin</a></li>" .
        "<li>Buat database baru bernama '<strong>pkbm_website</strong>'</li>" .
        "<li>Import file '<strong>db-pkt.sql</strong>' ke database tersebut</li>" .
        "<li>Refresh halaman ini</li>" .
        "</ol>");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $profil['nama_pkbm'] ?? 'PKBM'; ?> - Sekolah Kesetaraan Resmi & Terakreditasi</title>
    <?php echo FONT_LINK; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #003D82;
            --primary-dark: #00234F;
            --primary-deep: #001A3D;
            --primary-light: #E8F0FA;
            --secondary-color: #00CED1;
            --secondary-dark: #00B8D4;
            --accent-color: #FFD700;
            --accent-light: #FFE566;
            --gold-gradient: linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #FFD700 100%);
            --text-dark: #1a1a2e;
            --text-light: #4a4a4a;
            --bg-light: #f8f9fa;
            --white: #ffffff;
            --border-color: #e0e0e0;
            --shadow-sm: 0 2px 8px rgba(13, 38, 76, 0.06);
            --shadow-md: 0 8px 24px rgba(13, 38, 76, 0.1);
            --shadow-lg: 0 16px 40px rgba(13, 38, 76, 0.14);
            --shadow-premium: 0 20px 60px rgba(0, 35, 79, 0.18);
            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 20px;
            --transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: var(--white);
        }
        
        /* ================= HEADER & NAVIGATION (KONSISTEN) ================= */
        .top-bar {
            background: linear-gradient(90deg, var(--primary-deep), var(--primary-color));
            padding: 9px 0;
            font-size: 12.5px;
            font-family: 'Poppins', 'Segoe UI', Tahoma, sans-serif;
            position: relative;
        }
        
        .top-bar::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--secondary-color), var(--accent-color), var(--secondary-color), transparent);
        }
        
        .top-bar-content {
            max-width: 1320px;
            margin: 0 auto;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--white);
        }
        
        .contact-info {
            display: flex;
            gap: 28px;
        }
        
        .contact-info span {
            display: flex;
            align-items: center;
            gap: 8px;
            opacity: 0.92;
        }
        
        .contact-info i {
            color: var(--secondary-color);
            font-size: 13px;
        }
        
        .social-links {
            display: flex;
            gap: 8px;
        }
        
        .social-links a {
            color: var(--white);
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            font-size: 12px;
            transition: var(--transition);
        }
        
        .social-links a:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        header {
            background: var(--white);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
            border-bottom: 3px solid var(--secondary-color);
            font-family: 'Poppins', 'Segoe UI', Tahoma, sans-serif;
        }
        
        nav {
            max-width: 1320px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 30px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        
        .logo-image {
            width: 58px;
            height: 58px;
            object-fit: contain;
        }
        
        .logo-text h1 {
            font-size: 21px;
            font-weight: 700;
            color: var(--primary-color);
            letter-spacing: 0.3px;
            line-height: 1.2;
        }
        
        .logo-text p {
            font-size: 10.5px;
            color: var(--secondary-dark);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .nav-right {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2px;
        }
        
        .nav-menu a {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            font-size: 13.5px;
            padding: 9px 14px;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            display: block;
            white-space: nowrap;
        }
        
        .nav-menu a:hover {
            background: var(--primary-light);
            color: var(--primary-color);
        }
        
        .nav-menu a.active {
            background: var(--primary-color);
            color: var(--white);
        }
        
        /* Tombol Login Admin */
        .btn-login-admin {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark));
            color: var(--white);
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 13px;
            transition: var(--transition);
            box-shadow: 0 4px 14px rgba(0,206,209,0.35);
            white-space: nowrap;
        }
        
        .btn-login-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,206,209,0.45);
        }
        
        .btn-login-admin i {
            font-size: 15px;
        }
        
        .hamburger {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--primary-color);
            cursor: pointer;
            padding: 8px;
        }
        /* ================= AKHIR HEADER ================= */
        
        /* ======== HERO PREMIUM ======== */
        .hero {
            min-height: 700px;
            background: linear-gradient(135deg, rgba(0, 26, 61, 0.93) 0%, rgba(0, 45, 97, 0.88) 45%, rgba(0, 61, 130, 0.85) 100%),
                        url('admin/gambar/dokm.jpg') center/cover no-repeat;
            background-attachment: fixed;
            color: var(--white);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero.no-image {
            background: linear-gradient(135deg, var(--primary-color) 0%, #0066b3 50%, #005AA7 100%);
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="none"/><circle cx="10" cy="10" r="1" fill="rgba(0,206,209,0.15)"/></svg>');
            opacity: 0.3;
            animation: movePattern 20s linear infinite;
        }
        
        .hero::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 15% 40%, rgba(0, 206, 209, 0.18) 0%, transparent 45%),
                        radial-gradient(circle at 85% 60%, rgba(255, 215, 0, 0.12) 0%, transparent 45%);
            pointer-events: none;
        }
        
        /* Garis aksen emas bawah hero */
        .hero-accent-line {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color), var(--secondary-color));
            z-index: 2;
        }
        
        @keyframes movePattern {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        
        .hero-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 110px 40px;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 60px;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        
        /* Ornament hero kiri */
        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 9px 24px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,215,0,0.35);
            backdrop-filter: blur(12px);
            border-radius: 50px;
            font-family: 'Poppins', sans-serif;
            font-size: 12.5px;
            font-weight: 600;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            margin-bottom: 30px;
            animation: fadeInUp 0.9s ease both;
        }
        
        .hero-eyebrow i {
            color: var(--accent-color);
        }
        
        .hero-eyebrow::before,
        .hero-eyebrow::after {
            content: '◆';
            font-size: 7px;
            color: var(--accent-color);
            opacity: 0.8;
        }
        
        .hero-content h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 52px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 25px;
            text-shadow: 0 3px 15px rgba(0,0,0,0.4);
            overflow: hidden;
            white-space: nowrap;
            animation: marquee 15s linear infinite;
        }
        
        /* TEKS BERJALAN - DIPERTAHANKAN */
        @keyframes marquee {
            0% {
                transform: translateX(100%);
            }
            100% {
                transform: translateX(-100%);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .hero-divider {
            width: 120px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-color), var(--secondary-color), transparent);
            border-radius: 10px;
            margin: 0 0 28px;
            animation: fadeInUp 0.9s ease 0.1s both;
        }
        
        .hero-content p {
            font-size: 19px;
            line-height: 1.8;
            margin-bottom: 40px;
            opacity: 0.96;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            animation: fadeInUp 1s ease 0.2s both;
            max-width: 640px;
        }
        
        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
            margin-top: 50px;
            animation: fadeInUp 1.2s ease 0.35s both;
        }
        
        .stat-item {
            text-align: center;
            padding: 28px 18px;
            background: rgba(255,255,255,0.09);
            border-radius: var(--radius-md);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(255,255,255,0.18);
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .stat-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 20%;
            right: 20%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent-color), transparent);
            opacity: 0.7;
        }
        
        .stat-item:hover {
            background: rgba(255,255,255,0.16);
            transform: translateY(-6px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.35);
            border-color: rgba(255,215,0,0.35);
        }
        
        .stat-number {
            font-family: 'Poppins', sans-serif;
            font-size: 42px;
            font-weight: 800;
            background: linear-gradient(135deg, #FFD700, #FFC700, #FFD700);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            display: block;
            margin-bottom: 8px;
            filter: drop-shadow(0 2px 8px rgba(255,215,0,0.35));
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.95;
            line-height: 1.4;
            font-weight: 500;
        }
        
        /* ======== TOMBOL PREMIUM DENGAN SHEEN ======== */
        .btn-primary, .btn-outline {
            position: relative;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-primary::before, .btn-outline::before {
            content: '';
            position: absolute;
            top: 0;
            left: -80%;
            width: 50%;
            height: 100%;
            background: linear-gradient(100deg, transparent, rgba(255,255,255,0.45), transparent);
            transform: skewX(-25deg);
            transition: left 0.7s ease;
        }
        
        .btn-primary:hover::before, .btn-outline:hover::before {
            left: 130%;
        }
        
        .btn-primary {
            display: inline-block;
            padding: 16px 42px;
            background: var(--gold-gradient);
            color: var(--primary-deep);
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: 15px;
            transition: var(--transition);
            margin-right: 15px;
            box-shadow: 0 6px 22px rgba(255,215,0,0.45);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(255,215,0,0.6);
        }
        
        .btn-outline {
            display: inline-block;
            padding: 16px 42px;
            background: rgba(255,255,255,0.08);
            color: var(--white);
            text-decoration: none;
            border: 2px solid var(--secondary-color);
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 15px;
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }
        
        .btn-outline:hover {
            background: var(--secondary-color);
            color: var(--primary-deep);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,206,209,0.45);
        }
        
        /* ======== SECTION HEADER PREMIUM ======== */
        .section-tag {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: 'Poppins', sans-serif;
            background: rgba(0,61,130,0.06);
            border: 1px solid rgba(0,206,209,0.3);
            color: var(--primary-color);
            padding: 8px 24px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            margin-bottom: 18px;
        }
        
        .section-tag i {
            color: var(--secondary-dark);
        }
        
        .section-tag::before,
        .section-tag::after {
            content: '◆';
            font-size: 7px;
            color: var(--secondary-color);
        }
        
        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 70px;
            position: relative;
            z-index: 1;
        }
        
        .section-header h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 40px;
            font-weight: 800;
            margin-bottom: 16px;
            background: linear-gradient(120deg, var(--primary-dark) 0%, var(--primary-color) 50%, #005AA7 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }
        
        .section-header h2::after {
            content: '';
            display: block;
            width: 90px;
            height: 4px;
            margin: 20px auto 0;
            border-radius: 10px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color), var(--secondary-color));
        }
        
        .section-header p {
            color: var(--text-light);
            font-size: 17px;
            line-height: 1.8;
        }
        
        /* ======== ABOUT PREMIUM ======== */
        .about-section {
            padding: 110px 40px;
            background: linear-gradient(180deg, #ffffff 0%, #f4f7fb 100%);
            position: relative;
        }
        
        .about-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 80px;
            align-items: center;
        }
        
        .about-image {
            position: relative;
            padding: 36px;
            background: linear-gradient(135deg, rgba(0,206,209,0.1) 0%, rgba(0,61,130,0.1) 100%);
            border-radius: var(--radius-lg);
        }
        
        /* Frame berlian ganda */
        .about-image::before {
            content: '';
            position: absolute;
            top: 16px;
            left: 16px;
            right: 16px;
            bottom: 16px;
            border: 2px dashed rgba(0,206,209,0.4);
            border-radius: 16px;
        }
        
        .about-image::after {
            content: '◆';
            position: absolute;
            top: -11px;
            left: 50%;
            transform: translateX(-50%);
            color: var(--accent-color);
            font-size: 20px;
            text-shadow: 0 0 12px rgba(255,215,0,0.5);
        }
        
        .about-image img {
            width: 100%;
            max-width: 350px;
            height: auto;
            margin: 0 auto;
            display: block;
            border-radius: var(--radius-md);
            filter: drop-shadow(0 14px 34px rgba(0,61,130,0.25));
            position: relative;
            z-index: 1;
            transition: var(--transition);
        }
        
        .about-image:hover img {
            transform: translateY(-6px) scale(1.02);
        }
        
        .about-content h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            color: var(--secondary-dark);
            font-weight: 700;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .about-content h3::before {
            content: '';
            width: 34px;
            height: 3px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
            border-radius: 10px;
        }
        
        .about-content h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 38px;
            font-weight: 800;
            margin-bottom: 25px;
            line-height: 1.3;
            color: var(--primary-dark);
            letter-spacing: -0.5px;
        }
        
        .about-content p {
            color: var(--text-light);
            font-size: 16px;
            line-height: 1.9;
            margin-bottom: 20px;
        }
        
        .check-list {
            margin: 35px 0 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .check-item {
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--text-dark);
            padding: 16px 18px;
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .check-item:hover {
            background: rgba(0,206,209,0.05);
            border-color: var(--secondary-color);
            transform: translateX(5px);
            box-shadow: var(--shadow-sm);
        }
        
        .check-icon {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, var(--secondary-color), #00B8D4);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0,206,209,0.35);
        }
        
        /* ======== FEATURES PREMIUM ======== */
        .features-section {
            padding: 110px 40px;
            background: linear-gradient(135deg, #e8f4f8 0%, #f0f8fb 100%);
            position: relative;
        }
        
        .features-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="60" height="60" xmlns="http://www.w3.org/2000/svg"><circle cx="30" cy="30" r="1" fill="rgba(0,61,130,0.03)"/></svg>');
        }
        
        .features-container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
        }
        
        .feature-card {
            background: var(--white);
            padding: 42px 28px;
            border-radius: var(--radius-md);
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }
        
        /* Shine sweep saat hover */
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -80%;
            width: 50%;
            height: 100%;
            background: linear-gradient(100deg, transparent, rgba(0,206,209,0.1), transparent);
            transform: skewX(-25deg);
            transition: left 0.7s ease;
        }
        
        .feature-card:hover::before {
            left: 130%;
        }
        
        .feature-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-premium);
            border-color: rgba(0,206,209,0.4);
        }
        
        .feature-card:hover::after {
            transform: scaleX(1);
        }
        
        .feature-icon {
            width: 75px;
            height: 75px;
            margin: 0 auto 25px;
            background: linear-gradient(135deg, var(--primary-color), #005AA7);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: var(--white);
            box-shadow: 0 8px 22px rgba(0,61,130,0.3);
            transition: all 0.4s;
            position: relative;
        }
        
        .feature-icon::after {
            content: '';
            position: absolute;
            inset: 4px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.25);
        }
        
        .feature-card:hover .feature-icon {
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark));
            transform: scale(1.08) rotate(4deg);
            box-shadow: 0 12px 28px rgba(0,206,209,0.4);
        }
        
        .feature-card h4 {
            font-family: 'Poppins', sans-serif;
            font-size: 17.5px;
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 14px;
            line-height: 1.4;
        }
        
        .feature-card p {
            color: var(--text-light);
            font-size: 14px;
            line-height: 1.7;
        }
        
        /* ======== PROGRAM PREMIUM ======== */
        .program-section {
            padding: 110px 40px;
            background: linear-gradient(180deg, #ffffff 0%, #f4f7fb 100%);
        }
        
        .program-grid {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 35px;
        }
        
        .program-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: var(--transition);
            box-shadow: var(--shadow-md);
            position: relative;
        }
        
        .program-card::before {
            content: '\f521';
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            top: 16px;
            right: 16px;
            width: 38px;
            height: 38px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,215,0,0.4);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-color);
            font-size: 15px;
            z-index: 3;
        }
        
        .program-card:hover {
            transform: translateY(-12px);
            box-shadow: var(--shadow-premium);
            border-color: rgba(0,206,209,0.5);
        }
        
        .program-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 55%, #005AA7 100%);
            color: var(--white);
            padding: 48px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .program-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .program-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--accent-color), var(--secondary-color), var(--accent-color));
        }
        
        .program-header h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 62px;
            margin-bottom: 10px;
            font-weight: 800;
            background: linear-gradient(135deg, #FFD700, #FFC700, #FFD700);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 3px 8px rgba(255,215,0,0.3));
            position: relative;
            z-index: 1;
        }
        
        .program-header p {
            font-size: 18px;
            opacity: 0.95;
            position: relative;
            z-index: 1;
            font-weight: 500;
        }
        
        .program-content {
            padding: 35px 30px;
        }
        
        .program-content h4 {
            font-family: 'Poppins', sans-serif;
            font-size: 21px;
            margin-bottom: 16px;
            color: var(--primary-color);
            font-weight: 700;
        }
        
        .program-content p {
            color: var(--text-light);
            margin-bottom: 25px;
            line-height: 1.8;
            font-size: 15px;
        }
        
        .program-info {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px solid var(--primary-light);
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-dark);
            font-size: 14px;
            font-weight: 500;
        }
        
        .info-icon {
            color: var(--secondary-dark);
            font-size: 17px;
            width: 20px;
        }
        
        /* ======== MAP PREMIUM ======== */
        .map-section {
            padding: 110px 40px;
            background: linear-gradient(135deg, #e8f4f8 0%, #f0f8fb 100%);
        }
        
        .map-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: start;
        }
        
        .map-info {
            background: var(--white);
            padding: 45px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        
        .map-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color));
        }
        
        .map-info h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 28px;
            color: var(--primary-color);
            margin-bottom: 35px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .map-info h3 i {
            color: var(--secondary-dark);
        }
        
        .contact-item {
            display: flex;
            gap: 20px;
            margin-bottom: 28px;
            padding-bottom: 28px;
            border-bottom: 1px dashed var(--border-color);
            transition: var(--transition);
        }
        
        .contact-item:hover {
            transform: translateX(5px);
        }
        
        .contact-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .contact-icon {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, var(--primary-color), #005AA7);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 21px;
            flex-shrink: 0;
            box-shadow: 0 6px 16px rgba(0,61,130,0.3);
            transition: var(--transition);
        }
        
        .contact-item:hover .contact-icon {
            background: linear-gradient(135deg, var(--secondary-color), var(--secondary-dark));
            box-shadow: 0 8px 20px rgba(0,206,209,0.35);
        }
        
        .contact-details h4 {
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            color: var(--secondary-dark);
            margin-bottom: 6px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        
        .contact-details p {
            color: var(--text-dark);
            font-size: 15.5px;
            line-height: 1.6;
            font-weight: 500;
        }
        
        .map-frame {
            border-radius: var(--radius-lg);
            overflow: hidden;
            border: 1px solid var(--border-color);
            height: 500px;
            box-shadow: var(--shadow-premium);
            position: relative;
        }
        
        .map-frame::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-color), var(--secondary-color));
            z-index: 2;
        }
        
        .map-frame iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        /* ======== FOOTER PREMIUM ======== */
        footer {
            background: linear-gradient(180deg, var(--primary-deep) 0%, var(--primary-dark) 40%, var(--primary-color) 100%);
            color: var(--white);
            padding: 70px 40px 0;
            position: relative;
        }
        
        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--secondary-color), var(--accent-color), var(--secondary-color));
        }
        
        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 50px;
            margin-bottom: 44px;
        }
        
        .footer-section h3 {
            font-family: 'Poppins', sans-serif;
            margin-bottom: 22px;
            font-size: 17px;
            font-weight: 700;
            color: var(--accent-color);
            position: relative;
            padding-bottom: 14px;
        }
        
        .footer-section h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 36px;
            height: 3px;
            background: linear-gradient(90deg, var(--secondary-color), transparent);
            border-radius: 10px;
        }
        
        .footer-section p {
            color: rgba(255,255,255,0.8);
            line-height: 1.9;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 12px;
        }
        
        .footer-section a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .footer-section a i {
            color: var(--secondary-color);
            font-size: 14px;
            width: 16px;
        }
        
        .footer-section a:hover {
            color: var(--accent-color);
            padding-left: 5px;
        }
        
        .footer-bottom {
            text-align: center;
            padding: 24px 0;
            border-top: 1px solid rgba(0,206,209,0.25);
            color: rgba(255,255,255,0.65);
            font-size: 14px;
            position: relative;
        }
        
        .footer-bottom::before {
            content: '◆ ◆ ◆';
            display: block;
            font-size: 8px;
            color: rgba(255,215,0,0.4);
            margin-bottom: 10px;
            letter-spacing: 8px;
        }
        
        /* Smooth Scroll */
        html {
            scroll-behavior: smooth;
        }
        
        /* ======== ANIMASI SCROLL REVEAL ======== */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }
        
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .hero-divider {
                margin: 0 auto 28px;
            }
            
            .hero-content p {
                margin-left: auto;
                margin-right: auto;
            }
            
            .check-list {
                grid-template-columns: 1fr;
            }
            
            .about-container,
            .map-container {
                grid-template-columns: 1fr;
                gap: 50px;
            }
            
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .program-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .nav-right {
                gap: 14px;
            }
            
            .nav-menu a {
                padding: 9px 9px;
                font-size: 12.5px;
            }
        }
        
        @media (max-width: 900px) {
            .hamburger {
                display: block;
            }
            
            .nav-right {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--white);
                flex-direction: column;
                align-items: stretch;
                padding: 20px 24px;
                box-shadow: var(--shadow-lg);
                border-top: 1px solid var(--border-color);
                display: none;
            }
            
            .nav-right.open {
                display: flex;
            }
            
            .nav-menu {
                flex-direction: column;
                gap: 4px;
            }
            
            .nav-menu a {
                padding: 12px 16px;
                font-size: 14px;
            }
            
            .btn-login-admin {
                justify-content: center;
            }
            
            nav {
                position: relative;
            }
        }
        
        @media (max-width: 768px) {
            .top-bar-content {
                flex-direction: column;
                gap: 10px;
            }
            
            .hero {
                background-attachment: scroll;
                min-height: auto;
            }
            
            .hero-container {
                padding: 80px 24px;
            }
            
            /* Marquee tetap berjalan di mobile */
            .hero-content h2 {
                font-size: 36px;
            }
            
            .hero-stats {
                grid-template-columns: 1fr;
                gap: 14px;
            }
            
            .features-grid,
            .program-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 36px;
            }
            
            .section-header h2 {
                font-size: 30px;
            }
            
            .about-content h2 {
                font-size: 29px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-content">
            <div class="contact-info">
                <span><i class="fas fa-phone"></i> 0821 1115 8525</span>
                <span><i class="fas fa-envelope"></i> pkbm.trisulakusuma@gmail.com</span>
            </div>
            <div class="social-links">
                <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
            </div>
        </div>
    </div>
    
    <!-- Header -->
    <header>
        <nav>
            <div class="logo">
                <img src="admin/gambar/logo_pkbm-removebg-preview.png" alt="Logo PKBM" class="logo-image">
                <div class="logo-text">
                    <h1><?php echo $profil['nama_pkbm'] ?? 'PKBM'; ?></h1>
                    <p>Trisula Kusuma</p>
                </div>
            </div>
            <button class="hamburger" id="hamburgerBtn" aria-label="Menu">
                <i class="fas fa-bars"></i>
            </button>
            <div class="nav-right" id="navMenu">
                <ul class="nav-menu">
                    <li><a href="index.php" class="active">Beranda</a></li>
                    <li><a href="profil.php">Profil</a></li>
                    <li><a href="program.php">Program</a></li>
                    <li><a href="informasi.php">Informasi</a></li>
                    <li><a href="struktur.php">Struktur</a></li>
                    <li><a href="dokumen.php">Dokumen</a></li>
                    <li><a href="dokumentasi.php">Dokumentasi</a></li>
                    <li><a href="kontak.php">Kontak</a></li>
                </ul>
                <a href="<?php echo ADMIN_URL; ?>login.php" class="btn-login-admin">
                    <i class="fas fa-user-shield"></i> Login Admin
                </a>
            </div>
        </nav>
    </header>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-eyebrow">
                    <i class="fas fa-certificate"></i> Resmi & Terakreditasi
                </div>
                <h2>Pendidikan Berkualitas untuk Masa Depan Gemilang</h2>
                <div class="hero-divider"></div>
                <p>Pusat Kegiatan Belajar Masyarakat (PKBM) Trisula Kusuma - Lembaga pendidikan kesetaraan resmi dan terakreditasi yang memberikan kesempatan pendidikan setara bagi seluruh masyarakat Indonesia.</p>
                <div>
                    <a href="program.php" class="btn-primary">Daftar Sekarang</a>
                    <a href="profil.php" class="btn-outline">Tentang Kami</a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number">C</span>
                        <span class="stat-label">Terakreditas</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">100+</span>
                        <span class="stat-label">Lulusan Sukses</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">15+</span>
                        <span class="stat-label">Tahun Pengalaman</span>
                    </div>
                </div>
            </div>
            <div class="hero-image"></div>
        </div>
        <div class="hero-accent-line"></div>
    </section>
    
    <!-- About Section -->
    <section class="about-section">
        <div class="about-container">
            <div class="about-image reveal">
                <img src="admin/gambar/logo_pkbm-removebg-preview.png" alt="PKBM">
            </div>
            <div class="about-content reveal">
                <h3>Tentang Kami</h3>
                <h2>Pusat Kegiatan Belajar Masyarakat Trisula Kusuma</h2>
                <p>
                    PKBM Trisula Kusuma adalah lembaga pendidikan nonformal yang resmi dan terakreditasi, berlokasi di <?php echo $profil['alamat'] ?? 'Indonesia'; ?>. Kami berkomitmen untuk memberikan kesempatan pendidikan yang setara bagi masyarakat yang mengalami kendala dalam pendidikan formal.
                </p>
                <div class="check-list">
                    <div class="check-item">
                        <div class="check-icon"><i class="fas fa-check"></i></div>
                        <span>Ijazah Resmi dari Kementerian Pendidikan</span>
                    </div>
                    <div class="check-item">
                        <div class="check-icon"><i class="fas fa-check"></i></div>
                        <span>Terakreditasi C</span>
                    </div>
                    <div class="check-item">
                        <div class="check-icon"><i class="fas fa-check"></i></div>
                        <span>Tenaga Pengajar Profesional & Berpengalaman</span>
                    </div>
                    <div class="check-item">
                        <div class="check-icon"><i class="fas fa-check"></i></div>
                        <span>Sistem Pembelajaran Fleksibel</span>
                    </div>
                </div>
                <a href="https://wa.me/6282111158525?text=<?php echo urlencode('Halo PKBM Trisula Kusuma, saya ingin informasi lebih lanjut'); ?>" class="btn-primary" target="_blank">
                    <i class="fab fa-whatsapp"></i> Hubungi Kami
                </a>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features-section">
        <div class="features-container">
            <div class="section-header reveal">
                <div class="section-tag"><i class="fas fa-star"></i> Keunggulan Kami</div>
                <h2>Keunggulan PKBM Trisula Kusuma</h2>
                <p>Kami menyediakan layanan pendidikan berkualitas dengan berbagai keunggulan yang mendukung kesuksesan Anda</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-certificate"></i></div>
                    <h4>Ijazah Resmi Negara</h4>
                    <p>Ijazah Kesetaraan yang diakui secara resmi oleh Kementerian Pendidikan dengan hak yang sama dengan ijazah formal</p>
                </div>
                
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-award"></i></div>
                    <h4>Terakreditasi C</h4>
                    <p>Lembaga pendidikan resmi dengan akreditasi C yang menjamin kualitas pendidikan standar nasional</p>
                </div>
                
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-users"></i></div>
                    <h4>Bebas Usia</h4>
                    <p>Terbuka untuk semua usia tanpa batasan, memberikan kesempatan belajar bagi siapa saja yang ingin melanjutkan pendidikan</p>
                </div>
                
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-globe"></i></div>
                    <h4>Bebas Domisili</h4>
                    <p>Menerima peserta didik dari seluruh Indonesia bahkan luar negeri dengan sistem pembelajaran yang fleksibel</p>
                </div>
                
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <h4>Biaya Terjangkau</h4>
                    <p>Biaya pendidikan yang sangat terjangkau dengan sistem pembayaran yang dapat dicicil sesuai kemampuan</p>
                </div>
                
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-laptop"></i></div>
                    <h4>E-Learning System</h4>
                    <p>Sistem pembelajaran modern berbasis teknologi yang memudahkan akses belajar kapan saja dan dimana saja</p>
                </div>
                
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <h4>Tutor Profesional</h4>
                    <p>Tenaga pengajar berpengalaman dan berkualifikasi yang siap membimbing setiap tahap pembelajaran Anda</p>
                </div>
                
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-clock"></i></div>
                    <h4>Waktu Fleksibel</h4>
                    <p>Jadwal belajar yang dapat disesuaikan dengan aktivitas Anda, cocok bagi yang sudah bekerja atau memiliki kesibukan lain</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Program Section -->
    <section class="program-section">
        <div class="section-header reveal">
            <div class="section-tag"><i class="fas fa-graduation-cap"></i> Program Kami</div>
            <h2>Program Pendidikan Kesetaraan</h2>
            <p>Pilih program pendidikan yang sesuai dengan kebutuhan Anda</p>
        </div>
        
        <div class="program-grid">
            <?php 
            $paket_info = ['A' => 'SD/MI', 'B' => 'SMP/MTs', 'C' => 'SMA/MA'];
            while ($program = $programs->fetch_assoc()): 
            ?>
            <div class="program-card reveal">
                <div class="program-header">
                    <h3>PAKET <?php echo $program['jenis_paket']; ?></h3>
                    <p>Setara <?php echo $paket_info[$program['jenis_paket']] ?? ''; ?></p>
                </div>
                <div class="program-content">
                    <h4><?php echo $program['nama_program']; ?></h4>
                    <p><?php echo substr($program['deskripsi'], 0, 120) . '...'; ?></p>
                    <div class="program-info">
                        <?php if ($program['biaya']): ?>
                        <div class="info-item">
                            <i class="fas fa-money-check-alt info-icon"></i>
                            <span><?php echo $program['biaya']; ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($program['durasi']): ?>
                        <div class="info-item">
                            <i class="fas fa-calendar-alt info-icon"></i>
                            <span><?php echo $program['durasi']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </section>
    
    <!-- Map Section -->
    <section class="map-section">
        <div class="section-header reveal">
            <div class="section-tag"><i class="fas fa-map-marker-alt"></i> Lokasi Kami</div>
            <h2>Lokasi & Kontak Kami</h2>
            <p>Kunjungi kami atau hubungi untuk informasi lebih lanjut</p>
        </div>
        
        <div class="map-container">
            <div class="map-info reveal">
                <h3><i class="fas fa-address-card"></i> Informasi Kontak</h3>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-details">
                        <h4>Alamat</h4>
                        <p><?php echo $profil['alamat'] ?? 'Jalan Bail\'tahyat, Ohoi Mun Ngurditwain, Kec. Kei Besar Utara Barat, Kabupaten Maluku Tenggara'; ?></p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="contact-details">
                        <h4>Telepon</h4>
                        <p>0821 1115 8525</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-details">
                        <h4>Email</h4>
                        <p>pkbm.trisulakusuma@gmail.com</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="contact-details">
                        <h4>Jam Operasional</h4>
                        <p>Senin - Jumat: 08.00 - 16.00 WIT<br>Sabtu: 08.00 - 12.00 WIT</p>
                    </div>
                </div>
            </div>
            
            <div class="map-frame reveal">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.521260322283!2d106.8195613!3d-6.1751171!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f5d2e764b12d%3A0x3d2ad6e1e0e9bcc8!2sMonas!5e0!3m2!1sen!2sid!4v1234567890" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3><?php echo $profil['nama_pkbm'] ?? 'PKBM'; ?></h3>
                <p><?php echo $profil['alamat'] ?? 'Jalan Bail\'tahyat, Ohoi Mun Ngurditwain, Kec. Kei Besar Utara Barat, Kabupaten Maluku Tenggara'; ?></p>
                <p>Telp: 0821 1115 8525</p>
                <p>Email: pkbm.trisulakusuma@gmail.com</p>
            </div>
            
            <div class="footer-section">
                <h3>Menu</h3>
                <ul>
                    <li><a href="profil.php">Profil PKBM</a></li>
                    <li><a href="program.php">Program</a></li>
                    <li><a href="informasi.php">Informasi</a></li>
                    <li><a href="struktur.php">Struktur Organisasi</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Layanan</h3>
                <ul>
                    <li><a href="dokumen.php">Download Dokumen</a></li>
                    <li><a href="dokumentasi.php">Galeri</a></li>
                    <li><a href="kontak.php">Kontak Kami</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Ikuti Kami</h3>
                <ul>
                    <li><a href="#"><i class="fab fa-facebook-f"></i> Facebook</a></li>
                    <li><a href="#"><i class="fab fa-instagram"></i> Instagram</a></li>
                    <li><a href="#"><i class="fab fa-youtube"></i> YouTube</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo $profil['nama_pkbm'] ?? 'PKBM'; ?> Trisula Kusuma. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Mobile menu toggle (hamburger)
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const navMenu = document.getElementById('navMenu');
        
        hamburgerBtn.addEventListener('click', function() {
            navMenu.classList.toggle('open');
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });
        
        // Scroll reveal animation
        const revealObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        
        document.querySelectorAll('.reveal').forEach(function(el) {
            revealObserver.observe(el);
        });
    </script>
</body>
</html>