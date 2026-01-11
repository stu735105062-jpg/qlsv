-- phpMyAdmin SQL Dump
-- version 5.2.1
-- Host: localhost
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: student_management
CREATE DATABASE IF NOT EXISTS student_management;
USE student_management;

-- Table structure for table `student_info`
CREATE TABLE IF NOT EXISTS `student_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample data for table `student_info`
INSERT INTO `student_info` (`id`, `name`, `email`, `phone`) VALUES
(13, 'AHMED SAHAL ADAM', 'ahmedsahal@gmail.com', '634294218'),
(14, 'ajama', 'ahmedsahal@gmail.com', '0634916040'),
(15, 'caasha xusseen', 'caasha@gmail.com', '0634189019'),
(16, 'Mohamed Ali', 'Mohammed@gmail.com', '06345552890'),
(17, 'foosiya cali adan', 'foosiya@gmail.com', '777777');

-- Table structure for table `users`
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample data for table `users`
INSERT INTO `users` (`id`, `username`, `password`) VALUES
(1, 'admin', '123456'); -- lưu ý: bạn có thể dùng password hash trong thực tế
