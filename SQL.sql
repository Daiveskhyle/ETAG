CREATE DATABASE IF NOT EXISTS `etag` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `etag`;

-- 1. Attendee Registrations Table
CREATE TABLE IF NOT EXISTS `registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reg_code` VARCHAR(20) NOT NULL UNIQUE,
    `fullname` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(25) NOT NULL,
    `gender` ENUM('Male', 'Female') NOT NULL,
    `location` VARCHAR(150) NOT NULL,
    `attendance_mode` ENUM('In-Person (Osogbo)', 'Online Stream') DEFAULT 'In-Person (Osogbo)',
    `needs_accommodation` ENUM('Yes', 'No') DEFAULT 'No',
    `medical_support` ENUM('Yes', 'No') DEFAULT 'No',
    `prayer_request` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Dynamic Event Settings Table (Makes all site text fully editable)
CREATE TABLE IF NOT EXISTS `event_settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT NOT NULL
) ENGINE=InnoDB;

-- Seed Default Convention Data
INSERT INTO `event_settings` (`setting_key`, `setting_value`) VALUES
('ministry_name', 'The End Time Army of God Ministry'),
('ministry_alias', 'The Acts of the Holy Spirit Christian Assembly'),
('convention_title', 'Acts of the Holy Spirit Convention 2026'),
('theme_title', 'Flaming Fire'),
('theme_scripture', 'Psalm 104:4'),
('motto_scripture', 'Be strong in the Lord and in the power of His might - Eph. 6:10-18'),
('event_dates', '5th – 11th October, 2026'),
('countdown_target', '2026-10-05 10:00:00'),
('venue_address', '38 Kareem Adetunji Street, beside Diekola house, Fomwan Roundabout, Heritage Hotel junction, Ogo-Oluwa, Osogbo.'),
('hosts_names', 'Pastor Jeremiah & Pastor (Mrs) Hannah Oyekale'),
('lead_minister', 'Pastor J. O Oyekale'),
('contact_phone_1', '08062875799'),
('contact_phone_2', '08067768904'),
('livestream_enabled', '1')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);