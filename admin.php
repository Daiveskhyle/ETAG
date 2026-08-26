<?php
require_once 'db.php';

$msg = $_GET['msg'] ?? '';
$err = '';

// Delete Attendee Handler
if (isset($_GET['delete_reg'])) {
    $del_id = intval($_GET['delete_reg']);
    try {
        $stmt = $pdo->prepare("DELETE FROM registrations WHERE id = ?");
        $stmt->execute([$del_id]);
        header("Location: admin.php?msg=Attendee+removed+successfully");
        exit;
    } catch (PDOException $e) {
        $err = "Error deleting attendee: " . $e->getMessage();
    }
}

// Save Updated Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!empty($_POST['settings']) && is_array($_POST['settings'])) {
        foreach ($_POST['settings'] as $key => $val) {
            $val = is_string($val) ? trim($val) : $val;
            
            // Try updating event_settings (key-value structure)
            try {
                $stmt = $pdo->prepare("UPDATE event_settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([$val, $key]);
            } catch (Exception $e) {
                // Fallback for settings table structure
                try {
                    $stmt = $pdo->prepare("UPDATE settings SET $key = ? WHERE id = 1");
                    $stmt->execute([$val]);
                } catch (Exception $e2) {}
            }
        }
        
        // Handle is_live_now toggle checkbox
        $is_live = isset($_POST['settings']['is_live_now']) ? 1 : 0;
        try {
            $pdo->prepare("UPDATE event_settings SET setting_value = ? WHERE setting_key = 'is_live_now'")->execute([$is_live]);
        } catch(Exception $e) {
            try {
                $pdo->prepare("UPDATE settings SET is_live_now = ? WHERE id = 1")->execute([$is_live]);
            } catch(Exception $e2) {}
        }

        header("Location: admin.php?msg=Settings+saved+successfully!");
        exit;
    }
}

