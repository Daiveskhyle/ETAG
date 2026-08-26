-- Create Database
CREATE DATABASE IF NOT EXISTS `etag` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `etag`;

-- --------------------------------------------------------
-- Table structure for table `event_settings`
-- --------------------------------------------------------

CREATE TABLE `event_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial data for table `event_settings`

INSERT INTO `event_settings` (`setting_key`, `setting_value`) VALUES
('about_bg_image', ''),
('broadcast_bg_image', 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?auto=format&fit=crop&w=1920&q=80'),
('contact_phone_1', '08062875799'),
('contact_phone_2', '08067768904'),
('convention_title', 'Acts of the Holy Spirit Convention 2026'),
('countdown_target', '2026-10-05 10:00:00'),
('event_dates', '5th – 11th October, 2026'),
('hero_bg_image', ''),
('hosts_names', 'Pastor Jeremiah & Pastor (Mrs) Hannah Oyekale'),
('is_live_now', '0'),
('lead_minister', 'Pastor J. O Oyekale'),
('livestream_enabled', '1'),
('livestream_title', 'Official Broadcast — Acts of the Holy Spirit Convention 2026'),
('logo_url', ''),
('ministry_alias', 'The Acts of the Holy Spirit Christian Assembly'),
('ministry_name', 'The End Time Army of God'),
('motto_scripture', 'Be strong in the Lord and in the power of His might'),
('register_bg_image', ''),
('theme_scripture', 'Psalm 104:4'),
('theme_title', 'Flaming Fire'),
('venue_address', '38 Kareem Adetunji Street, beside Diekola house, Fomwan Roundabout, Heritage Hotel junction, Ogo-Oluwa, Osogbo.'),
('venue_bg_image', ''),
('youtube_live_url', 'https://youtu.be/_vAxXfyOZB4?si=NuW31vY4iFKhy7n3');

-- --------------------------------------------------------
-- Table structure for table `registrations`
-- --------------------------------------------------------

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reg_code` varchar(20) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(25) NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `location` varchar(150) NOT NULL,
  `attendance_mode` enum('In-Person (Osogbo)','Online Stream') DEFAULT 'In-Person (Osogbo)',
  `needs_accommodation` enum('Yes','No') DEFAULT 'No',
  `medical_support` enum('Yes','No') DEFAULT 'No',
  `prayer_request` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reg_code` (`reg_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial data for table `registrations`

INSERT INTO `registrations` (`id`, `reg_code`, `fullname`, `email`, `phone`, `gender`, `location`, `attendance_mode`, `needs_accommodation`, `medical_support`, `prayer_request`, `created_at`) VALUES
(1, 'ETAG-354E2D', 'OPEYEMI DAVID ADERIBIGBE', 'daiveskhyle@gmail.com', '07011801027', 'Male', 'OSOGBO', 'In-Person (Osogbo)', 'Yes', 'Yes', '', '2026-08-26 08:32:12'),
(3, 'ETAG-E8B7F5', 'OPEYEMI DAVID ADERIBIGBE', 'daiveskhyle@gmail.com', '07011801027', 'Male', 'OSOGBO', 'In-Person (Osogbo)', 'Yes', 'Yes', '', '2026-08-26 08:43:37');
