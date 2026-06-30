-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 29, 2026 at 06:45 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sankalp_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

DROP TABLE IF EXISTS `bills`;
CREATE TABLE IF NOT EXISTS `bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_type` enum('Purchase','Sale') NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `from_party` varchar(255) NOT NULL,
  `to_party` varchar(255) NOT NULL,
  `sub_total` decimal(12,2) DEFAULT 0.00,
  `total_cgst` decimal(12,2) DEFAULT 0.00,
  `total_sgst` decimal(12,2) DEFAULT 0.00,
  `total_gst` decimal(12,2) DEFAULT 0.00,
  `grand_total` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `bill_type`, `invoice_no`, `invoice_date`, `from_party`, `to_party`, `sub_total`, `total_cgst`, `total_sgst`, `total_gst`, `grand_total`, `created_at`) VALUES
(2, 'Purchase', 'VB/08', '2026-06-13', 'Verma Brothers', 'Sankalp Gift Corner', 42375.00, 3813.75, 3813.75, 7627.50, 50002.50, '2026-06-13 17:52:37'),
(3, 'Purchase', 'RE/09/2026-27', '2026-06-13', 'Radhe Enterprises', 'Sankalp Gift Corner', 111250.00, 9375.00, 9375.00, 18750.00, 130000.00, '2026-06-13 18:02:53'),
(4, 'Purchase', 'RRE/2026/27/18', '2026-06-15', 'Radha Rani Enterprises', 'Sankalp Gift Corner', 104500.00, 9405.00, 9405.00, 18810.00, 123310.00, '2026-06-15 17:58:00'),
(5, 'Purchase', 'NS/2026-27/01', '2026-06-16', 'Niharika Stationary', 'Sankalp Gift Corner', 64700.00, 5823.00, 5823.00, 11646.00, 76346.00, '2026-06-16 16:49:18');

-- --------------------------------------------------------

--
-- Table structure for table `bill_items`
--

DROP TABLE IF EXISTS `bill_items`;
CREATE TABLE IF NOT EXISTS `bill_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(50) DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `rate` decimal(10,2) NOT NULL,
  `taxable_amount` decimal(12,2) NOT NULL,
  `gst_percent` decimal(5,2) NOT NULL,
  `cgst_amount` decimal(12,2) NOT NULL,
  `sgst_amount` decimal(12,2) NOT NULL,
  `item_total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_no` (`invoice_no`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bill_items`
--

INSERT INTO `bill_items` (`id`, `invoice_no`, `item_name`, `hsn_code`, `quantity`, `rate`, `taxable_amount`, `gst_percent`, `cgst_amount`, `sgst_amount`, `item_total`) VALUES
(1, 'VB/08', 'Hard Disk', '', 25.00, 1500.00, 37500.00, 18.00, 3375.00, 3375.00, 44250.00),
(2, 'VB/08', 'Mouse Ibell', '', 25.00, 195.00, 4875.00, 18.00, 438.75, 438.75, 5752.50),
(3, 'RE/09/2026-27', 'Study Tables', '', 50.00, 1800.00, 90000.00, 18.00, 8100.00, 8100.00, 106200.00),
(4, 'RE/09/2026-27', 'A4 Notebook', '482010', 500.00, 40.00, 20000.00, 12.00, 1200.00, 1200.00, 22400.00),
(5, 'RE/09/2026-27', 'Pen Set', '482010', 50.00, 25.00, 1250.00, 12.00, 75.00, 75.00, 1400.00),
(6, 'RRE/2026/27/18', 'Study Tables', '', 50.00, 1750.00, 87500.00, 18.00, 7875.00, 7875.00, 103250.00),
(7, 'RRE/2026/27/18', 'Car Decoration Sheet', '', 100.00, 155.00, 15500.00, 18.00, 1395.00, 1395.00, 18290.00),
(8, 'RRE/2026/27/18', 'Transparent Papers', '', 200.00, 7.50, 1500.00, 18.00, 135.00, 135.00, 1770.00),
(9, 'NS/2026-27/01', 'Perfumes', '', 50.00, 125.00, 6250.00, 18.00, 562.50, 562.50, 7375.00),
(10, 'NS/2026-27/01', 'Deodorants', '', 50.00, 170.00, 8500.00, 18.00, 765.00, 765.00, 10030.00),
(11, 'NS/2026-27/01', 'Premium Watches', '', 50.00, 999.00, 49950.00, 18.00, 4495.50, 4495.50, 58941.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_date` date NOT NULL,
  `payment_mode` enum('Cash','Bank','UPI') NOT NULL,
  `to_party` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `ref_invoice_no` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receipts`
--

DROP TABLE IF EXISTS `receipts`;
CREATE TABLE IF NOT EXISTS `receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_date` date NOT NULL,
  `receipt_mode` enum('Cash','Bank','UPI') NOT NULL,
  `from_party` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `ref_invoice_no` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_master`
--

DROP TABLE IF EXISTS `stock_master`;
CREATE TABLE IF NOT EXISTS `stock_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  `hsn_code` varchar(20) DEFAULT '9608',
  `current_stock` decimal(10,2) DEFAULT 0.00,
  `min_stock_alert` decimal(10,2) DEFAULT 10.00,
  `purchase_rate` decimal(10,2) DEFAULT 0.00,
  `sale_rate` decimal(10,2) DEFAULT 0.00,
  `gst_percent` decimal(5,2) DEFAULT 18.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_name` (`item_name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_master`
--

INSERT INTO `stock_master` (`id`, `item_name`, `hsn_code`, `current_stock`, `min_stock_alert`, `purchase_rate`, `sale_rate`, `gst_percent`) VALUES
(1, 'Ball Pen Blue', '960810', 100.00, 20.00, 8.00, 10.00, 18.00),
(2, 'A4 Notebook', '482010', 550.00, 10.00, 40.00, 50.00, 12.00),
(3, 'Gift Box Medium', '481910', 15.00, 5.00, 80.00, 100.00, 18.00),
(4, 'Hard Disk', '', 25.00, 10.00, 1500.00, 0.00, 18.00),
(5, 'Mouse Ibell', '', 25.00, 10.00, 195.00, 0.00, 18.00),
(6, 'Study Tables', '', 100.00, 10.00, 1750.00, 0.00, 18.00),
(7, 'Pen Set', '482010', 50.00, 10.00, 25.00, 0.00, 12.00),
(8, 'Car Decoration Sheet', '', 100.00, 10.00, 155.00, 0.00, 18.00),
(9, 'Transparent Papers', '', 200.00, 10.00, 7.50, 0.00, 18.00),
(10, 'Perfumes', '', 50.00, 10.00, 125.00, 0.00, 18.00),
(11, 'Deodorants', '', 50.00, 10.00, 170.00, 0.00, 18.00),
(12, 'Premium Watches', '', 50.00, 10.00, 999.00, 0.00, 18.00);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD CONSTRAINT `bill_items_ibfk_1` FOREIGN KEY (`invoice_no`) REFERENCES `bills` (`invoice_no`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