// Fetch Registrations & Settings
$attendees = [];
try {
    $attendees = $pdo->query("SELECT * FROM registrations ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $err = "Could not fetch attendees: " . $e->getMessage();
}

$settings = get_settings($pdo);

// Calculate Metrics
$total_attendees = count($attendees);
$total_accomm = 0;
$total_medical = 0;
$total_online = 0;

foreach ($attendees as $a) {
    if (strtolower($a['needs_accommodation'] ?? '') === 'yes') $total_accomm++;
    if (strtolower($a['medical_support'] ?? '') === 'yes') $total_medical++;
    if (stripos($a['attendance_mode'] ?? '', 'online') !== false) $total_online++;
}
$total_onsite = $total_attendees - $total_online;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ETAG Convention — Admin Command Center</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Cinzel:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --bg-body: #06090e;
            --bg-card: #0d121d;
            --bg-card-hover: #131a29;
            --bg-input: #080c14;
            --border: rgba(255, 255, 255, 0.08);
            --border-active: rgba(245, 158, 11, 0.45);
            --gold: #f59e0b;
            --gold-glow: rgba(245, 158, 11, 0.2);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-dim: #64748b;
            --danger: #ef4444;
            --success: #22c55e;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-body); color: var(--text-main); line-height: 1.5; padding-bottom: 4rem; }
        a { color: inherit; text-decoration: none; }

        .container { max-width: 1300px; margin: 0 auto; padding: 0 1.5rem; }

        /* Top Bar */
        .topbar {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(16px);
        }
        .topbar-inner { display: flex; justify-content: space-between; align-items: center; }
        .brand-title { font-family: 'Cinzel', serif; font-size: 1.15rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 8px; }
        .brand-title span { color: var(--gold); }
        .topbar-actions { display: flex; gap: 12px; align-items: center; }

        /* Buttons */
        .btn {
            background: var(--gold);
            color: #07090e;
            font-weight: 700;
            font-size: 0.84rem;
            padding: 8px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn:hover { background: #fbbf24; transform: translateY(-1px); box-shadow: 0 4px 14px var(--gold-glow); }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.84rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-outline:hover { border-color: var(--gold); color: var(--text-main); }
        .btn-danger { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #ef4444; padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; cursor: pointer; }
        .btn-danger:hover { background: #ef4444; color: #fff; }
        .btn-whatsapp { background: #22c55e; color: #fff; padding: 6px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }

        /* Alerts */
        .alert-success { background: rgba(34, 197, 94, 0.15); border: 1px solid var(--success); color: #4ade80; padding: 12px 18px; border-radius: 10px; margin: 1.5rem 0; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; }
        .alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #f87171; padding: 12px 18px; border-radius: 10px; margin: 1.5rem 0; font-size: 0.9rem; }

        /* Metrics Grid */
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin: 1.8rem 0; }
        .metric-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.4rem;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.25s;
        }
        .metric-card:hover { border-color: var(--border-active); transform: translateY(-2px); }
        .metric-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .metric-val { font-size: 1.8rem; font-weight: 800; line-height: 1; color: var(--text-main); font-family: 'Cinzel', serif; }
        .metric-lbl { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-top: 4px; font-weight: 600; }

        /* Card Panels */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.8rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.4rem; flex-wrap: wrap; gap: 12px; }
        .card-title { font-family: 'Cinzel', serif; font-size: 1.25rem; font-weight: 700; color: var(--gold); display: flex; align-items: center; gap: 8px; }

        /* Tabs Nav */
        .tab-nav-bar { display: flex; gap: 8px; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 1.4rem; overflow-x: auto; }
        .tab-link {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-muted);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s;
        }
        .tab-link.active, .tab-link:hover { background: var(--gold); color: #07090e; border-color: var(--gold); }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* Form Inputs */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-full { grid-column: 1 / -1; }
        label { font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        input[type="text"], input[type="url"], textarea, select {
            width: 100%;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 14px;
            color: var(--text-main);
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s;
        }
        input:focus, textarea:focus, select:focus { border-color: var(--gold); }

        /* Toggle Switch */
        .switch-wrap { display: flex; align-items: center; gap: 12px; background: var(--bg-input); padding: 12px 16px; border-radius: 10px; border: 1px solid var(--border); width: fit-content; }
        .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #334155; transition: .3s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
        input:checked + .slider { background-color: #ef4444; }
        input:checked + .slider:before { transform: translateX(20px); }

        /* Table & Controls */
        .table-controls { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 1rem; flex-wrap: wrap; }
        .search-box {
            position: relative;
            min-width: 280px;
            flex-grow: 1;
            max-width: 450px;
        }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-dim); }
        .search-box input { padding-left: 36px; }

        .table-responsive { width: 100%; overflow-x: auto; border-radius: 12px; border: 1px solid var(--border); }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #080c14; color: var(--gold); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 12px 14px; border-bottom: 1px solid var(--border); font-weight: 800; }
        td { padding: 12px 14px; border-bottom: 1px solid var(--border); font-size: 0.86rem; color: var(--text-muted); }
        tr:hover td { background: var(--bg-card-hover); color: var(--text-main); }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; }
        .badge-gold { background: rgba(245, 158, 11, 0.15); color: var(--gold); border: 1px solid var(--gold); }
        .badge-green { background: rgba(34, 197, 94, 0.15); color: var(--success); }

        /* Modal */
        .modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; padding: 1rem; }
        .modal-box { background: var(--bg-card-solid); border: 1px solid var(--border); border-radius: 16px; padding: 2rem; max-width: 500px; width: 100%; max-height: 90vh; overflow-y: auto; position: relative; }
        .modal-close { position: absolute; right: 1.2rem; top: 1.2rem; background: transparent; border: none; color: var(--text-muted); font-size: 1.2rem; cursor: pointer; }

        @media(max-width: 768px) {
            .table-controls { flex-direction: column; align-items: stretch; }
            .search-box { max-width: 100%; }
        }
    </style>
</head>
<body>

<!-- Top Navigation -->
<div class="topbar">
    <div class="container topbar-inner">
        <div class="brand-title">
            <i class="fa-solid fa-shield-halved" style="color: var(--gold);"></i>
            ETAG <span>Convention 2026</span>
        </div>
        <div class="topbar-actions">
            <a href="index.php" target="_blank" class="btn-outline">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> View Website
            </a>
            <a href="index.php" class="btn-outline">
                <i class="fa-solid fa-right-from-bracket"></i> Exit
            </a>
        </div>
    </div>
</div>

<div class="container" style="margin-top: 1.5rem;">

    <?php if ($msg): ?>
        <div class="alert-success">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div class="alert-error">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($err) ?>
        </div>
    <?php endif; ?>

    <!-- Metrics Dashboard -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--gold);">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="metric-val"><?= $total_attendees ?></div>
                <div class="metric-lbl">Total Registrations</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa;">
                <i class="fa-solid fa-hotel"></i>
            </div>
            <div>
                <div class="metric-val"><?= $total_accomm ?></div>
                <div class="metric-lbl">Need Accommodation</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon" style="background: rgba(34, 197, 94, 0.15); color: #4ade80;">
                <i class="fa-solid fa-kit-medical"></i>
            </div>
            <div>
                <div class="metric-val"><?= $total_medical ?></div>
                <div class="metric-lbl">Medical Checkups</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon" style="background: rgba(168, 85, 247, 0.15); color: #c084fc;">
                <i class="fa-solid fa-location-dot"></i>
            </div>
            <div>
                <div class="metric-val"><?= $total_onsite ?> / <?= $total_online ?></div>
                <div class="metric-lbl">On-Site vs Online</div>
            </div>
        </div>
    </div>

    <!-- Edit Convention Settings Panel -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-sliders"></i> Event Configuration &amp; Broadcast Center
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="tab-nav-bar">
            <button class="tab-link active" onclick="switchTab('tab-event', this)"><i class="fa-solid fa-calendar-check"></i> Event Details</button>
            <button class="tab-link" onclick="switchTab('tab-broadcast', this)"><i class="fa-brands fa-youtube" style="color: #ef4444;"></i> Live Broadcast</button>
            <button class="tab-link" onclick="switchTab('tab-venue', this)"><i class="fa-solid fa-location-dot"></i> Venue &amp; Contacts</button>
            <button class="tab-link" onclick="switchTab('tab-media', this)"><i class="fa-solid fa-image"></i> Images &amp; Logo</button>
        </div>

        <form method="POST">
            <!-- TAB 1: Event Details -->
            <div class="tab-panel active" id="tab-event">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Convention Main Title</label>
                        <input type="text" name="settings[convention_title]" value="<?= htmlspecialchars($settings['convention_title'] ?? 'The Acts of the Holy Spirit Convention 2026') ?>">
                    </div>
                    <div class="form-group">
                        <label>Theme Title</label>
                        <input type="text" name="settings[theme_title]" value="<?= htmlspecialchars($settings['theme_title'] ?? 'Flaming Fire') ?>">
                    </div>
                    <div class="form-group">
                        <label>Lead Minister / Apostolic Host</label>
                        <input type="text" name="settings[lead_minister]" value="<?= htmlspecialchars($settings['lead_minister'] ?? 'Pastor Jeremiah Oyekale') ?>">
                    </div>
                    <div class="form-group">
                        <label>Event Dates Display</label>
                        <input type="text" name="settings[event_dates]" value="<?= htmlspecialchars($settings['event_dates'] ?? '5 October — 11 October 2026') ?>">
                    </div>
                    <div class="form-group">
                        <label>Countdown Target (YYYY-MM-DD HH:MM:SS)</label>
                        <input type="text" name="settings[countdown_target]" value="<?= htmlspecialchars($settings['countdown_target'] ?? '2026-10-05 10:00:00') ?>">
                    </div>
                    <div class="form-group">
                        <label>Theme Scripture</label>
                        <input type="text" name="settings[theme_scripture]" value="<?= htmlspecialchars($settings['theme_scripture'] ?? 'Hebrews 1:7') ?>">
                    </div>
                </div>
            </div>

            <!-- TAB 2: Live Broadcast Controller -->
            <div class="tab-panel" id="tab-broadcast">
                <div class="form-grid">
                    <div class="form-full">
                        <div class="switch-wrap">
                            <label class="switch">
                                <input type="checkbox" name="settings[is_live_now]" value="1" <?= (!empty($settings['is_live_now']) && $settings['is_live_now'] == 1) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                            <div>
                                <strong style="font-size: 0.95rem;">Broadcasting Live Now?</strong>
                                <p style="font-size: 0.8rem; color: var(--text-dim);">When enabled, the interactive video player displays automatically on the homepage.</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group form-full">
                        <label>YouTube Live Stream URL (Accepts any YouTube watch/share/live link)</label>
                        <input type="url" name="settings[youtube_live_url]" placeholder="https://www.youtube.com/watch?v=..." value="<?= htmlspecialchars($settings['youtube_live_url'] ?? '') ?>">
                    </div>

                    <div class="form-group form-full">
                        <label>Livestream Title / Banner Description</label>
                        <input type="text" name="settings[livestream_title]" placeholder="e.g. Day 1 Revival Hour — Flaming Fire" value="<?= htmlspecialchars($settings['livestream_title'] ?? 'Official Broadcast — Acts of the Holy Spirit Convention 2026') ?>">
                    </div>
                </div>
            </div>

            <!-- TAB 3: Venue & Contacts -->
            <div class="tab-panel" id="tab-venue">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Contact Phone 1</label>
                        <input type="text" name="settings[contact_phone_1]" value="<?= htmlspecialchars($settings['contact_phone_1'] ?? '08062875799') ?>">
                    </div>
                    <div class="form-group">
                        <label>Contact Phone 2</label>
                        <input type="text" name="settings[contact_phone_2]" value="<?= htmlspecialchars($settings['contact_phone_2'] ?? '08067768904') ?>">
                    </div>
                    <div class="form-group form-full">
                        <label>Venue Address</label>
                        <textarea name="settings[venue_address]" rows="2"><?= htmlspecialchars($settings['venue_address'] ?? '38 Kareem Adetunji Street, beside Diekola house, Fomwan Roundabout, Heritage Hotel junction, Ogo-Oluwa, Osogbo, Nigeria.') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- TAB 4: Media & Backgrounds -->
            <div class="tab-panel" id="tab-media">
                <div class="form-grid">
                    <div class="form-group form-full">
                        <label>Church / Event Logo URL</label>
                        <input type="text" name="settings[logo_url]" placeholder="assets/img/logo.png" value="<?= htmlspecialchars($settings['logo_url'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Hero Background Image URL</label>
                        <input type="text" name="settings[hero_bg_image]" value="<?= htmlspecialchars($settings['hero_bg_image'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>About / Gathering Background URL</label>
                        <input type="text" name="settings[about_bg_image]" value="<?= htmlspecialchars($settings['about_bg_image'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Venue Background Image URL</label>
                        <input type="text" name="settings[venue_bg_image]" value="<?= htmlspecialchars($settings['venue_bg_image'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Registration Section Background URL</label>
                        <input type="text" name="settings[register_bg_image]" value="<?= htmlspecialchars($settings['register_bg_image'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div style="margin-top: 1.5rem; display: flex; gap: 10px;">
                <button type="submit" name="save_settings" class="btn">
                    <i class="fa-solid fa-floppy-disk"></i> Save All Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Registered Attendees Database Table -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-address-book"></i> Registered Attendees
            </div>
            <div>
                <button type="button" class="btn-outline" onclick="exportTableToCSV('ETAG-Convention-Attendees.csv')">
                    <i class="fa-solid fa-file-excel" style="color: #22c55e;"></i> Export CSV
                </button>
            </div>
        </div>

        <!-- Table Filters & Search -->
        <div class="table-controls">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="attendeeSearch" placeholder="Search by name, pass code, phone or location..." onkeyup="filterTable()">
            </div>
            <div>
                <select id="modeFilter" onchange="filterTable()" style="width: auto;">
                    <option value="">All Attendance Modes</option>
                    <option value="All seven days">All 7 Days</option>
                    <option value="Friday 9th">Friday 9th (Deal Day)</option>
                    <option value="Sunday 11th">Sunday 11th (Thanksgiving)</option>
                    <option value="Online">Online Livestream</option>
                </select>
            </div>
        </div>

        <!-- Table Responsive Container -->
        <div class="table-responsive">
            <table id="attendeesTable">
                <thead>
                    <tr>
                        <th>Pass ID</th>
                        <th>Attendee Name</th>
                        <th>Phone / WhatsApp</th>
                        <th>Email</th>
                        <th>Location</th>
                        <th>Mode</th>
                        <th>Accomm.</th>
                        <th>Medical</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendees)): ?>
                        <tr><td colspan="9" style="text-align: center; padding: 2rem;">No registered attendees found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($attendees as $row): ?>
                            <tr>
                                <td><span class="badge badge-gold"><?= htmlspecialchars($row['reg_code']) ?></span></td>
                                <td><strong style="color: var(--text-main);"><?= htmlspecialchars($row['fullname']) ?></strong></td>
                                <td>
                                    <?= htmlspecialchars($row['phone']) ?>
                                    <a href="https://wa.me/234<?= ltrim(preg_replace('/[^0-9]/', '', $row['phone']), '0') ?>" target="_blank" class="btn-whatsapp" title="Chat on WhatsApp" style="margin-left: 4px;">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['location']) ?></td>
                                <td><?= htmlspecialchars($row['attendance_mode']) ?></td>
                                <td>
                                    <?php if(strtolower($row['needs_accommodation'] ?? '') === 'yes'): ?>
                                        <span class="badge badge-green">Yes</span>
                                    <?php else: ?>
                                        <span>No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(strtolower($row['medical_support'] ?? '') === 'yes'): ?>
                                        <span class="badge badge-green">Yes</span>
                                    <?php else: ?>
                                        <span>No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <button type="button" class="btn-outline" style="padding: 4px 8px; font-size: 0.72rem;" onclick="viewAttendee(<?= htmlspecialchars(json_encode($row)) ?>)">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <a href="admin.php?delete_reg=<?= $row['id'] ?>" class="btn-danger" onclick="return confirm('Are you sure you want to remove <?= addslashes($row['fullname']) ?>?');">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Attendee Details Modal -->
