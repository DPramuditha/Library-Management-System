-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 30, 2025 at 08:29 AM
-- Server version: 8.3.0
-- PHP Version: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `libraryms`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
CREATE TABLE IF NOT EXISTS `books` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `description` text,
  `cover_image_url` varchar(500) DEFAULT NULL,
  `publication_year` year DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `total_copies` int DEFAULT '1',
  `available_copies` int DEFAULT '1',
  `status` enum('available','borrowed','unavailable') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `isbn` (`isbn`)
) ENGINE=MyISAM AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `isbn`, `category`, `description`, `cover_image_url`, `publication_year`, `publisher`, `total_copies`, `available_copies`, `status`, `created_at`, `updated_at`) VALUES
(1, 'The Seven Moons of Maali Almeida', 'Shehan Karunatilaka', 'Null', 'Fiction', 'A satirical Sinhala novel about a war photographer navigating the afterlife to solve his murder during Sri Lanka’s civil war. Winner of the 2022 Booker Prize.', 'https://img.chirpbooks.com/image/upload/q_auto:good,w_960,h_960/v1685818326/cover_images/tantor/9781696611251.jpg', '2022', 'Sort of Books', 5, 4, 'available', '2025-08-25 13:57:03', '2025-08-30 06:55:36'),
(2, 'Code Name Faust', 'A.G. Taylor', '978-1838777838', 'Fiction', 'A thrilling young adult novel involving espionage and adventure, appealing to Sri Lankan readers of action-packed stories.', 'https://cms.sarasavi.lk/storage/product/1838777830.jpg', '2023', 'Bonnier Books', 3, 3, 'available', '2025-08-25 13:57:03', '2025-08-25 13:57:03'),
(3, 'Mile High', 'Liz Tomforde', '978-1399728547', 'Fiction', 'A contemporary romance novel set in the world of sports, popular among Sri Lankan readers for its emotional depth.', 'https://cms.sarasavi.lk/storage/product/1399728547.jpg', '2022', 'Hodder & Stoughton', 3, 2, 'borrowed', '2025-08-25 13:57:03', '2025-08-25 13:57:03'),
(4, 'Every Time I Go on Vacation Someone Dies', 'Catherine Mack', '978-1035032082', 'Fiction', 'A humorous mystery novel about a writer whose vacations turn deadly, engaging Sri Lankan fans of cozy mysteries.', 'https://cms.sarasavi.lk/storage/product/1035032082.jpg', '2024', 'Macmillan', 4, 4, 'available', '2025-08-25 13:57:03', '2025-08-25 13:57:03'),
(5, 'Camellia', 'Kumuduni Dias Hapangama', NULL, 'Fiction', 'A Sinhala novel exploring family dynamics and social issues in modern Sri Lanka, resonating with local readers.', 'https://cms.sarasavi.lk/storage/product/9553128815.jpg', '2020', 'Sarasavi Publishers', 3, 3, 'available', '2025-08-25 13:57:03', '2025-08-30 06:57:11'),
(6, 'Wednesday', 'Gaby Morgan', NULL, 'Fiction', 'A collection of poems inspired by the Wednesday Addams character, appealing to Sri Lankan fans of gothic and pop culture.', 'https://cms.sarasavi.lk/storage/product/0241760747.jpg', '2023', 'Penguin Random House', 3, 1, 'borrowed', '2025-08-25 13:57:03', '2025-08-30 08:10:34'),
(7, 'Assassin’s Creed: Underworld', 'Oliver Bowden', NULL, 'Fiction', 'A historical action novel tied to the Assassin’s Creed series, popular among Sri Lankan gamers and adventure readers.', 'https://cms.sarasavi.lk/storage/product/1405918861.jpg', '2015', 'Penguin Books', 4, 3, 'available', '2025-08-25 14:12:46', '2025-08-25 14:12:46'),
(8, 'Gamperaliya', 'Martin Wickramasinghe', NULL, 'Fiction', 'A classic Sinhala novel depicting the decline of a traditional Walauwa family and the rise of a new social order in rural Sri Lanka.', 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1290582737i/9757652.jpg', '1944', 'Tisara Prakasakayo', 4, 4, 'available', '2025-08-15 02:05:18', '2025-08-25 12:47:22'),
(9, 'Madol Doova', 'Martin Wickramasinghe', '', 'Fiction', 'A beloved Sinhala coming-of-age novel about Upali and Jinna’s adventures on a coastal island, exploring friendship and rural life in Sri Lanka.', 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1333331295i/13572653.jpg', '1947', 'Tisara Prakasakayo', 5, 2, 'available', '2025-08-15 01:51:19', '2025-08-30 08:13:00'),
(10, 'Viragaya', 'Martin Wickramasinghe', NULL, 'Fiction', 'A profound Sinhala novel exploring the introspective life of Aravinda, a man detached from material desires, set against Sri Lanka’s cultural backdrop.', 'https://www.kbooks.lk/image/cache/catalog/sarasa/viragaya_martin_wickramasinghe-500x500.jpg', '1956', 'Tisara Prakasakayo', 3, 2, 'borrowed', '2025-08-15 02:05:18', '2025-08-25 12:51:21'),
(11, 'Amba Yahaluwo', 'T.B. Ilangaratne', NULL, 'Fiction', 'A touching Sinhala novel about two boys from different social classes, exploring friendship and social divides in rural Sri Lanka.', 'https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1301313687i/4575781._UX160_.jpg', '1957', 'M.D. Gunasena', 4, 4, 'available', '2025-08-15 02:05:18', '2025-08-25 12:50:43'),
(12, 'In Too Deep', 'Lee Child, Andrew Child', NULL, 'Fiction', 'A gripping thriller featuring Jack Reacher, popular among Sri Lankan readers for its fast-paced action and suspense.', 'https://cms.sarasavi.lk/storage/product/1804993670.jpg', '2024', 'Bantam Press', 4, 3, 'available', '2025-08-30 06:24:59', '2025-08-30 06:58:58'),
(13, 'The Emperor of Gladness', 'Ocean Vuong', NULL, 'Fiction', 'A bestselling novel about an unlikely friendship in a post-industrial town, resonating with Sri Lankan readers for its emotional depth.', 'https://cms.sarasavi.lk/storage/product/1787335410.jpg', '2024', 'Jonathan Cape', 4, 3, 'available', '2025-08-30 06:24:59', '2025-08-30 06:59:20'),
(14, 'My Naughty Little Sister', 'Dorothy Edwards', NULL, 'Fiction', 'A charming children’s book about a mischievous girl, popular in Sri Lankan schools for young readers.', 'https://cms.sarasavi.lk/storage/product/1405253347.jpg', '2010', 'Egmont Books', 3, 2, 'borrowed', '2025-08-30 06:24:59', '2025-08-30 06:59:37'),
(15, 'Power Up Activity Book 3', 'Caroline Nixon, Michael Tomlinson', NULL, 'Non-Fiction', 'An educational activity book for young learners, widely used in Sri Lankan schools to enhance English skills.', 'https://cms.sarasavi.lk/storage/product/1009599313.jpg', '2022', 'Cambridge University Press', 5, 4, 'available', '2025-08-30 06:24:59', '2025-08-30 06:59:52'),
(16, 'Cambridge English for Nursing - Intermediate', 'Virginia Allum, Patricia McGarr', NULL, 'Non-Fiction', 'A specialized English textbook for nursing students, popular in Sri Lankan medical training programs.', 'https://cms.sarasavi.lk/storage/product/1009470124.jpg', '2021', 'Cambridge University Press', 3, 3, 'available', '2025-08-30 06:24:59', '2025-08-30 07:00:11'),
(17, 'Baeddegama Rasaswadaya Ha Vicharaya - AL', 'Unknown Author', NULL, 'Non-Fiction', 'A Sinhala study guide for Advanced Level students, analyzing the novel Baeddegama, widely used in Sri Lankan schools.', 'https://cms.sarasavi.lk/storage/product/9556964010.jpg', '2018', 'Sarasavi Publishers', 4, 4, 'available', '2025-08-30 06:24:59', '2025-08-30 07:00:26'),
(18, 'Wanduru Paetiyage Kathandare', 'Unknown Author', NULL, 'Fiction', 'A Sinhala novel exploring traditional Sri Lankan storytelling, appealing to local readers for its cultural narratives.', 'https://cms.sarasavi.lk/storage/product/9553132642.jpg', '2019', 'Sarasavi Publishers', 3, 3, 'available', '2025-08-30 06:24:59', '2025-08-30 07:00:39'),
(19, 'One Golden Summer', 'Clare Lydon, T.B. Markinson', NULL, 'Fiction', 'A heartwarming romance novel, attracting Sri Lankan readers for its light and engaging storytelling.', 'https://cms.sarasavi.lk/storage/product/1405965436.jpg', '2023', 'Penguin Books', 3, 2, 'borrowed', '2025-08-30 06:24:59', '2025-08-30 07:00:53'),
(20, 'Dear Spookysaur', 'Rose Impey', NULL, 'Fiction', 'A fun children’s book about a friendly dinosaur, popular among young Sri Lankan readers for its playful narrative.', 'https://cms.sarasavi.lk/storage/product/1407193848.jpg', '2020', 'Scholastic', 3, 3, 'available', '2025-08-30 06:24:59', '2025-08-30 07:01:06'),
(21, 'Alexander the Great Dane', 'A.J. Griffiths', NULL, 'Fiction', 'A humorous children’s story about a adventurous dog, engaging Sri Lankan young readers with its lighthearted plot.', 'https://cms.sarasavi.lk/storage/product/1910851469.jpg', '2017', 'Matador', 3, 2, 'borrowed', '2025-08-30 06:24:59', '2025-08-30 07:01:19'),
(22, 'Heen Saeraya', 'Unknown Author', NULL, 'Fiction', 'A Sinhala novel exploring themes of rural life and personal struggle in Sri Lanka, popular among local readers for its cultural resonance.', 'https://cms.sarasavi.lk/storage/product/10067990-0042.jpg', '2019', 'Sarasavi Publishers', 3, 3, 'available', '2025-08-30 06:39:21', '2025-08-30 07:01:31'),
(23, 'Wake Up', 'Unknown Author', NULL, 'Fiction', 'A contemporary novel with themes of personal awakening, appealing to Sri Lankan readers for its relatable narrative.', 'https://cms.sarasavi.lk/storage/product/0008392617.jpg', '2022', 'Simon & Schuster', 3, 0, 'unavailable', '2025-08-30 07:05:49', '2025-08-30 07:29:42'),
(24, 'The Reason', 'Catherine Bennetto', NULL, 'Fiction', 'A heartwarming romance novel about life’s unexpected turns, popular among Sri Lankan readers for its emotional storytelling.', 'https://cms.sarasavi.lk/storage/product/1471165795.jpg', '2017', 'Simon & Schuster', 3, 2, 'borrowed', '2025-08-30 07:05:49', '2025-08-30 07:05:49'),
(25, 'A Game of Thrones', 'George R.R. Martin', NULL, 'Fiction', 'The first book in the epic fantasy series A Song of Ice and Fire, widely popular in Sri Lanka for its intricate world-building.', 'https://cms.sarasavi.lk/storage/product/0007548230.jpg', '1996', 'Bantam Books', 5, 4, 'available', '2025-08-30 07:05:49', '2025-08-30 07:05:49'),
(26, 'Assassin’s Creed', 'Oliver Bowden', NULL, 'Fiction', 'A historical action novel based on the Assassin’s Creed video game series, engaging Sri Lankan gamers and adventure readers.', 'https://cms.sarasavi.lk/storage/product/1405931507.jpg', '2009', 'Penguin Books', 4, 3, 'available', '2025-08-30 07:05:49', '2025-08-30 07:05:49'),
(27, 'The 100: Rebellion', 'Kass Morgan', NULL, 'Fiction', 'The fourth book in The 100 series, a sci-fi adventure popular among Sri Lankan young adult readers for its dystopian themes.', 'https://cms.sarasavi.lk/storage/product/1473648882.jpg', '2016', 'Hodder & Stoughton', 3, 2, 'available', '2025-08-30 07:05:49', '2025-08-30 08:07:45'),
(28, 'The 100: Day 21', 'Kass Morgan', NULL, 'Fiction', 'The second book in The 100 series, following survivors in a post-apocalyptic world, appealing to Sri Lankan fans of sci-fi.', 'https://cms.sarasavi.lk/storage/product/1444766902.jpg', '2014', 'Hodder & Stoughton', 3, 1, 'borrowed', '2025-08-30 07:05:49', '2025-08-30 08:11:17');

-- --------------------------------------------------------

--
-- Table structure for table `borrowed_books`
--

DROP TABLE IF EXISTS `borrowed_books`;
CREATE TABLE IF NOT EXISTS `borrowed_books` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `book_id` int NOT NULL,
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `book_id` (`book_id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `borrowed_books`
--

INSERT INTO `borrowed_books` (`id`, `user_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `status`, `created_at`, `updated_at`) VALUES
(9, 15, 27, '2025-08-30', '2025-09-13', NULL, 'borrowed', '2025-08-30 08:07:45', '2025-08-30 08:07:45'),
(10, 15, 6, '2025-08-30', '2025-09-13', NULL, 'borrowed', '2025-08-30 08:10:34', '2025-08-30 08:10:34'),
(11, 19, 28, '2025-08-30', '2025-09-13', NULL, 'borrowed', '2025-08-30 08:11:17', '2025-08-30 08:11:17'),
(12, 19, 9, '2025-08-30', '2025-09-13', NULL, 'borrowed', '2025-08-30 08:11:32', '2025-08-30 08:11:32'),
(13, 20, 9, '2025-08-30', '2025-09-13', NULL, 'borrowed', '2025-08-30 08:13:00', '2025-08-30 08:13:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `role` enum('student','librarian','admin') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `role`, `created_at`) VALUES
(1, 'Admin User', 'admin@library.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1234567890', 'Admin Office', 'admin', '2025-08-16 23:18:53'),
(15, 'dimuthu pramuditha', 'dimuthu01@gmail.com', '$2y$10$EDmwuhRMQpspmr5/Yb/u8uhVpXBN4SE7JSBTIwxpgigKzPGLl55uO', '0772345678', '80/N Gampaha, Kirindiwela', 'student', '2025-08-30 06:13:50'),
(16, 'dimuthu', 'dimuthu02@gmail.com', '$2y$10$B6FguG35CqrH4PetJ128XeyirXwzD0RQMttx6LyMNbizAcb4NPplG', '0772345687', '81/N Gampaha, Kirindiwela', 'librarian', '2025-08-30 07:25:58'),
(17, 'Librarian User', 'librarian@gmail.com', '$2y$10$a1apBopLMOwrVsgF/18l2O3IYGAfxaWEWq/QQkSfe3W2hBP0YdzOe', '0771234567', '81/N Gampaha, Kirindiwela', 'librarian', '2025-08-30 07:54:59'),
(18, 'Student User', 'student@gmail.com', '$2y$10$9Jwd4zofebti9.4mIPCxyOd2ms65WDZVBr0a9xsRhIzuU025fs4pu', '0771234567', '81/N Gampaha, Kirindiwela', 'student', '2025-08-30 07:58:13'),
(19, 'Amasha', 'amasha@gmail.com', '$2y$10$BlRPHY4xWeBD/A5EDXTonug3hQTNcQf.DfKdHQu2nbObL5gk7Vrdu', '0778976756', '07/2, high level road, Avissawella', 'student', '2025-08-30 08:05:10'),
(20, 'Dinushi', 'dinushi@gamil.com', '$2y$10$SQFYHIn8GVRXRnesH8VnpOOpg9m91BWsYibtihG1n9wFuC7zg81oe', '0778656756', '76/5, nugegoda , coclombo', 'student', '2025-08-30 08:06:29');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
