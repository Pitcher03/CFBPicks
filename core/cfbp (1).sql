-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 06, 2025 at 02:06 PM
-- Server version: 10.6.22-MariaDB-cll-lve
-- PHP Version: 8.3.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cfbp`
--
CREATE DATABASE IF NOT EXISTS `cfbp` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `cfbp`;

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password_hash` varchar(100) NOT NULL,
  `score` decimal(10,1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `futures`
--

CREATE TABLE `futures` (
  `account_id` int(11) NOT NULL,
  `p12` varchar(30) NOT NULL,
  `mac` varchar(30) NOT NULL,
  `mtn` varchar(30) NOT NULL,
  `b12` varchar(30) NOT NULL,
  `aac` varchar(30) NOT NULL,
  `b10` varchar(30) NOT NULL,
  `sec` varchar(30) NOT NULL,
  `usa` varchar(30) NOT NULL,
  `sun` varchar(30) NOT NULL,
  `acc` varchar(30) NOT NULL,
  `seed_1` varchar(30) NOT NULL,
  `seed_2` varchar(30) NOT NULL,
  `seed_3` varchar(30) NOT NULL,
  `seed_4` varchar(30) NOT NULL,
  `armynavy` varchar(30) NOT NULL,
  `champion` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `week` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `home` varchar(30) NOT NULL,
  `away` varchar(30) NOT NULL,
  `value` int(11) NOT NULL DEFAULT 1,
  `underdog` varchar(30) DEFAULT NULL,
  `bonus` int(11) NOT NULL DEFAULT 0,
  `winner` varchar(30) DEFAULT NULL,
  `info` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `picks`
--

CREATE TABLE `picks` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `pick` varchar(30) NOT NULL,
  `type` enum('normal','tiebreaker','confidence') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scores`
--

CREATE TABLE `scores` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `week_num` int(11) NOT NULL,
  `score` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `winners`
--

CREATE TABLE `winners` (
  `week_num` int(11) NOT NULL,
  `account_id` varchar(30) DEFAULT NULL,
  `account_id_2` varchar(30) DEFAULT NULL,
  `bonus_points` int(11) NOT NULL,
  `max` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `futures`
--
ALTER TABLE `futures`
  ADD PRIMARY KEY (`account_id`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `picks`
--
ALTER TABLE `picks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pick_account` (`account_id`),
  ADD KEY `pick_game` (`game_id`);

--
-- Indexes for table `scores`
--
ALTER TABLE `scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_fk` (`account_id`);

--
-- Indexes for table `winners`
--
ALTER TABLE `winners`
  ADD UNIQUE KEY `week_num` (`week_num`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `picks`
--
ALTER TABLE `picks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scores`
--
ALTER TABLE `scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `futures`
--
ALTER TABLE `futures`
  ADD CONSTRAINT `account_id_fk` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `picks`
--
ALTER TABLE `picks`
  ADD CONSTRAINT `pick_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `pick_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`);

--
-- Constraints for table `scores`
--
ALTER TABLE `scores`
  ADD CONSTRAINT `account_fk` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