<div class="modal-backdrop" id="detailsModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
        <h3 style="font-family: 'Cinzel', serif; color: var(--gold); margin-bottom: 1rem;">Attendee Details</h3>
        <div id="modalContent" style="font-size: 0.9rem; line-height: 1.8;"></div>
    </div>
</div>

<script>
    // Tab Controller
    function switchTab(tabId, btn) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.tab-link').forEach(b => b.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        btn.classList.add('active');
    }

    // Real-Time Table Filter
    function filterTable() {
        const searchInput = document.getElementById('attendeeSearch').value.toLowerCase();
        const modeFilter = document.getElementById('modeFilter').value.toLowerCase();
        const table = document.getElementById('attendeesTable');
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName('td');
            if (cells.length < 8) continue;
            
            const rowText = rows[i].innerText.toLowerCase();
            const modeText = cells[5].innerText.toLowerCase();

            const matchesSearch = rowText.indexOf(searchInput) > -1;
            const matchesMode = modeFilter === '' || modeText.indexOf(modeFilter) > -1;

            if (matchesSearch && matchesMode) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }

    // Attendee Modal Viewer
    function viewAttendee(data) {
        const content = document.getElementById('modalContent');
        content.innerHTML = `
            <p><strong>Pass Code:</strong> <span style="color: var(--gold); font-weight: 800;">${data.reg_code}</span></p>
            <p><strong>Full Name:</strong> ${data.fullname}</p>
            <p><strong>Email:</strong> ${data.email}</p>
            <p><strong>Phone:</strong> ${data.phone}</p>
            <p><strong>Location:</strong> ${data.location}</p>
            <p><strong>Attendance Mode:</strong> ${data.attendance_mode}</p>
            <p><strong>Needs Accommodation:</strong> ${data.needs_accommodation}</p>
            <p><strong>Medical Support:</strong> ${data.medical_support}</p>
            <p><strong>Registered On:</strong> ${data.created_at || 'N/A'}</p>
            <div style="margin-top: 10px; padding: 10px; background: var(--bg-input); border-radius: 8px;">
                <strong>Prayer Request / Note:</strong>
                <p style="color: var(--text-muted); margin-top: 4px;">${data.prayer_request || 'No special prayer request submitted.'}</p>
            </div>
        `;
        document.getElementById('detailsModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('detailsModal').style.display = 'none';
    }

    // Export Table to CSV
    function exportTableToCSV(filename) {
        const rows = document.querySelectorAll("#attendeesTable tr");
        let csv = [];
        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll("td, th");
            for (let j = 0; j < cols.length - 1; j++) { // Exclude actions column
                let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s+)/gm, ' ');
                data = data.replace(/"/g, '""');
                row.push('"' + data + '"');
            }
            csv.push(row.join(","));
        }
        const csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
        const downloadLink = document.createElement("a");
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
</script>
</body>
</html>