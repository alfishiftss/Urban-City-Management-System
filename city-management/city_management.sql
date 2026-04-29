-- phpMyAdmin SQL Dump
-- Database: `city_management`

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Disable foreign key checks to prevent dependency errors during creation
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------

-- Table structure for table `citizens`
CREATE TABLE IF NOT EXISTS `citizens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `occupation` VARCHAR(100) DEFAULT NULL,
  `building` VARCHAR(100) DEFAULT NULL,
  `role` ENUM('Admin','Owner','Renter','Police') DEFAULT 'Renter',
  `nid` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `nid` (`nid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `areas`
CREATE TABLE IF NOT EXISTS `areas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `postal_code` VARCHAR(20) NOT NULL,
  `average_rent` DECIMAL(10,2) DEFAULT '0.00',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `buildings`
CREATE TABLE IF NOT EXISTS `buildings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `area_id` INT(11) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `address` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `area_id` (`area_id`),
  CONSTRAINT `buildings_ibfk_1` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `crimes`
CREATE TABLE IF NOT EXISTS `crimes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `crime_type` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `area_id` INT(11) NOT NULL,
  `reported_by` INT(11) DEFAULT NULL,
  `status` ENUM('pending','verified','rejected') DEFAULT 'pending',
  `verified_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `area_id` (`area_id`),
  KEY `reported_by` (`reported_by`),
  KEY `verified_by` (`verified_by`),
  CONSTRAINT `crimes_ibfk_1` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crimes_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `citizens` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crimes_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `citizens` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `criminal_records`
CREATE TABLE IF NOT EXISTS `criminal_records` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `citizen_id` INT(11) NOT NULL,
  `crime_description` TEXT NOT NULL,
  `punishment` TEXT NOT NULL,
  `penalty_amount` DECIMAL(10,2) DEFAULT '0.00',
  `recorded_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `citizen_id` (`citizen_id`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `criminal_records_ibfk_1` FOREIGN KEY (`citizen_id`) REFERENCES `citizens` (`id`) ON DELETE CASCADE,
  CONSTRAINT `criminal_records_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `citizens` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Table structure for table `announcements`
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `area_id` INT(11) DEFAULT NULL,
  `building_id` INT(11) DEFAULT NULL,
  `status` ENUM('Active','Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `area_id` (`area_id`),
  KEY `building_id` (`building_id`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Insert default Admin Account
-- Email: admin@admin.com 
-- Password: admin
INSERT IGNORE INTO `citizens` (`name`, `email`, `password`, `phone`, `occupation`, `building`, `role`, `nid`) VALUES
('System Admin', 'admin@admin.com', '$2y$10$wT.fQ.XQ1.w1oYFqXJk/5eyE8.bL1f6O7l.x2D9WlG0Qo5V2D3C4q', '555-0000', 'Administrator', 'City Hall', 'Admin', 'ADMIN-001');

COMMIT;
