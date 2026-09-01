-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Ago 31, 2026 alle 16:19
-- Versione del server: 8.0.45
-- Versione PHP: 8.0.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `my_workout`
--
CREATE DATABASE IF NOT EXISTS `my_workout` DEFAULT CHARACTER SET utf32 COLLATE utf32_unicode_ci;
USE `my_workout`;

-- --------------------------------------------------------

--
-- Struttura della tabella `exercises`
--

DROP TABLE IF EXISTS `exercises`;
CREATE TABLE `exercises` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` enum('chest','back','legs','shoulders','arms','core','cardio') COLLATE utf8mb4_unicode_ci NOT NULL,
  `equipment` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `exercises`
--

INSERT INTO `exercises` (`id`, `name`, `description`, `category`, `equipment`, `image_url`, `instructions`, `created_at`) VALUES
(1, 'Squat con bilanciere', 'Esercizio fondamentale per le gambe', 'legs', 'Bilanciere', 'https://fitnessprogramer.com/wp-content/uploads/2024/10/smith-machine-squat.gif', 'Profondità parallelo, ginocchia in linea piedi', '2026-08-27 11:27:34'),
(2, 'Panca Piana manubri', 'Esercizio per il petto', 'chest', 'Manubri', 'https://fitnessprogramer.com/wp-content/uploads/2021/02/Dumbbell-Press.gif', 'Scapole retratte, controllo eccentrico 2\"', '2026-08-27 11:27:34'),
(3, 'Affondi camminati', 'Esercizio per gambe e glutei', 'legs', 'Corpo libero/Manubri', 'https://fitnessprogramer.com/wp-content/uploads/2021/09/Walking-High-Knee-Lunges.gif', 'Passo ampio, busto eretto', '2026-08-27 11:27:34'),
(4, 'Panca Inclinata 30°', 'Focus pettorali alti', 'chest', 'Manubri/Bilanciere', 'https://fitnessprogramer.com/wp-content/uploads/2021/02/Incline-Dumbbell-Press.gif', 'Focus pettorali alti', '2026-08-27 11:27:34'),
(5, 'Alzate Laterali', 'Esercizio per deltoidi laterali', 'shoulders', 'Manubri', 'https://fitnessprogramer.com/wp-content/uploads/2021/02/Dumbbell-Lateral-Raise.gif', 'Gomiti leggermente flessi, no slancio', '2026-08-27 11:27:34'),
(6, 'French Press', 'Isolamento tricipiti', 'arms', 'Bilanciere/Manubri', 'https://fitnessprogramer.com/wp-content/uploads/2021/06/Dumbbell-Skull-Crusher.gif', 'Isolamento tricipiti', '2026-08-27 11:27:34'),
(7, 'Stacchi da Terra', 'Esercizio per catena posteriore', 'back', 'Bilanciere', 'https://fitnessprogramer.com/wp-content/uploads/2021/02/Barbell-Deadlift.gif', 'Schiena neutra, carico sui talloni', '2026-08-27 11:27:34'),
(8, 'Lat Machine', 'Esercizio per dorsali', 'back', 'Macchina', 'https://fitnessprogramer.com/wp-content/uploads/2021/02/Lat-Pulldown.gif', 'Presa larga, extrarotazione in chiusura', '2026-08-27 11:27:34'),
(9, 'Rematore manubrio', 'Esercizio per spessore schiena', 'back', 'Manubrio', 'https://fitnessprogramer.com/wp-content/uploads/2021/02/Dumbbell-Row.gif', 'Singolo braccio, gomito lungo il fianco', '2026-08-27 11:27:34'),
(10, 'Shoulder Press', 'Esercizio per spalle', 'shoulders', 'Manubri/Bilanciere', 'https://fitnessprogramer.com/wp-content/uploads/2021/02/Dumbbell-Shoulder-Press.gif', 'Seduto, no slancio, controllo completo', '2026-08-27 11:27:34'),
(11, 'Curl con manubri', 'Esercizio per bicipiti', 'arms', 'Manubri', 'https://fitnessprogramer.com/wp-content/uploads/2022/04/Double-Arm-Dumbbell-Curl.gif', 'No oscillazione tronco, extrarotazione', '2026-08-27 11:27:34'),
(12, 'Plank', 'Esercizio per core', 'core', 'Corpo libero', 'https://fitnessprogramer.com/wp-content/uploads/2021/02/plank.gif', 'Core contratto, no inarcamento lombare', '2026-08-27 11:27:34'),
(13, 'Leg Press', 'Esercizio per quadricipiti', 'legs', 'Macchina', 'https://fitnessprogramer.com/wp-content/uploads/2015/11/Leg-Press.gif', 'Piedi larghezza spalle, no lock ginocchia', '2026-08-27 11:27:34'),
(14, 'Dip', 'Esercizio per petto/tricipiti', 'chest', 'Parallele', 'https://fitnessprogramer.com/wp-content/uploads/2021/02/Triceps-Dips.gif', 'Pettorali/tricipiti, discesa controllata', '2026-08-27 11:27:34'),
(15, 'Pulley Basso', 'Esercizio per spessore schiena', 'back', 'Macchina', 'https://fitnessprogramer.com/wp-content/uploads/2021/02/Seated-Cable-Row.gif', 'Schiena spessore, extrarotazione', '2026-08-27 11:27:34'),
(16, 'Alzate Frontali', 'Deltoidi anteriori', 'shoulders', 'Manubri', 'https://fitnessprogramer.com/wp-content/uploads/2021/06/Alternating-Dumbbell-Front-Raise.gif', 'Deltoidi anteriori, no slancio', '2026-08-27 11:27:34'),
(17, 'Face Pull', 'Deltoidi posteriori, cuffia rotatori', 'shoulders', 'Cavo', 'https://fitnessprogramer.com/wp-content/uploads/2021/02/Face-Pull.gif', 'Deltoidi posteriori, cuffia rotatori', '2026-08-27 11:27:34'),
(18, 'Hammer Curl', 'Bicipiti/brachiale', 'arms', 'Manubri', 'https://fitnessprogramer.com/wp-content/uploads/2021/02/Hammer-Curl.gif', 'Bicipiti/brachiale, presa neutra', '2026-08-27 11:27:34');

-- --------------------------------------------------------

--
-- Struttura della tabella `foods`
--

DROP TABLE IF EXISTS `foods`;
CREATE TABLE `foods` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('protein','carbs','fats','vegetables','fruits','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `calories_per_100g` decimal(6,2) DEFAULT NULL,
  `protein_per_100g` decimal(6,2) DEFAULT NULL,
  `carbs_per_100g` decimal(6,2) DEFAULT NULL,
  `fats_per_100g` decimal(6,2) DEFAULT NULL,
  `fiber_per_100g` decimal(6,2) DEFAULT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `foods`
--

INSERT INTO `foods` (`id`, `name`, `category`, `calories_per_100g`, `protein_per_100g`, `carbs_per_100g`, `fats_per_100g`, `fiber_per_100g`, `image_url`, `created_at`) VALUES
(1, 'Petto di pollo', 'protein', '110.00', '23.00', '0.00', '1.50', '0.00', NULL, '2026-08-27 11:27:34'),
(2, 'Tacchino', 'protein', '135.00', '29.00', '0.00', '1.00', '0.00', NULL, '2026-08-27 11:27:34'),
(3, 'Manzo magro', 'protein', '150.00', '26.00', '0.00', '5.00', '0.00', NULL, '2026-08-27 11:27:34'),
(4, 'Salmone', 'protein', '208.00', '20.00', '0.00', '13.00', '0.00', NULL, '2026-08-27 11:27:34'),
(5, 'Merluzzo', 'protein', '82.00', '18.00', '0.00', '0.70', '0.00', NULL, '2026-08-27 11:27:34'),
(6, 'Tonno al naturale', 'protein', '103.00', '26.00', '0.00', '0.80', '0.00', NULL, '2026-08-27 11:27:34'),
(7, 'Uova', 'protein', '155.00', '13.00', '1.10', '11.00', '0.00', NULL, '2026-08-27 11:27:34'),
(8, 'Yogurt Greco 0%', 'protein', '59.00', '10.00', '3.60', '0.40', '0.00', NULL, '2026-08-27 11:27:34'),
(9, 'Parmigiano Reggiano', 'protein', '392.00', '35.00', '0.00', '28.00', '0.00', NULL, '2026-08-27 11:27:34'),
(10, 'Ricotta', 'protein', '174.00', '11.00', '3.00', '13.00', '0.00', NULL, '2026-08-27 11:27:34'),
(11, 'Whey protein', 'protein', '400.00', '80.00', '5.00', '5.00', '0.00', NULL, '2026-08-27 11:27:34'),
(12, 'Avena in fiocchi', 'carbs', '389.00', '16.90', '66.00', '6.90', '10.60', NULL, '2026-08-27 11:27:34'),
(13, 'Pasta integrale', 'carbs', '348.00', '13.00', '70.00', '2.50', '11.00', NULL, '2026-08-27 11:27:34'),
(14, 'Riso Basmati', 'carbs', '345.00', '7.00', '78.00', '0.90', '1.30', NULL, '2026-08-27 11:27:34'),
(15, 'Pane integrale', 'carbs', '247.00', '13.00', '41.00', '3.40', '7.00', NULL, '2026-08-27 11:27:34'),
(16, 'Patate', 'carbs', '77.00', '2.00', '17.00', '0.10', '2.20', NULL, '2026-08-27 11:27:34'),
(17, 'Banana', 'fruits', '89.00', '1.10', '23.00', '0.30', '2.60', NULL, '2026-08-27 11:27:34'),
(18, 'Mela', 'fruits', '52.00', '0.30', '14.00', '0.20', '2.40', NULL, '2026-08-27 11:27:34'),
(19, 'Pera', 'fruits', '57.00', '0.40', '15.00', '0.10', '3.10', NULL, '2026-08-27 11:27:34'),
(20, 'Arancia', 'fruits', '47.00', '0.90', '12.00', '0.10', '2.40', NULL, '2026-08-27 11:27:34'),
(21, 'Olio Extravergine Oliva', 'fats', '884.00', '0.00', '0.00', '100.00', '0.00', NULL, '2026-08-27 11:27:34'),
(22, 'Noci', 'fats', '654.00', '15.00', '14.00', '65.00', '7.00', NULL, '2026-08-27 11:27:34'),
(23, 'Mandorle', 'fats', '579.00', '21.00', '22.00', '50.00', '12.00', NULL, '2026-08-27 11:27:34'),
(24, 'Cioccolato fondente 85%', 'fats', '598.00', '8.00', '30.00', '50.00', '11.00', NULL, '2026-08-27 11:27:34'),
(25, 'Broccoli', 'vegetables', '34.00', '2.80', '7.00', '0.40', '2.60', NULL, '2026-08-27 11:27:34'),
(26, 'Spinaci', 'vegetables', '23.00', '2.90', '3.60', '0.40', '2.20', NULL, '2026-08-27 11:27:34'),
(27, 'Zucchine', 'vegetables', '17.00', '1.20', '3.10', '0.30', '1.00', NULL, '2026-08-27 11:27:34'),
(28, 'Insalata', 'vegetables', '15.00', '1.40', '2.90', '0.20', '1.30', NULL, '2026-08-27 11:27:34'),
(29, 'Pomodori', 'vegetables', '18.00', '0.90', '3.90', '0.20', '1.20', NULL, '2026-08-27 11:27:34'),
(30, 'Legumi misti', 'protein', '116.00', '8.00', '20.00', '0.80', '5.00', NULL, '2026-08-27 11:27:34');

-- --------------------------------------------------------

--
-- Struttura della tabella `meals`
--

DROP TABLE IF EXISTS `meals`;
CREATE TABLE `meals` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `meal_type` enum('breakfast','snack_am','lunch','snack_pm','dinner','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `meal_date` date NOT NULL,
  `total_calories` decimal(6,2) DEFAULT NULL,
  `total_protein` decimal(6,2) DEFAULT NULL,
  `total_carbs` decimal(6,2) DEFAULT NULL,
  `total_fats` decimal(6,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `meals`
--

INSERT INTO `meals` (`id`, `user_id`, `meal_type`, `meal_date`, `total_calories`, `total_protein`, `total_carbs`, `total_fats`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'breakfast', '2026-08-27', '620.00', '35.00', '90.00', '12.00', NULL, '2026-08-27 11:28:01', '2026-08-27 11:28:01'),
(2, 1, 'snack_am', '2026-08-27', '250.00', '15.00', '20.00', '12.00', NULL, '2026-08-27 11:28:01', '2026-08-27 11:28:01'),
(3, 1, 'lunch', '2026-08-27', '780.00', '40.00', '110.00', '15.00', NULL, '2026-08-27 11:28:01', '2026-08-27 11:28:01'),
(4, 1, 'snack_pm', '2026-08-27', '240.00', '5.00', '50.00', '2.00', NULL, '2026-08-27 11:28:01', '2026-08-27 11:28:01'),
(5, 1, 'dinner', '2026-08-27', '640.00', '50.00', '80.00', '12.00', NULL, '2026-08-27 11:28:01', '2026-08-27 11:28:01'),
(6, 1, 'breakfast', '2026-08-27', '620.00', '35.00', '90.00', '12.00', NULL, '2026-08-27 11:28:42', '2026-08-27 11:28:42'),
(7, 1, 'snack_am', '2026-08-27', '250.00', '15.00', '20.00', '12.00', NULL, '2026-08-27 11:28:42', '2026-08-27 11:28:42'),
(8, 1, 'lunch', '2026-08-27', '780.00', '40.00', '110.00', '15.00', NULL, '2026-08-27 11:28:42', '2026-08-27 11:28:42'),
(9, 1, 'snack_pm', '2026-08-27', '240.00', '5.00', '50.00', '2.00', NULL, '2026-08-27 11:28:42', '2026-08-27 11:28:42'),
(10, 1, 'dinner', '2026-08-27', '640.00', '50.00', '80.00', '12.00', NULL, '2026-08-27 11:28:42', '2026-08-27 11:28:42');

-- --------------------------------------------------------

--
-- Struttura della tabella `meal_foods`
--

DROP TABLE IF EXISTS `meal_foods`;
CREATE TABLE `meal_foods` (
  `id` int NOT NULL,
  `meal_id` int NOT NULL,
  `food_id` int NOT NULL,
  `quantity_grams` decimal(6,2) NOT NULL,
  `calories` decimal(6,2) DEFAULT NULL,
  `protein` decimal(6,2) DEFAULT NULL,
  `carbs` decimal(6,2) DEFAULT NULL,
  `fats` decimal(6,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `meal_foods`
--

INSERT INTO `meal_foods` (`id`, `meal_id`, `food_id`, `quantity_grams`, `calories`, `protein`, `carbs`, `fats`) VALUES
(1, 1, 12, '100.00', '389.00', '16.90', '66.00', '6.90'),
(2, 1, 17, '120.00', '107.00', '1.30', '27.60', '0.40'),
(3, 1, 11, '30.00', '120.00', '24.00', '1.50', '1.50'),
(4, 2, 9, '50.00', '196.00', '17.50', '0.00', '14.00'),
(5, 2, 18, '150.00', '78.00', '0.50', '21.00', '0.30'),
(6, 3, 13, '130.00', '452.00', '16.90', '91.00', '3.30'),
(7, 3, 21, '10.00', '88.00', '0.00', '0.00', '10.00'),
(8, 5, 1, '200.00', '220.00', '46.00', '0.00', '3.00'),
(9, 5, 16, '300.00', '231.00', '6.00', '51.00', '0.30'),
(10, 5, 21, '10.00', '88.00', '0.00', '0.00', '10.00');

-- --------------------------------------------------------

--
-- Struttura della tabella `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `link`, `is_read`, `created_by`, `created_at`) VALUES
(1, 1, 'abbiamo aggiornato il menu', '', 0, 2, '2026-08-31 11:36:34');

