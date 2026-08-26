<?php
require_once 'db.php';
$settings = get_settings($pdo);

$success_msg = '';
$error_msg = '';
$registered_user = null;

// Smart YouTube URL Parser (Converts any YouTube format to Embed URL)
function get_youtube_embed_url($url) {
    if (empty($url)) return null;
    $url = trim($url);
    
    if (strpos($url, 'youtube.com/embed/') !== false) {
        return $url;
    }
    
    // Matches standard watch?v=, youtu.be/, /live/, /v/, /embed/
    $pattern = '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=|(?:live\/))|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
    if (preg_match($pattern, $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1] . '?autoplay=1&rel=0&modestbranding=1';
    }
    
    return null;
}

// Process Registration Form & Save to Database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $attendance_mode = $_POST['attendance_mode'] ?? 'All seven days';
    $needs_accommodation = $_POST['needs_accommodation'] ?? 'No';
    $medical_support = $_POST['medical_support'] ?? 'No';
    $prayer_request = trim($_POST['prayer_request'] ?? '');

    if (empty($fullname) || empty($phone) || empty($email) || empty($location)) {
        $error_msg = "Please complete all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } else {
        $reg_code = 'ETAG-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

        try {
            $stmt = $pdo->prepare("INSERT INTO registrations 
                (reg_code, fullname, email, phone, gender, location, attendance_mode, needs_accommodation, medical_support, prayer_request, created_at) 
                VALUES (?, ?, ?, ?, 'Male', ?, ?, ?, ?, ?, NOW())");
            
            $stmt->execute([
                $reg_code, $fullname, $email, $phone, $location, $attendance_mode, $needs_accommodation, $medical_support, $prayer_request
            ]);

            $success_msg = "Your registration for Acts of the Holy Spirit Convention 2026 is confirmed.";
            $registered_user = [
                'code' => $reg_code,
                'name' => $fullname,
                'email' => $email,
                'phone' => $phone,
                'mode' => $attendance_mode,
                'location' => $location,
                'date' => date('d M Y')
            ];
        } catch (PDOException $e) {
            $error_msg = "Database error: " . $e->getMessage();
        }
    }
}

// Background Images, Logo & Livestream Settings
$hero_bg = $settings['hero_bg_image'] ?? 'https://images.unsplash.com/photo-1519791883288-dc8bd696e667?auto=format&fit=crop&w=1920&q=80';
$about_bg = $settings['about_bg_image'] ?? 'https://images.unsplash.com/photo-1438232992991-995b7058bbb3?auto=format&fit=crop&w=1920&q=80';
$venue_bg = $settings['venue_bg_image'] ?? 'https://images.unsplash.com/photo-1548625361-073c68ea8ea9?auto=format&fit=crop&w=1920&q=80';
$broadcast_bg = $settings['broadcast_bg_image'] ?? 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?auto=format&fit=crop&w=1920&q=80';
$register_bg = $settings['register_bg_image'] ?? 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=1920&q=80';
$logo_url = !empty($settings['logo_url']) ? htmlspecialchars($settings['logo_url']) : null;
$venue_address_text = $settings['venue_address'] ?? "38 Kareem Adetunji Street, beside Diekola house, Fomwan Roundabout, Heritage Hotel junction, Ogo-Oluwa, Osogbo, Nigeria.";

