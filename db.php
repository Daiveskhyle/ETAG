<?php
// Database Connection Configuration
$host = 'localhost';
$dbname = 'etag'; // Replace with your database name
$username = 'root';          // Replace with your DB username
$password = '';              // Replace with your DB password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

/**
 * Universal Settings Fetcher with Fallbacks
 */
function get_settings($pdo) {
    $settings = [
        'convention_title'  => 'The Acts of the Holy Spirit Convention 2026',
        'theme_title'       => 'Flaming Fire',
        'theme_scripture'   => 'Hebrews 1:7',
        'lead_minister'     => 'Pastor Jeremiah Oyekale',
        'ministry_name'     => 'The End Time Army of God',
        'motto_scripture'   => 'Be strong in the Lord and in the power of His might',
        'event_dates'       => '5 October — 11 October 2026',
        'countdown_target'  => '2026-10-05 10:00:00',
        'venue_address'     => '38 Kareem Adetunji Street, beside Diekola house, Fomwan Roundabout, Heritage Hotel junction, Ogo-Oluwa, Osogbo, Nigeria.',
        'contact_phone_1'   => '08062875799',
        'contact_phone_2'   => '08067768904',
        'youtube_live_url'  => '',
        'is_live_now'       => '0',
        'livestream_title'  => 'Official Live Broadcast — Acts of the Holy Spirit Convention 2026',
        'logo_url'          => '',
        'hero_bg_image'     => 'https://images.unsplash.com/photo-1519791883288-dc8bd696e667?auto=format&fit=crop&w=1920&q=80',
        'about_bg_image'    => 'https://images.unsplash.com/photo-1438232992991-995b7058bbb3?auto=format&fit=crop&w=1920&q=80',
        'venue_bg_image'    => 'https://images.unsplash.com/photo-1548625361-073c68ea8ea9?auto=format&fit=crop&w=1920&q=80',
        'broadcast_bg_image'=> 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?auto=format&fit=crop&w=1920&q=80',
        'register_bg_image' => 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=1920&q=80'
    ];

    // 1. Try reading from event_settings (Key-Value table)
    try {
        $rows = $pdo->query("SELECT setting_key, setting_value FROM event_settings")->fetchAll();
        if (!empty($rows)) {
            foreach ($rows as $r) {
                $settings[$r['setting_key']] = $r['setting_value'];
            }
            return $settings;
        }
    } catch (Exception $e) {}

    // 2. Try reading from settings (Column-based table)
    try {
        $row = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch();
        if ($row) {
            return array_merge($settings, array_filter($row, function($val) {
                return $val !== null;
            }));
        }
    } catch (Exception $e) {}

    return $settings;
}