-- --------------------------------------------------------

--
-- Struttura della tabella `progress_tracking`
--

DROP TABLE IF EXISTS `progress_tracking`;
CREATE TABLE `progress_tracking` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `track_date` date NOT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `chest_circumference` decimal(5,2) DEFAULT NULL,
  `arm_circumference` decimal(5,2) DEFAULT NULL,
  `waist_circumference` decimal(5,2) DEFAULT NULL,
  `energy_level` int DEFAULT NULL,
  `sleep_quality` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Dump dei dati per la tabella `progress_tracking`
--

INSERT INTO `progress_tracking` (`id`, `user_id`, `track_date`, `weight`, `chest_circumference`, `arm_circumference`, `waist_circumference`, `energy_level`, `sleep_quality`, `notes`, `created_at`) VALUES
(1, 1, '2026-08-27', '74.00', '102.00', '36.00', '82.00', 7, 7, '', '2026-08-27 11:28:01'),
(2, 1, '2026-08-27', '72.00', NULL, NULL, NULL, 7, 7, 'Inizio programma - Settimana 1', '2026-08-27 11:28:42'),
(3, 2, '2026-08-28', '70.00', '100.00', '35.00', '88.00', 7, 7, '', '2026-08-28 18:23:38');

-- --------------------------------------------------------