// Livestream Variables
$raw_yt_url = $settings['youtube_live_url'] ?? '';
$is_live_now = !empty($settings['is_live_now']) && $settings['is_live_now'] == 1;
$yt_embed_url = get_youtube_embed_url($raw_yt_url);
$livestream_title = $settings['livestream_title'] ?? 'Official Live Broadcast — Acts of the Holy Spirit Convention 2026';
?>
<!DOCTYPE html>
<html lang="en" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= htmlspecialchars($settings['convention_title'] ?? 'The Acts of the Holy Spirit Convention 2026') ?> — <?= htmlspecialchars($settings['theme_title'] ?? 'Flaming Fire') ?></title>
    
    <!-- Instant Default to "Auto" Theme Detection Script (Zero FOUC Flash) -->
    <script>
        (function() {
            let savedTheme = localStorage.getItem('etag_theme');
            if (!savedTheme) {
                savedTheme = 'auto';
                localStorage.setItem('etag_theme', 'auto');
            }
            document.documentElement.setAttribute('data-theme', savedTheme);
            if (savedTheme === 'auto') {
                const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.setAttribute('data-resolved-theme', systemDark ? 'dark' : 'light');
            } else {
                document.documentElement.setAttribute('data-resolved-theme', savedTheme);
            }
        })();
    </script>

    <!-- Typography & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600;1,700;1,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Client-Side PDF & Image Generation Engines -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        /* ========================================================
           DESIGN SYSTEM & THEMED OVERLAYS
           ======================================================== */
        :root, [data-resolved-theme="dark"] {
            --bg-body: #06080d;
            --bg-card: rgba(13, 17, 26, 0.88);
            --bg-card-solid: #0d111a;
            --bg-card-hover: rgba(20, 27, 43, 0.95);
            --bg-input: #070a10;
            --border: rgba(255, 255, 255, 0.09);
            --border-active: rgba(229, 169, 60, 0.55);
            --gold: #e5a93c;
            --gold-glow: rgba(229, 169, 60, 0.25);
            --gold-light: #fef08a;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --text-dim: #6b7280;
            --nav-bg: rgba(6, 8, 13, 0.94);
            --ticker-bg: #090c14;
            --pass-bg: #090d14;
            --broadcast-bg: #000000;
            --btn-primary-bg: #ffffff;
            --btn-primary-text: #07090e;
            --btn-tab-active-bg: #ffffff;
            --btn-tab-active-text: #000000;
            --gradient-theme: linear-gradient(135deg, #fffbeb 0%, #f59e0b 50%, #ea580c 100%);
            --shadow-subtle: 0 12px 36px rgba(0,0,0,0.6);
            --shadow-gold: 0 0 25px rgba(229, 169, 60, 0.2);
            
            /* Section Atmospheric Overlays */
            --hero-overlay: radial-gradient(circle at center 30%, rgba(7, 9, 14, 0.75) 0%, rgba(6, 8, 13, 0.98) 100%);
            --about-overlay: linear-gradient(180deg, rgba(6, 8, 13, 0.94) 0%, rgba(6, 8, 13, 0.97) 100%);
            --venue-overlay: linear-gradient(180deg, rgba(6, 8, 13, 0.92) 0%, rgba(6, 8, 13, 0.98) 100%);
            --broadcast-overlay: linear-gradient(180deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.96) 100%);
            --register-overlay: linear-gradient(180deg, rgba(6, 8, 13, 0.92) 0%, rgba(6, 8, 13, 0.98) 100%);
            --footer-overlay: linear-gradient(180deg, rgba(6, 8, 13, 0.96) 0%, rgba(4, 5, 8, 1) 100%);
        }

        [data-resolved-theme="light"] {
            --bg-body: #f8fafc;
            --bg-card: rgba(255, 255, 255, 0.92);
            --bg-card-solid: #ffffff;
            --bg-card-hover: rgba(241, 245, 249, 0.98);
            --bg-input: #f8fafc;
            --border: rgba(0, 0, 0, 0.08);
            --border-active: rgba(180, 121, 24, 0.55);
            --gold: #b47918;
            --gold-glow: rgba(180, 121, 24, 0.2);
            --gold-light: #855306;
            --text-main: #0f172a;
            --text-muted: #475569;
            --text-dim: #64748b;
            --nav-bg: rgba(248, 250, 252, 0.95);
            --ticker-bg: #edf2f7;
            --pass-bg: #ffffff;
            --broadcast-bg: #0f172a;
            --btn-primary-bg: #0f172a;
            --btn-primary-text: #ffffff;
            --btn-tab-active-bg: #0f172a;
            --btn-tab-active-text: #ffffff;
            --gradient-theme: linear-gradient(135deg, #0f172a 0%, #b47918 50%, #ea580c 100%);
            --shadow-subtle: 0 10px 30px rgba(0,0,0,0.06);
            --shadow-gold: 0 0 20px rgba(180, 121, 24, 0.15);
            
            /* Section Atmospheric Overlays */
            --hero-overlay: radial-gradient(circle at center 30%, rgba(248, 250, 252, 0.85) 0%, rgba(248, 250, 252, 0.98) 100%);
            --about-overlay: linear-gradient(180deg, rgba(248, 250, 252, 0.94) 0%, rgba(248, 250, 252, 0.98) 100%);
            --venue-overlay: linear-gradient(180deg, rgba(248, 250, 252, 0.93) 0%, rgba(248, 250, 252, 0.98) 100%);
            --broadcast-overlay: linear-gradient(180deg, rgba(15, 23, 42, 0.90) 0%, rgba(15, 23, 42, 0.98) 100%);
            --register-overlay: linear-gradient(180deg, rgba(248, 250, 252, 0.94) 0%, rgba(248, 250, 252, 0.98) 100%);
            --footer-overlay: linear-gradient(180deg, rgba(248, 250, 252, 0.96) 0%, rgba(241, 245, 249, 1) 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
            scroll-behavior: smooth;
            transition: background-color 0.25s ease, color 0.25s ease, border-color 0.25s ease;
        }

        i.fa-brands.fa-youtube {
            margin-right: 10px !important; /* Overrides default FontAwesome margins */
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            line-height: 1.65;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            width: 100%;
        }

        a { color: inherit; text-decoration: none; }
        .container { width: 90%; max-width: 1140px; margin: 0 auto; position: relative; z-index: 2; }

        .font-serif { font-family: 'Playfair Display', serif; }
        .font-cinzel { font-family: 'Cinzel', serif; }

        /* Navigation */
        header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: var(--nav-bg);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--border);
            padding: 0.85rem 0;
        }
        .nav-inner { display: flex; justify-content: space-between; align-items: center; }
        
        .brand-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.84rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-main);
        }
        .brand-logo {
            height: 38px;
            max-width: 48px;
            object-fit: contain;
            border-radius: 4px;
        }
        .brand-text { display: flex; flex-direction: column; line-height: 1.15; }
        .brand-text span { color: var(--gold); font-size: 0.72rem; letter-spacing: 1.5px; }
        
        .nav-right-cluster { display: flex; gap: 14px; align-items: center; }
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-links a { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); transition: all 0.2s; position: relative; }
        .nav-links a:hover { color: var(--text-main); }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0%;
            height: 2px;
            background: var(--gold);
            transition: width 0.25s ease;
        }
        .nav-links a:hover::after { width: 100%; }

        /* Admin Login Button */
        .btn-admin-nav {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 30px;
            border: 1px solid var(--border);
            background: var(--bg-card-solid);
            color: var(--text-muted);
            transition: all 0.2s ease;
        }
        .btn-admin-nav i { color: var(--gold); font-size: 0.75rem; }
        .btn-admin-nav:hover {
            border-color: var(--gold);
            color: var(--text-main);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px var(--gold-glow);
        }

        /* Theme Switcher Pill */
        .theme-switcher-wrap {
            display: inline-flex;
            align-items: center;
            background: var(--bg-card-solid);
            border: 1px solid var(--border);
            border-radius: 30px;
            padding: 3px;
            gap: 2px;
        }
        .theme-btn {
            background: transparent;
            border: none;
            color: var(--text-dim);
            padding: 5px 9px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.72rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-weight: 700;
            transition: all 0.2s;
        }
        .theme-btn:hover { color: var(--text-main); }
        .theme-btn.active {
            background: var(--gold);
            color: #07090e;
            box-shadow: 0 2px 6px rgba(0,0,0,0.18);
        }

        .mobile-nav-toggle {
            display: none;
            background: transparent;
            border: none;
            color: var(--text-main);
            font-size: 1.35rem;
            cursor: pointer;
            padding: 6px;
        }

        .mobile-drawer {
            display: none;
            flex-direction: column;
            background: var(--bg-card-solid);
            border-bottom: 1px solid var(--border);
            padding: 1rem var(--border);
            width: 100%;
        }
        .mobile-drawer.open { display: flex; }
        .mobile-drawer a {
            padding: 12px 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .mobile-drawer a:last-child { border-bottom: none; }
        .mobile-drawer a:hover { color: var(--gold); }

        /* Hero */
        .hero {
            position: relative;
            padding: 6rem 0 4rem;
            text-align: center;
            background-image: var(--hero-overlay), url('<?= htmlspecialchars($hero_bg) ?>');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
        }
        .hero-live-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(229, 169, 60, 0.12);
            border: 1px solid var(--border-active);
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--gold);
            margin-bottom: 1.6rem;
            font-weight: 800;
            box-shadow: 0 4px 16px var(--gold-glow);
        }
        .live-dot {
            width: 7px;
            height: 7px;
            background-color: #22c55e;
            border-radius: 50%;
            box-shadow: 0 0 8px #22c55e;
            animation: pulse-dot 1.8s infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.4); opacity: 0.6; }
        }

        .hero-title {
            font-family: 'Cinzel', serif;
            font-size: clamp(2.2rem, 6.5vw, 4.8rem);
            font-weight: 900;
            letter-spacing: -0.5px;
            line-height: 1.08;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            color: var(--text-main);
            text-shadow: 0 4px 24px rgba(0,0,0,0.5);
        }
        .hero-theme-display {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: clamp(2.6rem, 7.5vw, 5.6rem);
            font-weight: 800;
            background: var(--gradient-theme);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.4rem;
            line-height: 1.02;
        }
        .hero-desc {
            max-width: 680px;
            margin: 0 auto 2.5rem;
            font-size: clamp(0.98rem, 2.5vw, 1.12rem);
            color: var(--text-muted);
            line-height: 1.7;
            padding: 0 10px;
        }

        .hero-actions { display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; margin-bottom: 3.5rem; }
        .btn-primary {
            background: var(--btn-primary-bg);
            color: var(--btn-primary-text);
            padding: 14px 32px;
            border-radius: 40px;
            font-size: 0.92rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.25s;
            cursor: pointer;
            border: none;
            box-shadow: var(--shadow-subtle);
        }
        .btn-primary:hover {
            background: var(--gold);
            color: #07090e;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px var(--gold-glow);
        }
        .btn-outline {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 14px 30px;
            border-radius: 40px;
            font-size: 0.92rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            transition: all 0.25s;
        }
        .btn-outline:hover {
            border-color: var(--gold);
            color: var(--gold);
            transform: translateY(-2px);
        }

        /* Countdown */
        .countdown-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            max-width: 680px;
            margin: 0 auto;
            padding: 2rem 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(10px);
        }
        .count-item { text-align: center; }
        .count-val { font-size: clamp(2rem, 5vw, 3.4rem); font-weight: 800; font-family: 'Cinzel', serif; line-height: 1; color: var(--text-main); }
        .count-lbl { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 2px; color: var(--text-dim); margin-top: 6px; font-weight: 600; }

        /* Marquee Ticker */
        .ticker-wrap {
            overflow: hidden;
            border-bottom: 1px solid var(--border);
            padding: 0.9rem 0;
            background: var(--ticker-bg);
            white-space: nowrap;
        }
        .ticker-move { display: inline-block; animation: ticker 28s linear infinite; }
        .ticker-text { font-size: 0.84rem; text-transform: uppercase; letter-spacing: 2.2px; color: var(--gold); font-weight: 700; }
        @keyframes ticker { 0% { transform: translate3d(0, 0, 0); } 100% { transform: translate3d(-50%, 0, 0); } }

        /* Sections */
        section { padding: 5.5rem 0; border-bottom: 1px solid var(--border); position: relative; }
        .sec-number { font-size: 0.82rem; text-transform: uppercase; letter-spacing: 2.5px; color: var(--gold); font-weight: 800; margin-bottom: 0.8rem; }
        .sec-heading { font-family: 'Cinzel', serif; font-size: clamp(1.7rem, 3.6vw, 2.8rem); font-weight: 800; line-height: 1.2; margin-bottom: 0.9rem; color: var(--text-main); }
        .sec-sub { font-size: clamp(0.95rem, 2vw, 1.08rem); color: var(--text-muted); max-width: 680px; margin-bottom: 3rem; line-height: 1.7; }

        /* 01 — Pillars */
        #about {
            background-image: var(--about-overlay), url('<?= htmlspecialchars($about_bg) ?>');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
        }
        .pillars-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 18px; }
        .pillar-card {
            background: var(--bg-card);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 2.2rem 1.6rem;
            box-shadow: var(--shadow-subtle);
            transition: all 0.3s ease;
        }
        .pillar-card:hover { border-color: var(--border-active); transform: translateY(-4px); background: var(--bg-card-hover); box-shadow: var(--shadow-gold); }
        .pillar-idx { font-family: 'Cinzel', serif; font-size: 1.4rem; color: var(--gold); margin-bottom: 0.8rem; font-weight: 800; }
        .pillar-name { font-size: 1.18rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-main); }
        .pillar-desc { font-size: 0.88rem; color: var(--text-muted); line-height: 1.65; }

        /* 02 — Ministers */
        .ministers-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 18px; }
        .minister-card {
            background: var(--bg-card);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 2.2rem 1.6rem;
            box-shadow: var(--shadow-subtle);
            transition: all 0.3s ease;
        }
        .minister-card:hover { border-color: var(--border-active); transform: translateY(-3px); }
        .minister-role { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 2px; color: var(--gold); margin-bottom: 6px; font-weight: 800; }
        .minister-name { font-family: 'Cinzel', serif; font-size: 1.3rem; font-weight: 700; color: var(--text-main); }
        .minister-tagline { font-size: 0.86rem; color: var(--text-muted); margin-top: 6px; }

        /* 03 — Programme */
        .tab-nav {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 12px;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border);
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .tab-nav::-webkit-scrollbar { display: none; }
        
        .tab-btn {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 0.84rem;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
            transition: all 0.2s;
        }
        .tab-btn.active, .tab-btn:hover { background: var(--btn-tab-active-bg); color: var(--btn-tab-active-text); border-color: var(--btn-tab-active-bg); }

        .session-list { display: flex; flex-direction: column; gap: 14px; }
        .session-item {
            background: var(--bg-card);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.5rem 1.8rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow-subtle);
            transition: 0.25s;
        }
        .session-item:hover { border-color: var(--border-active); transform: translateX(3px); }
        .session-time-col { min-width: 120px; }
        .session-time { font-family: 'Cinzel', serif; font-size: 1.15rem; font-weight: 800; color: var(--gold); }
        .session-period { font-size: 0.72rem; text-transform: uppercase; color: var(--text-dim); font-weight: 600; }
        .session-info { flex-grow: 1; }
        .session-badge {
            display: inline-block;
            background: rgba(229, 169, 60, 0.12);
            color: var(--gold);
            font-size: 0.68rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 2px 8px;
            border-radius: 4px;
            margin-bottom: 4px;
        }
        .session-title { font-size: 1.05rem; font-weight: 700; color: var(--text-main); margin: 2px 0; }
        .session-desc { font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; }

        /* 04 — Venue */
        #venue {
            background-image: var(--venue-overlay), url('<?= htmlspecialchars($venue_bg) ?>');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
        }
        .venue-grid { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 28px; }
        .venue-address-box {
            background: var(--bg-card);
            backdrop-filter: blur(14px);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-subtle);
        }
        .address-actions { display: flex; gap: 10px; margin-top: 1.5rem; flex-wrap: wrap; }
        .btn-copy-addr {
            background: var(--bg-input);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 10px 18px;
            border-radius: 30px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-copy-addr:hover { border-color: var(--gold); color: var(--gold); }

        /* ========================================================
           05 — BROADCAST & YOUTUBE PLAYER WRAPPER
           ======================================================== */
        .broadcast-card-wrap {
            background-image: var(--broadcast-overlay), url('<?= htmlspecialchars($broadcast_bg) ?>');
            background-size: cover;
            background-position: center center;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: clamp(2rem, 5vw, 3.5rem) 1.5rem;
            max-width: 900px;
            margin: 0 auto;
            box-shadow: var(--shadow-subtle);
            position: relative;
        }

        .live-stream-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
            flex-wrap: wrap;
            gap: 12px;
        }
        .live-badge-glow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(239, 68, 68, 0.18);
            border: 1px solid #ef4444;
            color: #ef4444;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            box-shadow: 0 0 16px rgba(239, 68, 68, 0.3);
        }
        .red-dot-pulse {
            width: 8px;
            height: 8px;
            background-color: #ef4444;
            border-radius: 50%;
            animation: pulse-dot 1.4s infinite;
        }

        /* 16:9 Fluid Video Container */
        .video-player-container {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            height: 0;
            overflow: hidden;
            border-radius: 14px;
            border: 1px solid var(--border-active);
            box-shadow: 0 10px 30px rgba(0,0,0,0.6);
            background: #000;
            margin-bottom: 1.2rem;
        }
        .video-player-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }

        .broadcast-meta-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 14px;
            font-size: 0.85rem;
            color: var(--text-muted);
            flex-wrap: wrap;
            gap: 10px;
        }

        /* 06 — FAQ */
        .faq-item { border-bottom: 1px solid var(--border); padding: 1.3rem 0; }
        .faq-q { font-size: 1.02rem; font-weight: 700; color: var(--text-main); cursor: pointer; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .faq-a { font-size: 0.92rem; color: var(--text-muted); margin-top: 10px; line-height: 1.7; display: none; }
        .faq-item.open .faq-a { display: block; }
        .faq-item.open .faq-icon { transform: rotate(45deg); color: var(--gold); }

        /* 07 — Registration */
        #register {
            background-image: var(--register-overlay), url('<?= htmlspecialchars($register_bg) ?>');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
        }
        .reg-split { display: grid; grid-template-columns: 1fr 1.35fr; gap: 32px; }
        .reg-meta { background: var(--bg-card); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid var(--border); border-radius: 18px; padding: 2.2rem; height: fit-content; box-shadow: var(--shadow-subtle); }
        .reg-meta-item { border-bottom: 1px solid var(--border); padding: 0.95rem 0; }
        .reg-meta-item:last-child { border-bottom: none; }
        .reg-meta-lbl { font-size: 0.74rem; text-transform: uppercase; letter-spacing: 1.8px; color: var(--text-dim); font-weight: 700; }
        .reg-meta-val { font-size: 1rem; font-weight: 700; color: var(--text-main); margin-top: 4px; }

        .reg-form-panel { background: var(--bg-card); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid var(--border); border-radius: 18px; padding: 2.2rem; box-shadow: var(--shadow-subtle); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 1.4rem; }
        .form-full { grid-column: span 2; }
        .form-lbl { font-size: 0.8rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 6px; }
        
        .form-input-wrap { position: relative; }
        .form-input-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-dim); font-size: 0.9rem; }
        .form-input-wrap .form-input { padding-left: 38px; }
        
        .form-input {
            width: 100%;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 13px 14px;
            color: var(--text-main);
            font-size: 16px;
            outline: none;
            transition: all 0.2s;
        }
        .form-input:focus { border-color: var(--gold); box-shadow: 0 0 0 3px var(--gold-glow); }
        .btn-submit-reg {
            background: var(--btn-primary-bg);
            color: var(--btn-primary-text);
            border: none;
            width: 100%;
            padding: 15px;
            border-radius: 30px;
            font-size: 0.95rem;
            font-weight: 800;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            transition: all 0.25s;
            margin-top: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit-reg:hover { background: var(--gold); color: #07090e; box-shadow: 0 6px 20px var(--gold-glow); }

        /* Pass Badge */
        .pass-container {
            background: var(--pass-bg);
            border: 1.5px dashed var(--gold);
            border-radius: 16px;
            padding: 1.8rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-subtle);
        }
        .pass-badge {
            background: #ffffff;
            color: #07090e !important;
            border: 2px solid #e5a93c;
            border-radius: 14px;
            padding: 24px;
            margin: 16px 0;
            text-align: left;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        .pass-badge * { color: #07090e !important; }
        .pass-badge-top-strip {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #b47918 0%, #e5a93c 50%, #ea580c 100%);
        }
        .pass-badge-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }
        .pass-badge-title { font-family: 'Cinzel', serif; font-size: 1.05rem; font-weight: 800; text-transform: uppercase; }
        .pass-badge-theme { font-size: 0.78rem; color: #b47918 !important; font-weight: 700; margin-top: 2px; }
        .pass-badge-body { display: grid; grid-template-columns: 1fr auto; gap: 16px; align-items: center; }
        .pass-badge-name { font-size: 1.35rem; font-weight: 800; font-family: 'Cinzel', serif; }
        .pass-badge-meta { font-size: 0.85rem; color: #475569 !important; margin-top: 6px; line-height: 1.5; }
        
        .pass-badge-code-wrap {
            background: #07090e;
            padding: 12px 16px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #e5a93c;
            margin-top: 14px;
        }
        .pass-badge-code-wrap * { color: #ffffff !important; }
        .pass-badge-code-lbl { font-size: 0.68rem; color: #e5a93c !important; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; }
        .pass-badge-code-val { font-size: 1.45rem; font-weight: 800; font-family: 'Cinzel', serif; letter-spacing: 2.5px; margin-top: 2px; }
        
        .pass-qr-box { text-align: center; }
        .pass-qr-box img { width: 90px; height: 90px; border-radius: 8px; border: 1px solid #cbd5e1; }
        .pass-badge-footer {
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #64748b !important;
            font-weight: 600;
        }

        .pass-action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-top: 16px;
        }
        .btn-pass-action {
            background: var(--bg-card-solid);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 12px 14px;
            border-radius: 30px;
            font-size: 0.82rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-pass-action:hover {
            border-color: var(--gold);
            color: var(--gold);
            transform: translateY(-2px);
        }
        .btn-pass-action.primary {
            background: var(--gold);
            color: #07090e !important;
            border-color: var(--gold);
        }
        .btn-pass-action.primary:hover {
            background: #ffffff;
            color: #07090e !important;
        }
        .btn-pass-action.whatsapp {
            background: #22c55e;
            color: #ffffff !important;
            border-color: #22c55e;
        }
        .btn-pass-action.whatsapp:hover {
            background: #16a34a;
        }

        /* Footer */
        footer {
            position: relative;
            padding: 4.5rem 0 2.5rem;
            font-size: 0.85rem;
            color: var(--text-dim);
            background-image: var(--footer-overlay), url('<?= htmlspecialchars($about_bg) ?>');
            background-size: cover;
            background-position: center bottom;
        }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 24px; margin-bottom: 2.5rem; }
        .footer-col h4 { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-main); margin-bottom: 1rem; font-weight: 800; }
        .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 8px; }
        .footer-col ul a:hover { color: var(--gold); }
        .footer-bottom { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 1.8rem; font-size: 0.8rem; }

        .toast-msg {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: var(--gold);
            color: #07090e;
            padding: 12px 20px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 700;
            box-shadow: var(--shadow-subtle);
            display: none;
            z-index: 2000;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .nav-links { display: none; }
            .btn-admin-nav { display: none; }
            .mobile-nav-toggle { display: block; }
            .venue-grid, .reg-split, .footer-grid { grid-template-columns: 1fr; gap: 24px; }
            .footer-bottom { flex-direction: column; gap: 10px; text-align: center; }
            .pass-action-buttons { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            section { padding: 4rem 0; }
            .hero, #about, #venue, #register, footer { background-attachment: scroll; }
            .hero { padding: 4.8rem 0 3rem; }
            .hero-actions .btn-primary, .hero-actions .btn-outline { width: 100%; max-width: 320px; }
            .reg-meta, .reg-form-panel { padding: 1.6rem 1.2rem; }
            .form-grid { grid-template-columns: 1fr; gap: 12px; }
            .form-full { grid-column: span 1; }
            .session-item { flex-direction: column; align-items: flex-start; gap: 8px; }
            .theme-btn span.theme-name { display: none; }
            .pass-badge-body { grid-template-columns: 1fr; }
            .pass-qr-box { display: none; }
        }

        @media (max-width: 480px) {
            .countdown-strip { gap: 6px; padding: 1.4rem 0; }
            .count-val { font-size: 1.75rem; }
            .count-lbl { font-size: 0.64rem; }
            .brand-text { font-size: 0.75rem; }
            .brand-logo { height: 32px; }
        }
    </style>
</head>
<body>

    <!-- Toast Notification -->
    <div id="toast" class="toast-msg"></div>

    <!-- Header Navigation -->
    <header>
        <div class="container nav-inner">
            <a href="#" class="brand-badge">
                <?php if ($logo_url): ?>
                    <img src="<?= $logo_url ?>" alt="Logo" class="brand-logo">
                <?php endif; ?>
                <div class="brand-text">
                    <?= htmlspecialchars($settings['ministry_name'] ?? 'The End Time Army of God') ?>
                    <span>2026 Convention</span>
                </div>
            </a>

            <div class="nav-right-cluster">
                <!-- Desktop Links -->
                <div class="nav-links">
                    <a href="#about">About</a>
                    <a href="#ministers">Ministers</a>
                    <a href="#programme">Programme</a>
                    <a href="#venue">Venue</a>
                    <a href="#broadcast">Broadcast</a>
                    <a href="#faq">FAQ</a>
                </div>

                <!-- Admin Login Button -->
                <a href="admin.php" class="btn-admin-nav" title="Administrator Login">
                    <i class="fa-solid fa-lock"></i> Admin
                </a>

                <!-- Theme Switcher Pill -->
                <div class="theme-switcher-wrap" role="group" aria-label="Theme selection">
                    <button class="theme-btn" id="theme-btn-auto" onclick="setAppTheme('auto')" title="System Theme">
                        <i class="fa-solid fa-desktop"></i> <span class="theme-name">Auto</span>
                    </button>
                    <button class="theme-btn" id="theme-btn-light" onclick="setAppTheme('light')" title="Light Theme">
                        <i class="fa-solid fa-sun"></i> <span class="theme-name">Light</span>
                    </button>
                    <button class="theme-btn" id="theme-btn-dark" onclick="setAppTheme('dark')" title="Dark Theme">
                        <i class="fa-solid fa-moon"></i> <span class="theme-name">Dark</span>
                    </button>
                </div>

                <!-- Mobile Hamburger Toggle -->
                <button class="mobile-nav-toggle" onclick="toggleMobileMenu()" aria-label="Toggle menu">
                    <i class="fa-solid fa-bars" id="menu-icon"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Drawer Menu -->
        <div class="mobile-drawer" id="mobileDrawer">
            <a href="#about" onclick="closeMobileMenu()"><i class="fa-solid fa-fire"></i> About Convention</a>
            <a href="#ministers" onclick="closeMobileMenu()"><i class="fa-solid fa-user-group"></i> Ministers &amp; Hosts</a>
            <a href="#programme" onclick="closeMobileMenu()"><i class="fa-solid fa-calendar-days"></i> Programme Schedule</a>
            <a href="#venue" onclick="closeMobileMenu()"><i class="fa-solid fa-location-dot"></i> Venue &amp; Logistics</a>
            <a href="#broadcast" onclick="closeMobileMenu()"><i class="fa-solid fa-video"></i> Live Broadcast</a>
            <a href="#faq" onclick="closeMobileMenu()"><i class="fa-solid fa-circle-question"></i> FAQ</a>
            <a href="#register" onclick="closeMobileMenu()" style="color: var(--gold); font-weight: 700;"><i class="fa-solid fa-ticket"></i> Attendee Registration →</a>
            <a href="admin.php" onclick="closeMobileMenu()" style="color: var(--text-main); font-weight: 600;">
                <i class="fa-solid fa-lock" style="color: var(--gold);"></i> Admin Portal
            </a>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-live-pill">
                <span class="live-dot"></span>
                <?= htmlspecialchars($settings['event_dates'] ?? '5 October — 11 October 2026') ?>
            </div>
            
            <h1 class="hero-title"><?= htmlspecialchars($settings['convention_title'] ?? 'The Mantle') ?></h1>
            <div class="hero-theme-display"><?= htmlspecialchars($settings['theme_title'] ?? 'Flaming Fire') ?></div>
            
            <p class="hero-desc">
                Seven days of worship, the Word, prayer, deliverance and apostolic impartation for an army prepared to carry God's mandate to nations.
            </p>

            <div class="hero-actions">
                <a href="#register" class="btn-primary">Claim Free Pass ↗</a>
                <a href="#broadcast" class="btn-outline"><i class="fa-brands fa-youtube" style="color: #ef4444;"></i> Watch Live Stream</a>
            </div>

            <!-- Stacked Countdown -->
            <div class="countdown-strip" id="countdown">
                <div class="count-item"><div class="count-val" id="days">00</div><div class="count-lbl">Days</div></div>
                <div class="count-item"><div class="count-val" id="hours">00</div><div class="count-lbl">Hours</div></div>
                <div class="count-item"><div class="count-val" id="mins">00</div><div class="count-lbl">Minutes</div></div>
                <div class="count-item"><div class="count-val" id="secs">00</div><div class="count-lbl">Seconds</div></div>
            </div>
        </div>
    </section>

    <!-- Marquee Ticker -->
    <div class="ticker-wrap">
        <div class="ticker-move">
            <span class="ticker-text">Worship ✦ The Word ✦ Prayer ✦ Impartation ✦ Revival ✦ Flaming Fire ✦ Healing ✦ Free Medicals ✦ Free Feeding ✦ Empowerment ✦ &nbsp;</span>
            <span class="ticker-text">Worship ✦ The Word ✦ Prayer ✦ Impartation ✦ Revival ✦ Flaming Fire ✦ Healing ✦ Free Medicals ✦ Free Feeding ✦ Empowerment ✦ &nbsp;</span>
        </div>
    </div>

    <!-- 01 — The Gathering -->
    <section id="about">
        <div class="container">
            <div class="sec-number">01 — The gathering</div>
            <h2 class="sec-heading">A mantle is not admired from a distance.<br>It is received, carried and released.</h2>
            <p class="sec-sub">
                The Acts of the Holy Spirit Convention is a catalytic gathering for believers, ministers and leaders who desire deeper consecration, clarity and power for their assignment.
            </p>

            <div class="pillars-grid">
                <div class="pillar-card">
                    <div class="pillar-idx">01</div>
                    <div class="pillar-name">The Word</div>
                    <div class="pillar-desc">Christ-centred apostolic teaching that brings conviction, depth and sovereign direction.</div>
                </div>
                <div class="pillar-card">
                    <div class="pillar-idx">02</div>
                    <div class="pillar-name">Worship &amp; Fire</div>
                    <div class="pillar-desc">Unhurried moments of corporate worship and wholehearted surrender under flaming fire.</div>
                </div>
                <div class="pillar-card">
                    <div class="pillar-idx">03</div>
                    <div class="pillar-name">Prayer &amp; Deliverance</div>
                    <div class="pillar-desc">Focused intercession, healing ministry and supernatural deliverance for all attendees.</div>
                </div>
                <div class="pillar-card">
                    <div class="pillar-idx">04</div>
                    <div class="pillar-name">Welfare &amp; Free Meals</div>
                    <div class="pillar-desc">Commissioning moments with free medical checkups, free daily feeding and community care.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 02 — Ministers -->
    <section id="ministers">
        <div class="container">
            <div class="sec-number">02 — Ministers</div>
            <h2 class="sec-heading">Voices for this moment</h2>
            <p class="sec-sub">Seasoned ministers, one gathering, one clear mandate: equipping believers to take the world for Christ.</p>

            <div class="ministers-grid">
                <div class="minister-card">
                    <div class="minister-role">Convention Host</div>
                    <div class="minister-name">Pastor Jeremiah Oyekale</div>
                    <div class="minister-tagline">General Overseer &amp; Convener</div>
                </div>
                <div class="minister-card">
                    <div class="minister-role">Co-Host</div>
                    <div class="minister-name">Pastor (Mrs) Hannah Oyekale</div>
                    <div class="minister-tagline">Minister of Grace &amp; Empowerment</div>
                </div>
                <div class="minister-card">
                    <div class="minister-role">Lead Minister</div>
                    <div class="minister-name"><?= htmlspecialchars($settings['lead_minister'] ?? 'Pastor J. O Oyekale') ?></div>
                    <div class="minister-tagline">Apostolic Impartation &amp; Signs</div>
                </div>
                <div class="minister-card">
                    <div class="minister-role">Guest Faculty</div>
                    <div class="minister-name">Anointed Ministers of God</div>
                    <div class="minister-tagline">Special Ministers &amp; Marriage Seminar Speakers</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 03 — Programme -->
    <section id="programme">
        <div class="container">
            <div class="sec-number">03 — Programme</div>
            <h2 class="sec-heading">Seven days.<br>Multiple encounters.</h2>
            <p class="sec-sub">Every day is built around focused sessions: morning seminars, evening revival hour, and night vigils.</p>

            <div class="tab-nav">
                <button class="tab-btn active" onclick="switchDay(0, this)">Mon 5 Oct</button>
                <button class="tab-btn" onclick="switchDay(1, this)">Tue 6 Oct</button>
                <button class="tab-btn" onclick="switchDay(2, this)">Wed 7 Oct</button>
                <button class="tab-btn" onclick="switchDay(3, this)">Thu 8 Oct</button>
                <button class="tab-btn" onclick="switchDay(4, this)" style="border-color:var(--gold); color:var(--gold);">Fri 9 Oct (DEAL DAY)</button>
                <button class="tab-btn" onclick="switchDay(5, this)">Sun 11 Oct (Thanksgiving)</button>
            </div>

            <div id="scheduleTabContent"></div>
        </div>
    </section>

    <!-- 04 — Venue -->
    <section id="venue">
        <div class="container">
            <div class="sec-number">04 — Venue &amp; Logistics</div>
            <div class="venue-grid">
                <div class="venue-address-box">
                    <h2 class="sec-heading" style="font-size: 1.8rem; margin-bottom: 0.6rem;">One city.<br>One gathering.</h2>
                    <p style="font-size: 1.05rem; color: var(--gold); font-weight: 700; margin-bottom: 6px;">
                        The Acts of the Holy Spirit Christian Assembly
                    </p>
                    <p style="color: var(--text-muted); line-height: 1.7; font-size: 0.95rem;" id="venueAddressText">
                        <?= nl2br(htmlspecialchars($venue_address_text)) ?>
                    </p>
                    
                    <div class="address-actions">
                        <button type="button" class="btn-copy-addr" onclick="copyAddress()">
                            <i class="fa-regular fa-copy"></i> Copy Address
                        </button>
                        <a href="https://maps.google.com/?q=Heritage+Hotel+junction+Ogo+Oluwa+Osogbo" target="_blank" class="btn-outline" style="padding: 9px 20px; font-size: 0.82rem;">
                            <i class="fa-solid fa-diamond-turn-right"></i> Open in Maps
                        </a>
                    </div>
                </div>

                <div style="background: var(--bg-card); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid var(--border); border-radius: 16px; padding: 2rem; box-shadow: var(--shadow-subtle);">
                    <div style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 2px; color: var(--gold); margin-bottom: 1.2rem; font-weight: 800;">
                        Attendee Guide
                    </div>
                    <div style="margin-bottom: 1.2rem;">
                        <strong style="color: var(--text-main); font-size: 0.95rem;">01. Priority Seating</strong>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 2px;">Doors open 30 minutes before every plenary. Registered delegates have priority entry.</p>
                    </div>
                    <div style="margin-bottom: 1.2rem;">
                        <strong style="color: var(--text-main); font-size: 0.95rem;">02. Complimentary Meals &amp; Medicals</strong>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 2px;">Daily feeding and certified health consultations are freely provided on ground.</p>
                    </div>
                    <div>
                        <strong style="color: var(--text-main); font-size: 0.95rem;">03. Save Pass To Phone</strong>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 2px;">Complete the form below to download your pass as PDF, PNG or WhatsApp.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 05 — Broadcast & YouTube Live Stream Player -->
    <section id="broadcast">
        <div class="container" style="text-align: center;">
            <div class="sec-number">05 — Live Broadcast</div>
            <h2 class="sec-heading">Join the room<br>from anywhere.</h2>
            <p class="sec-sub" style="margin-left: auto; margin-right: auto;">
                Watch today's sessions live from anywhere in the world and experience the move of the Holy Spirit.
            </p>
            
            <div class="broadcast-card-wrap">
                <?php if ($yt_embed_url && $is_live_now): ?>
                    <!-- LIVE NOW ACTIVE STATE -->
                    <div class="live-stream-header">
                        <div class="live-badge-glow">
                            <span class="red-dot-pulse"></span> LIVE BROADCAST
                        </div>
                        <div style="font-size: 0.82rem; color: var(--gold); font-weight: 700;">
                            <i class="fa-solid fa-signal"></i> Streaming Worldwide
                        </div>
                    </div>

                    <!-- Responsive 16:9 YouTube Embed Video Player -->
                    <div class="video-player-container">
                        <iframe 
                            src="<?= htmlspecialchars($yt_embed_url) ?>" 
                            title="<?= htmlspecialchars($livestream_title) ?>"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                            allowfullscreen>
                        </iframe>
                    </div>

                    <div class="broadcast-meta-strip">
                        <div style="font-weight: 700; color: #fff; font-size: 0.95rem; text-align: left;">
                            <i class="fa-brands fa-youtube" style="color: #ef4444; margin-right: 6px;"></i>
                            <?= htmlspecialchars($livestream_title) ?>
                        </div>
                        <div>
                            <a href="<?= htmlspecialchars($raw_yt_url) ?>" target="_blank" class="btn-outline" style="padding: 6px 14px; font-size: 0.78rem;">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Open in YouTube App
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- UPCOMING / STANDBY STATE -->
                    <div style="padding: 2rem 1rem;">
                        <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px; color: var(--gold); margin-bottom: 10px; font-weight: 800;">
                            Next Live Session
                        </div>
                        <h3 style="font-size: clamp(1.2rem, 3vw, 1.6rem); color: #fff; margin-bottom: 1.2rem; font-family: 'Cinzel', serif;">
                            <?= htmlspecialchars($livestream_title) ?>
                        </h3>
                        <p style="color: var(--text-muted); font-size: 0.92rem; max-width: 550px; margin: 0 auto 2rem;">
                            The broadcast player will activate automatically once the session begins. Claim your pass to receive live streaming alerts.
                        </p>
                        <div style="display: flex; justify-content: center; gap: 14px; flex-wrap: wrap;">
                            <a href="#register" class="btn-primary">
                                <i class="fa-solid fa-bell"></i> Notify Me When Live
                            </a>
                            <?php if ($raw_yt_url): ?>
                                <a href="<?= htmlspecialchars($raw_yt_url) ?>" target="_blank" class="btn-outline">
                                    <i class="fa-brands fa-youtube" style="color:#ef4444;"></i> Visit Official Channel
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- 06 — FAQ -->
    <section id="faq">
        <div class="container" style="max-width: 800px;">
            <div class="sec-number">06 — FAQ</div>
            <h2 class="sec-heading">Everything you need<br>before you arrive.</h2>
            
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-q">Who can attend this convention? <span class="faq-icon">+</span></div>
                <div class="faq-a">The gathering is open to believers, ministers, ministry workers, young leaders and everyone hungry for spiritual growth, revival and kingdom power.</div>
            </div>

            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-q">What time are the sessions? <span class="faq-icon">+</span></div>
                <div class="faq-a">Sessions hold daily: Morning Seminars (10:00 AM), Evening Revival Hour (5:00 PM), and Night Vigil (11:00 PM) from 5 to 11 October 2026.</div>
            </div>

            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-q">Where is the venue? <span class="faq-icon">+</span></div>
                <div class="faq-a">38 Kareem Adetunji Street, beside Diekola house, Fomwan Roundabout, Heritage Hotel junction, Ogo-Oluwa, Osogbo, Nigeria.</div>
            </div>

            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-q">Are feeding and medical checkups free? <span class="faq-icon">+</span></div>
                <div class="faq-a">Yes. Free medical consultations and daily meals are provided freely for all participants on site.</div>
            </div>

            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-q">Do I need to register before attending? <span class="faq-icon">+</span></div>
                <div class="faq-a">Yes. Please complete the registration form below so the event team can plan for seating, feeding, and send essential information.</div>
            </div>
        </div>
    </section>

    <!-- 07 — Registration -->
    <section id="register">
        <div class="container">
            <div class="sec-number">07 — Registration</div>
            
            <div class="reg-split">
                <div class="reg-meta">
                    <h2 style="font-family: 'Cinzel', serif; font-size: clamp(1.6rem, 3.5vw, 2.1rem); color: var(--text-main); line-height: 1.15; margin-bottom: 0.8rem;">Your moment has come.<br>Be present.</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.6rem; line-height: 1.6;">Register to attend The Convention 2026 and receive your digital access pass, venue pack, and priority seat number.</p>

                    <div class="reg-meta-item">
                        <div class="reg-meta-lbl">Convention Dates</div>
                        <div class="reg-meta-val"><?= htmlspecialchars($settings['event_dates'] ?? '5—11 Oct 2026') ?></div>
                    </div>
                    <div class="reg-meta-item">
                        <div class="reg-meta-lbl">Session Timings</div>
                        <div class="reg-meta-val">10:00 AM &bull; 5:00 PM &bull; 11:00 PM</div>
                    </div>
                    <div class="reg-meta-item">
                        <div class="reg-meta-lbl">City / Location</div>
                        <div class="reg-meta-val">Ogo-Oluwa, Osogbo, Nigeria</div>
                    </div>
                </div>

                <div class="reg-form-panel">
                    <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin-bottom: 4px;">Delegate Registration</h3>
                    <p style="color: var(--text-muted); font-size: 0.84rem;">Fill your details below to generate your access pass.</p>

                    <!-- PASS DISPLAY & EXPORT AREA -->
                    <?php if ($success_msg && $registered_user): ?>
                        <div class="pass-container" id="passContainer">
                            <div style="text-align: center; margin-bottom: 14px;">
                                <i class="fa-solid fa-circle-check" style="font-size: 2.4rem; color: #22c55e; margin-bottom: 6px;"></i>
                                <h4 style="font-size: 1.25rem; color: var(--text-main); font-weight: 800;">Pass Issued &amp; Saved</h4>
                                <p style="color: var(--text-muted); font-size: 0.84rem;"><?= $success_msg ?></p>
                            </div>

                            <!-- Digital Pass Badge -->
                            <div class="pass-badge" id="passBadge">
                                <div class="pass-badge-top-strip"></div>
                                <div class="pass-badge-header">
                                    <div>
                                        <div class="pass-badge-title"><?= htmlspecialchars($settings['convention_title'] ?? 'The Acts of the Holy Spirit Convention') ?></div>
                                        <div class="pass-badge-theme">Theme: <?= htmlspecialchars($settings['theme_title'] ?? 'Flaming Fire') ?></div>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="background: #e5a93c; color: #000; font-size: 0.65rem; font-weight: 800; padding: 4px 8px; border-radius: 4px; text-transform: uppercase;">
                                            Official Pass
                                        </span>
                                    </div>
                                </div>

                                <div class="pass-badge-body">
                                    <div>
                                        <div class="pass-badge-name"><?= htmlspecialchars($registered_user['name']) ?></div>
                                        <div class="pass-badge-meta">
                                            <strong>Phone:</strong> <?= htmlspecialchars($registered_user['phone']) ?><br>
                                            <strong>Location:</strong> <?= htmlspecialchars($registered_user['location']) ?><br>
                                            <strong>Attendance:</strong> <?= htmlspecialchars($registered_user['mode']) ?>
                                        </div>
                                    </div>

                                    <div class="pass-qr-box">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($registered_user['code'] . ' - ' . $registered_user['name']) ?>" alt="QR Pass" crossorigin="anonymous">
                                    </div>
                                </div>

                                <div style="margin-top: 14px;">
                                    <div class="pass-badge-code-wrap">
                                        <div class="pass-badge-code-lbl">Access Pass Code</div>
                                        <div class="pass-badge-code-val"><?= htmlspecialchars($registered_user['code']) ?></div>
                                    </div>
                                </div>

                                <div class="pass-badge-footer">
                                    <span>Date Issued: <?= $registered_user['date'] ?></span>
                                    <span>Venue: Osogbo, Nigeria</span>
                                </div>
                            </div>

                            <!-- Action Buttons Cluster (PDF, Image & WhatsApp Share) -->
                            <div class="pass-action-buttons">
                                <button type="button" onclick="downloadPassPDF('<?= $registered_user['code'] ?>')" class="btn-pass-action primary" id="btnPdf">
                                    <i class="fa-solid fa-file-pdf"></i> Save as PDF
                                </button>
                                <button type="button" onclick="downloadPassImage('<?= $registered_user['code'] ?>')" class="btn-pass-action" id="btnImg">
                                    <i class="fa-solid fa-image"></i> Save as Image
                                </button>
                                <a href="https://api.whatsapp.com/send?text=<?= urlencode("Hallelujah! I just registered for The Acts of the Holy Spirit Convention 2026. My Pass Code is " . $registered_user['code'] . ". See you in Osogbo!") ?>" target="_blank" class="btn-pass-action whatsapp">
                                    <i class="fa-brands fa-whatsapp"></i> Share Pass
                                </a>
                            </div>
                        </div>
                    <?php elseif ($error_msg): ?>
                        <div style="background: rgba(220,38,38,0.15); border: 1px solid #ef4444; color: #ef4444; padding: 12px 16px; border-radius: 10px; margin: 1rem 0; font-size: 0.88rem;">
                            <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error_msg) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="#register" id="regForm" onsubmit="handleFormSubmit()">
                        <div class="form-grid">
                            <div class="form-full">
                                <label class="form-lbl">Full Name *</label>
                                <div class="form-input-wrap">
                                    <i class="fa-regular fa-user"></i>
                                    <input type="text" name="fullname" class="form-input" placeholder="e.g. Samuel Ade" required>
                                </div>
                            </div>

                            <div>
                                <label class="form-lbl">Email Address *</label>
                                <div class="form-input-wrap">
                                    <i class="fa-regular fa-envelope"></i>
                                    <input type="email" name="email" class="form-input" placeholder="samuel@gmail.com" required>
                                </div>
                            </div>

                            <div>
                                <label class="form-lbl">WhatsApp Phone *</label>
                                <div class="form-input-wrap">
                                    <i class="fa-brands fa-whatsapp"></i>
                                    <input type="tel" name="phone" class="form-input" placeholder="08012345678" required>
                                </div>
                            </div>

                            <div class="form-full">
                                <label class="form-lbl">City &amp; State *</label>
                                <div class="form-input-wrap">
                                    <i class="fa-solid fa-location-dot"></i>
                                    <input type="text" name="location" class="form-input" placeholder="e.g. Osogbo, Osun State" required>
                                </div>
                            </div>

                            <div class="form-full">
                                <label class="form-lbl">Sessions Attending *</label>
                                <select name="attendance_mode" class="form-input">
                                    <option value="All seven days">All seven days (5—11 Oct 2026)</option>
                                    <option value="Friday 9th DEAL DAY only">Friday 9th DEAL DAY only (All-Night)</option>
                                    <option value="Sunday 11th Thanksgiving only">Sunday 11th Thanksgiving only</option>
                                    <option value="Online livestream">Online livestream only</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-lbl">Require Accommodation? *</label>
                                <select name="needs_accommodation" class="form-input">
                                    <option value="No">No</option>
                                    <option value="Yes">Yes</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-lbl">Free Medical Checkup? *</label>
                                <select name="medical_support" class="form-input">
                                    <option value="Yes">Yes (Complimentary)</option>
                                    <option value="No">No</option>
                                </select>
                            </div>

                            <div class="form-full">
                                <label class="form-lbl">Prayer Request / Special Note</label>
                                <textarea name="prayer_request" rows="2" class="form-input" placeholder="What are you trusting God for at this convention?"></textarea>
                            </div>

                            <div class="form-full">
                                <button type="submit" name="register_submit" id="btnSubmitForm" class="btn-submit-reg">
                                    Confirm Registration &amp; Get Pass →
                                </button>
                                <small style="display: block; color: var(--text-dim); font-size: 0.75rem; margin-top: 8px;">
                                    Fields marked * are required. Your access pass will be generated immediately.
                                </small>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h3 style="font-family: 'Cinzel', serif; font-size: 1.1rem; color: var(--text-main); margin-bottom: 4px;">
                        <?= htmlspecialchars($settings['ministry_name'] ?? 'The End Time Army of God') ?>
                    </h3>
                    <p style="color: var(--gold); font-size: 0.84rem; margin-bottom: 6px; font-weight: 700;">2026 Convention</p>
                    <p style="font-size: 0.84rem; color: var(--text-dim);"><?= htmlspecialchars($settings['motto_scripture'] ?? 'Be strong in the Lord and in the power of His might') ?></p>
                </div>

                <div class="footer-col">
                    <h4>Explore</h4>
                    <ul>
                        <li><a href="#about">About</a></li>
                        <li><a href="#ministers">Ministers</a></li>
                        <li><a href="#programme">Programme</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Attend</h4>
                    <ul>
                        <li><a href="#venue">Venue</a></li>
                        <li><a href="#broadcast">Live Stream</a></li>
                        <li><a href="#faq">FAQ</a></li>
                        <li><a href="#register">Register</a></li>
                        <li><a href="admin.php">Admin Portal</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Connect</h4>
                    <ul>
                        <li><a href="tel:<?= $settings['contact_phone_1'] ?? '08062875799' ?>">Tel: <?= htmlspecialchars($settings['contact_phone_1'] ?? '08062875799') ?></a></li>
                        <li><a href="tel:<?= $settings['contact_phone_2'] ?? '08067768904' ?>">Tel: <?= htmlspecialchars($settings['contact_phone_2'] ?? '08067768904') ?></a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div>&copy; 2026 <?= htmlspecialchars($settings['ministry_name'] ?? 'The End Time Army of God') ?>. All rights reserved.</div>
                <div><a href="#" style="color: var(--text-main); font-weight: 700;">Back to top ↑</a></div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Components -->
    <script>
        // 1. Mobile Menu Drawer Controller
        function toggleMobileMenu() {
            const drawer = document.getElementById('mobileDrawer');
            const icon = document.getElementById('menu-icon');
            drawer.classList.toggle('open');
            if (drawer.classList.contains('open')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-xmark');
            } else {
                icon.classList.remove('fa-xmark');
                icon.classList.add('fa-bars');
            }
        }

        function closeMobileMenu() {
            const drawer = document.getElementById('mobileDrawer');
            const icon = document.getElementById('menu-icon');
            if (drawer) drawer.classList.remove('open');
            if (icon) {
                icon.classList.remove('fa-xmark');
                icon.classList.add('fa-bars');
            }
        }

        // 2. Theme Switcher Logic
        function setAppTheme(theme) {
            localStorage.setItem('etag_theme', theme);
            document.documentElement.setAttribute('data-theme', theme);
            
            if (theme === 'auto') {
                const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.setAttribute('data-resolved-theme', systemDark ? 'dark' : 'light');
            } else {
                document.documentElement.setAttribute('data-resolved-theme', theme);
            }
            updateThemeButtons(theme);
        }

        function updateThemeButtons(theme) {
            document.querySelectorAll('.theme-btn').forEach(btn => btn.classList.remove('active'));
            const activeBtn = document.getElementById(`theme-btn-${theme}`);
            if (activeBtn) activeBtn.classList.add('active');
        }

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            const currentTheme = localStorage.getItem('etag_theme') || 'auto';
            if (currentTheme === 'auto') {
                document.documentElement.setAttribute('data-resolved-theme', e.matches ? 'dark' : 'light');
            }
        });

        const initialTheme = localStorage.getItem('etag_theme') || 'auto';
        updateThemeButtons(initialTheme);

        // 3. Countdown Timer
        const targetDate = new Date('<?= $settings['countdown_target'] ?? '2026-10-05 10:00:00' ?>').getTime();
        function updateTimer() {
            const diff = targetDate - new Date().getTime();
            if (diff > 0) {
                document.getElementById('days').innerText = String(Math.floor(diff / (1000 * 60 * 60 * 24))).padStart(2, '0');
                document.getElementById('hours').innerText = String(Math.floor((diff / (1000 * 60 * 60)) % 24)).padStart(2, '0');
                document.getElementById('mins').innerText = String(Math.floor((diff / 1000 / 60) % 60)).padStart(2, '0');
                document.getElementById('secs').innerText = String(Math.floor((diff / 1000) % 60)).padStart(2, '0');
            }
        }
        setInterval(updateTimer, 1000);
        updateTimer();

        // 4. Schedule Tabs Data
        const scheduleData = [
            [
                { time: '10:00 AM', badge: 'Morning Plenary', title: 'Opening Charge & Alignment', desc: 'Opening charge, conference registration & foundational Word.' },
                { time: '05:00 PM', badge: 'Revival Hour', title: 'Holy Ghost Ignition Service', desc: 'Worship, salvation charge, and Holy Ghost baptism.' },
                { time: '11:00 PM', badge: 'Night Vigil', title: 'Apostolic Midnight Intercession', desc: 'Midnight apostolic prayer and warfare intercession.' }
            ],
            [
                { time: '10:00 AM', badge: 'Leadership Seminar', title: 'Ministers & Workers Plenary', desc: 'Building ministers for enduring kingdom mandate and power.' },
                { time: '05:00 PM', badge: 'Revival Hour', title: 'Prophetic Deliverance Service', desc: 'Altar ministry of deliverance and prophetic empowerment.' },
                { time: '11:00 PM', badge: 'Night Vigil', title: 'Midnight Power Gathering', desc: 'Corporate warfare prayers and impartation.' }
            ],
            [
                { time: '10:00 AM', badge: 'Family Seminar', title: 'Kingdom Homes & Marriages', desc: 'Kingdom principles for solid families and home empowerment.' },
                { time: '05:00 PM', badge: 'Revival Hour', title: 'Signs & Wonders Revival', desc: 'Healing line, divine restorations and miracles.' },
                { time: '11:00 PM', badge: 'Night Vigil', title: 'Unbroken Consecration Vigil', desc: 'Consecration and unbroken prayer vigil.' }
            ],
            [
                { time: '10:00 AM', badge: 'Bible Study', title: 'Apostolic Word & Music Explosion', desc: 'Deep doctrinal study, praise explosion, and empowerment.' },
                { time: '05:00 PM', badge: 'Anointing Service', title: 'Revival & Holy Ghost Oil', desc: 'Special anointing service for all participants.' },
                { time: '11:00 PM', badge: 'Night Vigil', title: 'Pre-Deal Day Warfare', desc: 'Pre-Deal Day preparation prayers.' }
            ],
            [
                { time: '08:00 PM', badge: 'DEAL DAY (TILL DAWN)', title: 'Acts of the Holy Spirit Night', desc: 'An explosive all-night encounter of prophetic deliverance, raw Holy Ghost fire, and impartation.' }
            ],
            [
                { time: '10:00 AM', badge: 'GRAND FINALE', title: 'Acts of Thanksgiving & Victory', desc: 'Victory celebration, testimonies, Holy Communion and covenant blessing.' }
            ]
        ];

        function renderDay(index) {
            const container = document.getElementById('scheduleTabContent');
            const sessions = scheduleData[index];
            let html = '<div class="session-list">';
            sessions.forEach(s => {
                html += `
                    <div class="session-item">
                        <div class="session-time-col">
                            <div class="session-time">${s.time}</div>
                            <div class="session-period">Session</div>
                        </div>
                        <div class="session-info">
                            <span class="session-badge">${s.badge}</span>
                            <div class="session-title">${s.title}</div>
                            <div class="session-desc">${s.desc}</div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        function switchDay(index, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderDay(index);
        }

        renderDay(0);

        // 5. FAQ Accordion Toggle
        function toggleFaq(el) {
            el.classList.toggle('open');
        }

        // 6. Copy Venue Address to Clipboard
        function copyAddress() {
            const text = document.getElementById('venueAddressText').innerText;
            navigator.clipboard.writeText(text).then(() => {
                showToast('Address copied to clipboard!');
            }).catch(() => {
                showToast('Address ready on screen.');
            });
        }

        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.innerText = message;
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        // 7. Form Submission UX State
        function handleFormSubmit() {
            const btn = document.getElementById('btnSubmitForm');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating Your Pass...';
            btn.disabled = true;
        }

        // 8. Pass Download Engine (Image & PDF)
        function downloadPassImage(passCode) {
            const passElement = document.getElementById('passBadge');
            const btn = document.getElementById('btnImg');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

            html2canvas(passElement, {
                scale: 3,
                useCORS: true,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = `Convention-Pass-${passCode}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
                btn.innerHTML = originalText;
                showToast('Pass image downloaded successfully!');
            }).catch(err => {
                console.error(err);
                btn.innerHTML = originalText;
                alert('Could not save image. Please take a screenshot of your pass.');
            });
        }

        function downloadPassPDF(passCode) {
            const passElement = document.getElementById('passBadge');
            const btn = document.getElementById('btnPdf');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating PDF...';

            html2canvas(passElement, {
                scale: 3,
                useCORS: true,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({
                    orientation: 'landscape',
                    unit: 'mm',
                    format: [140, 95]
                });

                pdf.addImage(imgData, 'PNG', 5, 5, 130, 85);
                pdf.save(`Convention-Pass-${passCode}.pdf`);
                btn.innerHTML = originalText;
                showToast('PDF Pass generated and saved!');
            }).catch(err => {
                console.error(err);
                btn.innerHTML = originalText;
                alert('Could not generate PDF. Please use the Save as Image option.');
            });
        }
    </script>
</body>
</html>