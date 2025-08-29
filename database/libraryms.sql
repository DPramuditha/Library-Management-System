-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 29, 2025 at 07:49 AM
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
) ENGINE=MyISAM AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `isbn`, `category`, `description`, `cover_image_url`, `publication_year`, `publisher`, `total_copies`, `available_copies`, `status`, `created_at`, `updated_at`) VALUES
(1, 'The Great Gatsby', 'F. Scott Fitzgerald', NULL, 'Classic Literature', 'A classic American novel set in the Jazz Age', '../assets/book1.jpg', '1925', 'Charles Scribner\'s Sons', 3, 2, 'available', '2025-08-05 06:49:37', '2025-08-05 06:49:37'),
(2, 'To Kill a Mockingbird', 'Harper Lee', NULL, 'Fiction', 'A gripping tale of racial injustice and childhood innocence', '../assets/book2.jpg', '1960', 'J.B. Lippincott & Co.', 4, 2, 'available', '2025-08-05 06:49:37', '2025-08-14 15:16:26'),
(3, '1984', 'George Orwell', NULL, 'Dystopian', 'A dystopian social science fiction novel', '../assets/book3.jpg', '1949', 'Secker & Warburg', 2, 0, 'borrowed', '2025-08-05 06:49:37', '2025-08-09 19:20:20'),
(4, 'Pride and Prejudice', 'Jane Austen', NULL, 'Romance', 'A romantic novel of manners', '../assets/book4.jpg', '0000', 'T. Egerton', 3, 0, 'borrowed', '2025-08-05 06:49:37', '2025-08-05 07:20:28'),
(15, 'Gamperaliya', 'Martin Wickramasinghe', NULL, 'Fiction', 'A classic Sinhala novel depicting the decline of a traditional Walauwa family and the rise of a new social order in rural Sri Lanka.', 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1290582737i/9757652.jpg', '1944', 'Tisara Prakasakayo', 4, 4, 'available', '2025-08-15 07:35:18', '2025-08-25 18:17:22'),
(6, 'Pride and Prejudice', 'Jane Austen', '978-0141439518', 'Romance', 'A classic novel about love, class, and societal expectations in 19th-century England.', '', '0000', 'T. Egerton', 4, 2, 'available', '2025-08-10 18:28:24', '2025-08-10 18:30:29'),
(7, 'Dune', 'Frank Herbert', '978-0441172719', 'Science Fiction', 'A sweeping sci-fi epic about politics, religion, and survival on a desert planet.', '', '1965', 'Chilton Books', 3, 0, 'borrowed', '2025-08-10 18:28:24', '2025-08-10 18:34:55'),
(14, 'Madol Doova', 'Martin Wickramasinghe', '', 'Fiction', 'A beloved Sinhala coming-of-age novel about Upali and Jinna’s adventures on a coastal island, exploring friendship and rural life in Sri Lanka.', 'https://images-na.ssl-images-amazon.com/images/S/compressed.photo.goodreads.com/books/1333331295i/13572653.jpg', '1947', 'Tisara Prakasakayo', 5, 4, 'available', '2025-08-15 07:21:19', '2025-08-25 18:19:34'),
(9, 'The Catcher in the Rye', 'J.D. Salinger', '978-0316769488', 'Fiction', 'A story of teenage angst and alienation as Holden Caulfield navigates life in New York.', '', '1951', 'Little, Brown and Company', 3, 4, 'available', '2025-08-10 18:30:10', '2025-08-16 06:10:59'),
(10, 'The Name of the Wind', 'Patrick Rothfuss', '978-0756404079', 'Fantasy', 'A tale of Kvothe, a gifted young man who becomes a legendary figure through magic and adventure.', '', '2007', 'DAW Books', 4, 2, 'available', '2025-08-10 18:30:10', '2025-08-11 18:26:23'),
(16, 'Viragaya', 'Martin Wickramasinghe', NULL, 'Fiction', 'A profound Sinhala novel exploring the introspective life of Aravinda, a man detached from material desires, set against Sri Lanka’s cultural backdrop.', 'https://www.kbooks.lk/image/cache/catalog/sarasa/viragaya_martin_wickramasinghe-500x500.jpg', '1956', 'Tisara Prakasakayo', 3, 2, 'borrowed', '2025-08-15 07:35:18', '2025-08-25 18:21:21'),
(13, 'The Alchemist', 'Paulo Coelho', '978-0062315007', 'Fiction', 'A philosophical story about following one’s dreams, centered on a young Andalusian shepherd.', '', '1988', 'HarperOne', 4, 1, 'available', '2025-08-10 18:30:10', '2025-08-12 20:16:37'),
(17, 'Hevanella', 'K. Jayatillake', NULL, 'Fiction', 'A Sinhala novel of adventure and rebellion, following a young man’s journey through societal challenges in colonial Sri Lanka.', '', '1960', 'S. Godage & Brothers', 3, 3, 'available', '2025-08-15 07:35:18', '2025-08-15 07:35:18'),
(18, 'Amba Yahaluwo', 'T.B. Ilangaratne', NULL, 'Fiction', 'A touching Sinhala novel about two boys from different social classes, exploring friendship and social divides in rural Sri Lanka.', 'https://i.gr-assets.com/images/S/compressed.photo.goodreads.com/books/1301313687i/4575781._UX160_.jpg', '1957', 'M.D. Gunasena', 4, 4, 'available', '2025-08-15 07:35:18', '2025-08-25 18:20:43'),
(31, 'Kaliyugaya', 'Martin Wickramasinghe', NULL, 'Fiction', '\'A Sinhala novel continuing the saga of Gamperaliya, exploring the impact of modernization on a traditional Sri Lankan family.', '', '1997', 'Tisara Prakasakayo', 4, 2, 'available', '2025-08-19 04:46:49', '2025-08-19 07:19:36'),
(32, 'The Seven Moons of Maali Almeida', 'Shehan Karunatilaka', '978-1908745903', 'Fiction', 'A satirical Sinhala novel about a war photographer navigating the afterlife to solve his murder during Sri Lanka’s civil war. Winner of the 2022 Booker Prize.', 'https://img.chirpbooks.com/image/upload/q_auto:good,w_960,h_960/v1685818326/cover_images/tantor/9781696611251.jpg', '2022', 'Sort of Books', 5, 4, 'available', '2025-08-25 19:27:03', '2025-08-25 19:27:03'),
(33, 'Code Name Faust', 'A.G. Taylor', '978-1838777838', 'Fiction', 'A thrilling young adult novel involving espionage and adventure, appealing to Sri Lankan readers of action-packed stories.', 'https://cms.sarasavi.lk/storage/product/1838777830.jpg', '2023', 'Bonnier Books', 3, 3, 'available', '2025-08-25 19:27:03', '2025-08-25 19:27:03'),
(34, 'Mile High', 'Liz Tomforde', '978-1399728547', 'Fiction', 'A contemporary romance novel set in the world of sports, popular among Sri Lankan readers for its emotional depth.', 'https://cms.sarasavi.lk/storage/product/1399728547.jpg', '2022', 'Hodder & Stoughton', 3, 2, 'borrowed', '2025-08-25 19:27:03', '2025-08-25 19:27:03'),
(35, 'Every Time I Go on Vacation Someone Dies', 'Catherine Mack', '978-1035032082', 'Fiction', 'A humorous mystery novel about a writer whose vacations turn deadly, engaging Sri Lankan fans of cozy mysteries.', 'https://cms.sarasavi.lk/storage/product/1035032082.jpg', '2024', 'Macmillan', 4, 4, 'available', '2025-08-25 19:27:03', '2025-08-25 19:27:03'),
(36, 'Camellia', 'Kumuduni Dias Hapangama', '978-9553128812', 'Fiction', 'A Sinhala novel exploring family dynamics and social issues in modern Sri Lanka, resonating with local readers.', 'https://cms.sarasavi.lk/storage/product/9553128815.jpg', '2020', 'Sarasavi Publishers', 3, 3, 'available', '2025-08-25 19:27:03', '2025-08-25 19:27:03'),
(38, 'Wednesday', 'Gaby Morgan', '978-0241760741', 'Fiction', 'A collection of poems inspired by the Wednesday Addams character, appealing to Sri Lankan fans of gothic and pop culture.', 'https://cms.sarasavi.lk/storage/product/0241760747.jpg', '2023', 'Penguin Random House', 3, 2, 'borrowed', '2025-08-25 19:27:03', '2025-08-25 19:27:03'),
(45, 'Assassin’s Creed: Underworld', 'Oliver Bowden', NULL, 'Fiction', 'A historical action novel tied to the Assassin’s Creed series, popular among Sri Lankan gamers and adventure readers.', 'https://cms.sarasavi.lk/storage/product/1405918861.jpg', '2015', 'Penguin Books', 4, 3, 'available', '2025-08-25 19:42:46', '2025-08-25 19:42:46');

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
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `borrowed_books`
--

INSERT INTO `borrowed_books` (`id`, `user_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, 9, '2025-08-11', '2025-08-25', '2025-08-12', 'returned', '2025-08-11 18:23:03', '2025-08-12 10:43:27'),
(5, 2, 2, '2025-08-14', '2025-08-28', NULL, 'borrowed', '2025-08-14 15:16:26', '2025-08-14 15:16:26'),
(4, 4, 13, '2025-08-11', '2025-08-25', NULL, 'borrowed', '2025-08-11 20:00:08', '2025-08-11 20:00:08'),
(8, 2, 15, '2025-08-16', '2025-08-15', NULL, 'borrowed', '2025-08-16 06:52:16', '2025-08-16 06:52:16');

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
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `role`, `created_at`) VALUES
(1, 'dimuthu pramuditha', 'dimuthu02@gmail.com', '$2y$10$3n2TJ9OhKJOuTkYRFjGjFOPBeY3fZurQc6vliK2EXCUsRyca7K7ya', '0712564978', '80/A Gampaha, Kirindiwela', 'librarian', '2025-08-09 10:17:01'),
(2, 'dimuthu pramuditha', 'dimuthu01@gmail.com', '$2y$10$zBeqTBf1zJdMliEA/TmrHeMkCcfRRpTZLgp7TaHW1w9wfo4HHZ9Na', '0712564974', '80/N Gampaha, Kirindiwela', 'student', '2025-08-09 10:18:13'),
(4, 'dimuthu pram', 'dimuthu03@gmail.com', '$2y$10$Q7feKuijtop/uuXIpeNSkeCXhxze6cfgPbux3kq7JBaPLkO1EGMRe', '0712564988', '80/A Gampaha, Kirindiwela', 'student', '2025-08-09 15:23:17'),
(7, 'Admin User', 'admin@library.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1234567890', 'Admin Office', 'admin', '2025-08-17 04:48:53');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