--
-- Struttura della tabella `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `profile_photo`, `role`, `created_at`, `updated_at`) VALUES
(1, 'domenico.ambrosio', 'domenico@example.com', '$2a$12$kZWR2LdIzDmetL/aA28Ehu41SfOhLcHkcARsuZHj69RtyIbIFUVZW', 'Domenico Ambrosio', NULL, 'user', '2026-08-27 11:27:34', '2026-08-27 12:02:08'),
(2, 'Andrea', 'andrea.mercurio@edilgen.net', '$2y$10$HNcEe1XT5/rcr0qo7EMvk.ZzuN6N0NHcQktWs7yNjFSVcTqu/DBR6', 'Andrea Mercurio', NULL, 'admin', '2026-08-27 11:55:16', '2026-08-31 10:55:40'),
(3, 'gennaroc', 'gennarocano51@gmail.com', '$2y$10$2UyGIF0Gd9ohDYkOyPI6P.juLBumB6SVgrSmNRT8SLvJMFEEWfprG', 'Gennaro Canò', NULL, 'user', '2026-08-28 19:54:32', '2026-08-28 19:54:32'),
(4, 'Canó', 'marcocano91@hotmail.it', '$2y$10$3i/NgzrqR7oe2ZuyJOsx..MfKjgGbR4w7I5lqOtDfsN3ErOqUxrn2', 'Marco', NULL, 'user', '2026-08-28 19:56:33', '2026-08-28 19:56:33');

-- --------------------------------------------------------

--
-- Struttura della tabella `user_profiles`
--

DROP TABLE IF EXISTS `user_profiles`;
CREATE TABLE `user_profiles` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `age` int DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(4,2) DEFAULT NULL,
  `training_level` enum('beginner','intermediate','advanced') COLLATE utf8mb4_unicode_ci DEFAULT 'intermediate',
  `goal` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'muscle_gain',
  `bmr` decimal(6,2) DEFAULT NULL,
  `tdee` decimal(6,2) DEFAULT NULL,
  `target_calories` decimal(6,2) DEFAULT NULL,
  `target_protein` decimal(6,2) DEFAULT '160.00',
  `target_carbs` decimal(6,2) DEFAULT '400.00',
  `target_fats` decimal(6,2) DEFAULT '85.00',
  `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `age`, `height`, `weight`, `bmi`, `training_level`, `goal`, `bmr`, `tdee`, `target_calories`, `target_protein`, `target_carbs`, `target_fats`, `profile_photo`, `created_at`, `updated_at`) VALUES
