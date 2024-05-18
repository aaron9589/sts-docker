-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 05, 2021 at 08:43 PM
-- Server version: 10.1.25-MariaDB
-- PHP Version: 7.1.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sts_db3`
--

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

DROP TABLE IF EXISTS `cars`;
CREATE TABLE `cars` (
  `Id` int(11) NOT NULL,
  `reporting_marks` varchar(16) NOT NULL,
  `car_code_id` int(11) NOT NULL,
  `current_location_id` int(11) NOT NULL,
  `position` int(11) DEFAULT NULL,
  `status` varchar(256) NOT NULL,
  `handled_by_job_id` int(11) DEFAULT NULL,
  `remarks` text,
  `load_count` int(11) NOT NULL,
  `home_location` int(11) DEFAULT NULL,
  `RFID_code` char(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `car_codes`
--

DROP TABLE IF EXISTS `car_codes`;
CREATE TABLE `car_codes` (
  `Id` int(11) NOT NULL,
  `code` tinytext NOT NULL,
  `description` tinytext,
  `remarks` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `car_orders`
--

DROP TABLE IF EXISTS `car_orders`;
CREATE TABLE `car_orders` (
  `waybill_number` varchar(16) NOT NULL,
  `shipment` int(11) NOT NULL,
  `car` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `commodities`
--

DROP TABLE IF EXISTS `commodities`;
CREATE TABLE `commodities` (
  `Id` int(11) NOT NULL,
  `Code` tinytext NOT NULL,
  `Description` tinytext,
  `Remarks` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `empty_locations`
--

DROP TABLE IF EXISTS `empty_locations`;
CREATE TABLE `empty_locations` (
  `shipment` int(11) NOT NULL,
  `priority` int(11) NOT NULL,
  `location` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `Id` int(11) NOT NULL,
  `name` tinytext NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations` (
  `Id` int(11) NOT NULL,
  `code` tinytext NOT NULL,
  `station` int(11) NOT NULL,
  `track` tinytext,
  `spot` tinytext,
  `rpt_station` tinytext,
  `remarks` text,
  `color` tinytext
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `owners`
--

DROP TABLE IF EXISTS `owners`;
CREATE TABLE `owners` (
  `id` int(11) NOT NULL,
  `name` varchar(256) DEFAULT NULL,
  `remarks` varchar(256) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ownership`
--

DROP TABLE IF EXISTS `ownership`;
CREATE TABLE `ownership` (
  `car_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `on_off_rr` varchar(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pool`
--

DROP TABLE IF EXISTS `pool`;
CREATE TABLE `pool` (
  `car_id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `routing`
--

DROP TABLE IF EXISTS `routing`;
CREATE TABLE `routing` (
  `id` int(11) NOT NULL,
  `station` tinytext NOT NULL,
  `station_nbr` int(11) DEFAULT NULL,
  `instructions` text,
  `sort_seq` int(11) DEFAULT NULL,
  `color1` int(11) DEFAULT NULL,
  `color2` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_name` varchar(256) NOT NULL,
  `setting_desc` varchar(256) NOT NULL,
  `setting_value` varchar(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_name`, `setting_desc`, `setting_value`) VALUES
('print_width', 'Print Width', '7.5in'),
('railroad_initials', 'Initials of the railroad', ''),
('railroad_name', 'Name of the railroad', ''),
('session_nbr', 'Session Number', '0');

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

DROP TABLE IF EXISTS `shipments`;
CREATE TABLE `shipments` (
  `Id` int(11) NOT NULL,
  `code` tinytext NOT NULL,
  `description` tinytext NOT NULL,
  `consignment` int(11) NOT NULL,
  `car_code` int(11) NOT NULL,
  `loading_location` int(11) NOT NULL,
  `unloading_location` int(11) NOT NULL,
  `last_ship_date` int(11) NOT NULL,
  `min_interval` int(11) NOT NULL,
  `max_interval` int(11) NOT NULL,
  `min_amount` int(11) NOT NULL,
  `max_amount` int(11) NOT NULL,
  `special_instructions` tinytext,
  `remarks` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `car_codes`
--
ALTER TABLE `car_codes`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `car_orders`
--
ALTER TABLE `car_orders`
  ADD PRIMARY KEY (`waybill_number`);

--
-- Indexes for table `commodities`
--
ALTER TABLE `commodities`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `empty_locations`
--
ALTER TABLE `empty_locations`
  ADD PRIMARY KEY (`shipment`,`priority`,`location`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `owners`
--
ALTER TABLE `owners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `routing`
--
ALTER TABLE `routing`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_name`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`Id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `car_codes`
--
ALTER TABLE `car_codes`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `commodities`
--
ALTER TABLE `commodities`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `owners`
--
ALTER TABLE `owners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `routing`
--
ALTER TABLE `routing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
