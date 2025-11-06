-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 06, 2025 at 05:51 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u578436281_leaderboard`
--

-- --------------------------------------------------------

--
-- Table structure for table `leaderboard`
--

CREATE TABLE `leaderboard` (
  `guid` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_kills` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_deaths` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_teamkills` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_teamdeaths` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_assists` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_damage_dealt` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_damage_taken` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_friendly_damage_dealt` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_friendly_damage_taken` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_rounds_played` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_active` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `nationality` varchar(2) DEFAULT NULL,
  `last_round_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `first_picks` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `first_deaths` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `map_stats`
--

CREATE TABLE `map_stats` (
  `guid` int(10) UNSIGNED NOT NULL,
  `map_name` varchar(255) NOT NULL,
  `kills` int(10) UNSIGNED DEFAULT 0,
  `deaths` int(10) UNSIGNED DEFAULT 0,
  `damage_dealt` int(10) UNSIGNED DEFAULT 0,
  `damage_taken` int(10) UNSIGNED DEFAULT 0,
  `assists` int(10) UNSIGNED DEFAULT 0,
  `rounds_played` int(10) UNSIGNED DEFAULT 0,
  `first_picks` int(10) UNSIGNED DEFAULT 0,
  `first_deaths` int(10) UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `matchweeks`
--

CREATE TABLE `matchweeks` (
  `matchweek_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` timestamp NOT NULL,
  `end_date` timestamp NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `snapshot_data` longtext DEFAULT NULL,
  `map_snapshot_data` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `matchweeks`
--

INSERT INTO `matchweeks` (`matchweek_id`, `name`, `start_date`, `end_date`, `is_active`, `snapshot_data`, `map_snapshot_data`) VALUES
(1, 'Matchweek 1', '2025-10-05 17:49:08', '2025-10-05 17:49:08', 0, NULL, NULL),
(2, 'Matchweek 2', '2025-09-14 18:57:57', '2025-09-14 18:57:57', 0, NULL, NULL),
(3, 'Matchweek 3', '2025-10-05 17:48:59', '2025-10-05 17:48:59', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `matchweek_map_snapshots`
--

CREATE TABLE `matchweek_map_snapshots` (
  `snapshot_id` int(11) NOT NULL,
  `matchweek_id` int(11) NOT NULL,
  `guid` int(10) UNSIGNED NOT NULL,
  `map_name` varchar(255) NOT NULL,
  `kills` int(10) UNSIGNED DEFAULT 0,
  `deaths` int(10) UNSIGNED DEFAULT 0,
  `damage_dealt` int(10) UNSIGNED DEFAULT 0,
  `damage_taken` int(10) UNSIGNED DEFAULT 0,
  `assists` int(10) UNSIGNED DEFAULT 0,
  `rounds_played` int(10) UNSIGNED DEFAULT 0,
  `first_picks` int(10) UNSIGNED DEFAULT 0,
  `first_deaths` int(10) UNSIGNED DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `matchweek_snapshots`
--

CREATE TABLE `matchweek_snapshots` (
  `snapshot_id` int(11) NOT NULL,
  `matchweek_id` int(11) NOT NULL,
  `guid` int(10) UNSIGNED NOT NULL,
  `kills` int(10) UNSIGNED DEFAULT 0,
  `deaths` int(10) UNSIGNED DEFAULT 0,
  `assists` int(10) UNSIGNED DEFAULT 0,
  `damage_dealt` int(10) UNSIGNED DEFAULT 0,
  `damage_taken` int(10) UNSIGNED DEFAULT 0,
  `rounds_played` int(10) UNSIGNED DEFAULT 0,
  `first_picks` int(10) UNSIGNED DEFAULT 0,
  `first_deaths` int(10) UNSIGNED DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `name_history`
--

CREATE TABLE `name_history` (
  `guid` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `last_used` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting` varchar(255) NOT NULL,
  `value` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting`, `value`) VALUES
('ipv4', '\"*\"'),
('title', '\"BYT - Revival Series\"'),
('token', '\"Redacted"'),
('whitelist-team-1', '[\"526649\",\"1814235\",\"1544447\",\"503765\",\"774878\",\"434315\",\"2232\",\"780242\"]'),
('whitelist-team-2', '[\"2511723\",\"2101026\",\"2191845\",\"2527500\",\"2525807\",\"937791\",\"2532448\",\"1352599\"]');

-- --------------------------------------------------------

--
-- Table structure for table `tokens`
--

CREATE TABLE `tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tokens`
--

INSERT INTO `tokens` (`token_id`, `user_id`, `value`) VALUES
(4, 1, 'Redacted'),
(5, 1, 'Redacted'),
(6, 1, 'Redacted');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(65) NOT NULL,
  `password` varchar(65) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`) VALUES
(1, 'wursti', 'Redacted');

--
-- Indexes for table `leaderboard`
--
ALTER TABLE `leaderboard`
  ADD PRIMARY KEY (`guid`);

--
-- Indexes for table `map_stats`
--
ALTER TABLE `map_stats`
  ADD PRIMARY KEY (`guid`,`map_name`);

--
-- Indexes for table `matchweeks`
--
ALTER TABLE `matchweeks`
  ADD PRIMARY KEY (`matchweek_id`);

--
-- Indexes for table `matchweek_map_snapshots`
--
ALTER TABLE `matchweek_map_snapshots`
  ADD PRIMARY KEY (`snapshot_id`),
  ADD UNIQUE KEY `unique_matchweek_player_map` (`matchweek_id`,`guid`,`map_name`),
  ADD KEY `guid` (`guid`);

--
-- Indexes for table `matchweek_snapshots`
--
ALTER TABLE `matchweek_snapshots`
  ADD PRIMARY KEY (`snapshot_id`),
  ADD UNIQUE KEY `unique_matchweek_player` (`matchweek_id`,`guid`),
  ADD KEY `guid` (`guid`);

--
-- Indexes for table `name_history`
--
ALTER TABLE `name_history`
  ADD PRIMARY KEY (`guid`,`name`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting`);

--
-- Indexes for table `tokens`
--
ALTER TABLE `tokens`
  ADD PRIMARY KEY (`token_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `matchweeks`
--
ALTER TABLE `matchweeks`
  MODIFY `matchweek_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `matchweek_map_snapshots`
--
ALTER TABLE `matchweek_map_snapshots`
  MODIFY `snapshot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5495;

--
-- AUTO_INCREMENT for table `matchweek_snapshots`
--
ALTER TABLE `matchweek_snapshots`
  MODIFY `snapshot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4911;

--
-- AUTO_INCREMENT for table `tokens`
--
ALTER TABLE `tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `matchweek_map_snapshots`
--
ALTER TABLE `matchweek_map_snapshots`
  ADD CONSTRAINT `matchweek_map_snapshots_ibfk_1` FOREIGN KEY (`matchweek_id`) REFERENCES `matchweeks` (`matchweek_id`),
  ADD CONSTRAINT `matchweek_map_snapshots_ibfk_2` FOREIGN KEY (`guid`) REFERENCES `leaderboard` (`guid`);

--
-- Constraints for table `matchweek_snapshots`
--
ALTER TABLE `matchweek_snapshots`
  ADD CONSTRAINT `matchweek_snapshots_ibfk_1` FOREIGN KEY (`matchweek_id`) REFERENCES `matchweeks` (`matchweek_id`),
  ADD CONSTRAINT `matchweek_snapshots_ibfk_2` FOREIGN KEY (`guid`) REFERENCES `leaderboard` (`guid`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