(1, 1, 30, '187.00', '72.00', '20.59', 'intermediate', 'muscle_gain', '1743.75', '2702.81', '3102.81', '160.00', '400.00', '85.00', NULL, '2026-08-27 11:27:34', '2026-08-27 14:30:33'),
(2, 2, 34, '174.00', '74.00', '24.44', 'intermediate', 'muscle_gain', '1662.50', '2576.88', '2976.88', '160.00', '400.00', '85.00', NULL, '2026-08-27 11:55:16', '2026-08-28 18:30:21'),
(3, 3, NULL, NULL, NULL, NULL, 'intermediate', 'muscle_gain', NULL, NULL, NULL, '160.00', '400.00', '85.00', NULL, '2026-08-28 19:54:32', '2026-08-28 19:54:32'),
(4, 4, NULL, NULL, NULL, NULL, 'intermediate', 'muscle_gain', NULL, NULL, NULL, '160.00', '400.00', '85.00', NULL, '2026-08-28 19:56:33', '2026-08-28 19:56:33');

-- --------------------------------------------------------

--
-- Struttura della tabella `workouts`
--

DROP TABLE IF EXISTS `workouts`;
CREATE TABLE `workouts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `focus_area` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_minutes` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `workouts`
--

INSERT INTO `workouts` (`id`, `user_id`, `name`, `day_of_week`, `focus_area`, `duration_minutes`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Allenamento A - Spinta & Gambe', 'monday', 'Spinta + Quadricipiti', 65, 1, '2026-08-27 11:28:01', '2026-08-27 11:28:01'),
(2, 1, 'Allenamento B - Trazione & Catena Posteriore', 'wednesday', 'Trazione + Catena posteriore', 70, 1, '2026-08-27 11:28:01', '2026-08-27 11:28:01'),
(3, 1, 'Allenamento C - Volume & Dettagli', 'friday', 'Volume + Dettagli', 60, 1, '2026-08-27 11:28:01', '2026-08-27 11:28:01'),
(7, 2, 'Schiena', 'friday', 'Schiena', 60, 1, '2026-08-28 18:15:50', '2026-08-28 18:15:50'),
(8, 2, 'Pinco', 'friday', '', 60, 1, '2026-08-28 18:21:04', '2026-08-28 18:21:04');

-- --------------------------------------------------------

--
-- Struttura della tabella `workout_exercises`
--

DROP TABLE IF EXISTS `workout_exercises`;
CREATE TABLE `workout_exercises` (
  `id` int NOT NULL,
  `workout_id` int NOT NULL,
  `exercise_id` int NOT NULL,
  `order_num` int NOT NULL,
  `sets` int NOT NULL,
  `reps` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rest_time` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rpe` int DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `workout_exercises`
--

INSERT INTO `workout_exercises` (`id`, `workout_id`, `exercise_id`, `order_num`, `sets`, `reps`, `rest_time`, `rpe`, `weight`, `notes`) VALUES
(7, 2, 7, 1, 4, '6-8', '2:30', 8, NULL, 'Schiena neutra, carico sui talloni'),
(8, 2, 8, 2, 4, '8-10', '1:30', 8, NULL, 'Presa larga, extrarotazione in chiusura'),
(9, 2, 9, 3, 3, '10-12', '1:30', 7, NULL, 'Singolo braccio, gomito lungo il fianco'),
(10, 2, 10, 4, 4, '8-10', '1:30', 8, NULL, 'Seduto, no slancio, controllo completo'),
(11, 2, 11, 5, 3, '10-12', '1:00', 7, NULL, 'No oscillazione tronco, extrarotazione'),
(12, 2, 12, 6, 3, 'Max', '1:00', 7, NULL, 'Core contratto, no inarcamento lombare'),
(13, 3, 13, 1, 4, '10-12', '1:30', 7, NULL, 'Piedi larghezza spalle, no lock ginocchia'),
(14, 3, 14, 2, 3, '8-10', '1:30', 7, NULL, 'Pettorali/tricipiti, discesa controllata'),
(15, 3, 15, 3, 3, '12', '1:30', 7, NULL, 'Schiena spessore, extrarotazione'),
(16, 3, 16, 4, 3, '12', '1:00', 7, NULL, 'Deltoidi anteriori, no slancio'),
(17, 3, 17, 5, 3, '15', '1:00', 7, NULL, 'Deltoidi posteriori, cuffia rotatori'),
(18, 3, 18, 6, 3, '12', '1:00', 7, NULL, 'Bicipiti/brachiale, presa neutra'),
(49, 8, 4, 1, 3, '10', '1:30', 7, NULL, ''),
(51, 7, 8, 1, 3, '10', '1:30', 7, '40.00', ''),
(52, 7, 2, 2, 3, '10', '1:30', 7, '20.00', ''),
(53, 7, 9, 3, 3, '10', '1:30', 7, '20.00', ''),
(54, 1, 1, 1, 4, '8-10', '2:00', 8, '40.00', 'Profondità parallelo, ginocchia in linea piedi'),
(55, 1, 2, 2, 4, '8-10', '1:30', 8, '30.00', 'Scapole retratte, controllo eccentrico 2\"'),
(56, 1, 3, 3, 3, '12/gamba', '1:30', 7, '10.00', 'Passo ampio, busto eretto'),
(57, 1, 4, 4, 3, '10-12', '1:30', 7, '20.00', 'Focus pettorali alti'),
(58, 1, 5, 5, 4, '12-15', '1:00', 7, '12.00', 'Gomiti leggermente flessi, no slancio'),
(59, 1, 6, 6, 3, '12', '1:00', 7, '10.00', 'Isolamento tricipiti');

-- --------------------------------------------------------

--
-- Struttura della tabella `workout_sessions`
--

DROP TABLE IF EXISTS `workout_sessions`;
CREATE TABLE `workout_sessions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `workout_id` int NOT NULL,
  `session_date` datetime NOT NULL,
  `status` enum('in_progress','completed','partial') COLLATE utf8mb4_unicode_ci DEFAULT 'in_progress',
  `is_partial` tinyint(1) DEFAULT '0',
  `completed_exercises_count` int DEFAULT '0',
  `total_exercises_count` int DEFAULT '0',
  `duration_minutes` int DEFAULT NULL,
  `total_volume` decimal(8,2) DEFAULT NULL,
  `calories_burned` decimal(6,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `workout_sessions`
--

INSERT INTO `workout_sessions` (`id`, `user_id`, `workout_id`, `session_date`, `status`, `is_partial`, `completed_exercises_count`, `total_exercises_count`, `duration_minutes`, `total_volume`, `calories_burned`, `notes`, `created_at`) VALUES
(7, 1, 3, '2026-08-28 18:39:49', 'in_progress', 0, 6, 6, 60, NULL, NULL, NULL, '2026-08-28 16:39:49'),
(8, 2, 7, '2026-08-28 20:17:39', 'in_progress', 0, 2, 3, 60, NULL, NULL, NULL, '2026-08-28 18:17:39');

-- --------------------------------------------------------

--
-- Struttura della tabella `workout_sets`
--

DROP TABLE IF EXISTS `workout_sets`;
CREATE TABLE `workout_sets` (
  `id` int NOT NULL,
  `session_id` int NOT NULL,
  `exercise_id` int NOT NULL,
  `set_number` int NOT NULL,
  `weight` decimal(5,2) DEFAULT '0.00',
  `reps` int DEFAULT '0',
  `completed` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `workout_sets`
--

INSERT INTO `workout_sets` (`id`, `session_id`, `exercise_id`, `set_number`, `weight`, `reps`, `completed`, `created_at`) VALUES
(57, 7, 17, 1, '18.00', 15, 1, '2026-08-28 17:27:52'),
(58, 7, 17, 2, '18.00', 15, 1, '2026-08-28 17:27:52'),
(59, 7, 17, 3, '18.00', 15, 1, '2026-08-28 17:27:52'),
(63, 8, 8, 1, '40.00', 10, 1, '2026-08-28 18:18:07'),
(64, 8, 8, 2, '40.00', 10, 1, '2026-08-28 18:18:07'),
(65, 8, 8, 3, '40.00', 10, 1, '2026-08-28 18:18:07'),
(66, 8, 2, 1, '20.00', 10, 1, '2026-08-28 18:29:29'),
(67, 8, 2, 2, '20.00', 10, 1, '2026-08-28 18:29:29'),
(68, 8, 2, 3, '20.00', 10, 1, '2026-08-28 18:29:29'),
(69, 7, 13, 1, '63.00', 12, 1, '2026-08-31 08:09:07'),
(70, 7, 13, 2, '75.00', 12, 1, '2026-08-31 08:09:07'),
(71, 7, 13, 3, '86.00', 12, 1, '2026-08-31 08:09:07'),
(72, 7, 13, 4, '97.00', 12, 1, '2026-08-31 08:09:07'),
(79, 7, 15, 1, '45.00', 12, 1, '2026-08-31 08:09:32'),
(80, 7, 15, 2, '52.00', 12, 1, '2026-08-31 08:09:32'),
(81, 7, 15, 3, '52.00', 12, 1, '2026-08-31 08:09:32'),
(82, 7, 16, 1, '10.00', 12, 1, '2026-08-31 08:09:38'),
(83, 7, 16, 2, '10.00', 12, 1, '2026-08-31 08:09:38'),
(84, 7, 16, 3, '10.00', 12, 1, '2026-08-31 08:09:38'),
(85, 7, 18, 1, '12.50', 12, 1, '2026-08-31 09:04:36'),
(86, 7, 18, 2, '12.50', 12, 1, '2026-08-31 09:04:36'),
(87, 7, 18, 3, '12.50', 12, 1, '2026-08-31 09:04:36'),
(88, 7, 14, 1, '72.00', 12, 1, '2026-08-31 09:05:08'),
(89, 7, 14, 2, '72.00', 12, 1, '2026-08-31 09:05:08'),
(90, 7, 14, 3, '72.00', 12, 1, '2026-08-31 09:05:08');

-- --------------------------------------------------------

--
-- Struttura della tabella `workout_warmups`
--

DROP TABLE IF EXISTS `workout_warmups`;
CREATE TABLE `workout_warmups` (
  `id` int NOT NULL,
  `workout_id` int NOT NULL,
  `order_num` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration_minutes` int DEFAULT '5',
  `description` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `workout_warmups`
--

INSERT INTO `workout_warmups` (`id`, `workout_id`, `order_num`, `name`, `duration_minutes`, `description`) VALUES
(1, 1, 1, 'Cardio leggero (cyclette/tapis)', 5, 'Riscaldamento cardiovascolare generale'),
(2, 1, 2, 'Mobilità articolare (spalle, anche, caviglie)', 3, 'Rotazioni e mobilità dinamica'),
(3, 1, 3, 'Serie di avvicinamento al carico', 2, '2-3 serie leggere con il 40-50% del carico di lavoro'),
(4, 2, 1, 'Cardio leggero (cyclette/tapis)', 5, 'Riscaldamento cardiovascolare generale'),
(5, 2, 2, 'Mobilità articolare (spalle, anche, caviglie)', 3, 'Rotazioni e mobilità dinamica'),
(6, 2, 3, 'Serie di avvicinamento al carico', 2, '2-3 serie leggere con il 40-50% del carico di lavoro'),
(7, 3, 1, 'Cardio leggero (cyclette/tapis)', 5, 'Riscaldamento cardiovascolare generale'),
(8, 3, 2, 'Mobilità articolare (spalle, anche, caviglie)', 3, 'Rotazioni e mobilità dinamica'),
(9, 3, 3, 'Serie di avvicinamento al carico', 2, '2-3 serie leggere con il 40-50% del carico di lavoro');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `exercises`
--
ALTER TABLE `exercises`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_name` (`name`);

--
-- Indici per le tabelle `foods`
--
ALTER TABLE `foods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_name` (`name`);

--
-- Indici per le tabelle `meals`
--
ALTER TABLE `meals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_date` (`meal_date`),
  ADD KEY `idx_meal_type` (`meal_type`);

--
-- Indici per le tabelle `meal_foods`
--
ALTER TABLE `meal_foods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `food_id` (`food_id`),
  ADD KEY `idx_meal_id` (`meal_id`);

--
-- Indici per le tabelle `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indici per le tabelle `progress_tracking`
--
ALTER TABLE `progress_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_date` (`track_date`);

--
-- Indici per le tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`);

--
-- Indici per le tabelle `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indici per le tabelle `workouts`
--
ALTER TABLE `workouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_day` (`day_of_week`);

--
-- Indici per le tabelle `workout_exercises`
--
ALTER TABLE `workout_exercises`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_workout_id` (`workout_id`),
  ADD KEY `idx_exercise_id` (`exercise_id`);

--
-- Indici per le tabelle `workout_sessions`
--
ALTER TABLE `workout_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `workout_id` (`workout_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_session_date` (`session_date`);

--
-- Indici per le tabelle `workout_sets`
--
ALTER TABLE `workout_sets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_exercise` (`exercise_id`);

--
-- Indici per le tabelle `workout_warmups`
--
ALTER TABLE `workout_warmups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_workout` (`workout_id`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `exercises`
--
ALTER TABLE `exercises`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT per la tabella `foods`
--
ALTER TABLE `foods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT per la tabella `meals`
--
ALTER TABLE `meals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT per la tabella `meal_foods`
--
ALTER TABLE `meal_foods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT per la tabella `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `progress_tracking`
--
ALTER TABLE `progress_tracking`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT per la tabella `workouts`
--
ALTER TABLE `workouts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT per la tabella `workout_exercises`
--
ALTER TABLE `workout_exercises`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT per la tabella `workout_sessions`
--
ALTER TABLE `workout_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT per la tabella `workout_sets`
--
ALTER TABLE `workout_sets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT per la tabella `workout_warmups`
--
ALTER TABLE `workout_warmups`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `meals`
--
ALTER TABLE `meals`
  ADD CONSTRAINT `meals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `meal_foods`
--
ALTER TABLE `meal_foods`
  ADD CONSTRAINT `meal_foods_ibfk_1` FOREIGN KEY (`meal_id`) REFERENCES `meals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `meal_foods_ibfk_2` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `progress_tracking`
--
ALTER TABLE `progress_tracking`
  ADD CONSTRAINT `progress_tracking_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `workouts`
--
ALTER TABLE `workouts`
  ADD CONSTRAINT `workouts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `workout_exercises`
--
ALTER TABLE `workout_exercises`
  ADD CONSTRAINT `workout_exercises_ibfk_1` FOREIGN KEY (`workout_id`) REFERENCES `workouts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workout_exercises_ibfk_2` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `workout_sessions`
--
ALTER TABLE `workout_sessions`
  ADD CONSTRAINT `workout_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workout_sessions_ibfk_2` FOREIGN KEY (`workout_id`) REFERENCES `workouts` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `workout_sets`
--
ALTER TABLE `workout_sets`
  ADD CONSTRAINT `workout_sets_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `workout_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workout_sets_ibfk_2` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `workout_warmups`
--
ALTER TABLE `workout_warmups`
  ADD CONSTRAINT `workout_warmups_ibfk_1` FOREIGN KEY (`workout_id`) REFERENCES `workouts` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
