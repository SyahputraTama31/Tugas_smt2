-- phpMyAdmin SQL Dump
-- Database: `rohis`
-- Updated: 2026-04-19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(25) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('superadmin','admin','user') NOT NULL DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `nama`, `password`, `role`) VALUES
(1, 'tama', 'Raditya Syahputra Dwitama', '$2y$10$Iy7f9d/XZxLFgymZkd.Ni.nQmjSioRc1vsRfb/0LllgOAWz9qw2RW', 'superadmin'),
(3, 'dwitama', 'Raditya Syahputra', '$2y$10$z1jOQErTSyiYp8Q9vofeZuSnkh5SzGw1TJpu6DLJu/0bgOwfXOsnC', 'user');

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time DEFAULT NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `foto_base64` longtext,
  `pesan` text,
  `status` enum('hadir','izin','sakit','alpha') NOT NULL DEFAULT 'hadir',
  PRIMARY KEY (`id`),
  KEY `idx_user_tanggal` (`user_id`, `tanggal`),
  CONSTRAINT `fk_absensi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan_absen`
--

CREATE TABLE `pengaturan_absen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lat_admin` double NOT NULL DEFAULT '0',
  `lng_admin` double NOT NULL DEFAULT '0',
  `radius_km` double NOT NULL DEFAULT '0.5',
  `jam_mulai` time NOT NULL DEFAULT '07:00:00',
  `jam_selesai` time NOT NULL DEFAULT '09:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pengaturan_absen`
--

INSERT INTO `pengaturan_absen` (`id`, `lat_admin`, `lng_admin`, `radius_km`, `jam_mulai`, `jam_selesai`) VALUES
(1, 0, 0, 0.5, '07:00:00', '09:00:00');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
