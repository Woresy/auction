-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2025-12-04 00:19:46
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `auction`
--
CREATE DATABASE IF NOT EXISTS `auction` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `auction`;
-- --------------------------------------------------------

--
-- 表的结构 `bid`
--

CREATE TABLE `bid` (
  `bidId` int(10) NOT NULL,
  `itemId` int(10) NOT NULL,
  `buyerId` int(10) NOT NULL,
  `bidAmount` float NOT NULL,
  `bidTime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `bid`
--

INSERT INTO `bid` (`bidId`, `itemId`, `buyerId`, `bidAmount`, `bidTime`) VALUES
(4, 9, 7, 1502, '2025-12-02 01:24:09'),
(5, 9, 9, 1504, '2025-12-02 13:37:48'),
(6, 13, 7, 510, '2025-12-02 15:14:32'),
(7, 13, 9, 512, '2025-12-02 15:15:43'),
(8, 13, 7, 520, '2025-12-02 15:17:01'),
(9, 9, 7, 1510, '2025-12-02 21:27:02'),
(10, 11, 7, 1201, '2025-12-02 21:30:10'),
(11, 8, 7, 900, '2025-12-02 21:30:20'),
(12, 8, 9, 1000, '2025-12-02 21:34:21'),
(14, 17, 12, 60, '2025-12-03 18:14:53'),
(18, 12, 9, 220, '2025-12-03 21:27:20'),
(21, 10, 9, 60, '2025-12-03 21:51:04'),
(22, 10, 7, 62, '2025-12-03 21:51:44'),
(23, 10, 9, 65, '2025-12-03 22:02:50');

-- --------------------------------------------------------

--
-- 表的结构 `feedback`
--

CREATE TABLE `feedback` (
  `feedbackId` int(11) NOT NULL,
  `itemId` int(11) NOT NULL,
  `buyerId` int(11) NOT NULL,
  `sellerId` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `items`
--

CREATE TABLE `items` (
  `itemId` int(10) NOT NULL,
  `sellerId` int(10) NOT NULL,
  `title` varchar(50) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `category` varchar(50) NOT NULL,
  `startPrice` int(10) NOT NULL,
  `finalPrice` int(10) NOT NULL,
  `startDate` datetime NOT NULL,
  `endDate` datetime NOT NULL,
  `status` varchar(20) NOT NULL,
  `winnerId` int(10) NOT NULL,
  `imagePath` varchar(255) DEFAULT NULL,
  `reservePrice` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `items`
--

INSERT INTO `items` (`itemId`, `sellerId`, `title`, `description`, `category`, `startPrice`, `finalPrice`, `startDate`, `endDate`, `status`, `winnerId`, `imagePath`, `reservePrice`) VALUES
(8, 8, 'iPhone 13 Pro 256GB', 'Brand new iPhone 13 Pro, sealed box with warranty', 'electronics', 800, 1000, '2025-12-01 22:00:32', '2025-12-08 22:00:00', 'active', 9, 'uploads/iphone 13 pro.jpg', NULL),
(9, 8, 'Vintage Rolex Watch', 'Classic Rolex from 1970s, fully serviced', 'fashion', 1500, 1510, '2025-12-01 22:06:07', '2025-12-04 22:05:00', 'active', 7, 'uploads\\Vintage_rolex_watch.jpg', NULL),
(10, 8, 'Programming Books Collection', 'Complete set of programming books', 'books', 50, 65, '2025-12-01 22:20:01', '2025-12-10 22:19:00', 'active', 9, 'uploads\\programming_books_collection.jpg', NULL),
(11, 8, 'MacBook Pro 2023', 'M2 chip, 16GB RAM, 512GB SSD', 'electronics', 1200, 1201, '2025-12-01 23:17:45', '2025-12-09 23:17:00', 'active', 7, 'uploads\\MacbookPro2023.jpg', NULL),
(12, 8, 'Garden Furniture Set', 'Outdoor table and 4 chairs, teak wood', 'home', 200, 220, '2025-12-02 00:34:23', '2025-12-07 00:34:00', 'active', 9, 'uploads\\garden_furniture_set.jpg', NULL),
(13, 8, 'Football signed by Mess', 'Official match ball signed by Lionel Messi', 'sports', 500, 520, '2025-12-02 15:11:27', '2025-12-02 15:25:00', 'closed', 7, 'uploads/1764688287_FB.jpg', NULL),
(14, 8, 'Antique Coin Collection', 'Rare coins from 19th century', 'collectibles', 300, 300, '2025-12-02 15:32:05', '2025-12-02 15:33:00', 'closed', 8, 'uploads\\antique-coin-collection.jpg', NULL),
(15, 8, 'Sumsang', 'phone', 'electronics', 900, 900, '2025-12-03 12:38:22', '2025-12-03 12:45:00', 'closed', 8, 'uploads/1764765502_1764691063_三星.jpeg', 1050),
(17, 11, 'Dyson Vacuum', 'Cleaners', 'electronics', 50, 60, '2025-12-03 17:52:58', '2025-12-03 20:52:00', 'closed', 12, 'uploads/1764784378_Dyson_Vacuum.jpg', 60);

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `userId` int(11) NOT NULL,
  `userName` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(50) NOT NULL,
  `registerDate` date NOT NULL,
  `role` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`userId`, `userName`, `password`, `email`, `registerDate`, `role`) VALUES
(3, 'Mingze', '$2y$10$Ro8KNPMeydfIIfQHTfepY.pNUHxEkKm9RX1bEBYf2FeXxUNt7sqCq', '123@gmail.com', '2025-11-30', 'buyer'),
(4, 'mingze', '$2y$10$9BcRBaOm2qRMvRogyBWMT.I4fkAGeS2g/qV/.Kj5r43EADcZwYDW.', '1@gmail.com', '2025-11-30', 'seller'),
(5, 'mz', '$2y$10$M92.rMTMkKKFyRj7x33v..ZN8e1lOVkd8tn4kLvlOVXb4vW0j3.3e', 'mz@163.com', '2025-12-01', 'buyer'),
(6, 'gmz', '$2y$10$7puuW4U6bvpOBExmUoMOPOfkJ.n/L5247VYyXhEfmv9Z/ZkcBls4m', 'gmz@163.com', '2025-12-01', 'seller'),
(7, 'Baiyan Zhang', '$2y$10$8wi7hD3cVW2DIEzS7izHkumD2iwQb2cbf61L2rdLXkvsORIWIN3o2', 'baiyanzhang1101@gmail.com', '2025-12-01', 'buyer'),
(8, 'yekk', '$2y$10$uyWdiKjr3jgoANcZ1uUxRuSCEho5v09H2dz0svpXxRJYZ1Qums8De', '18752953089@163.com', '2025-12-01', 'seller'),
(9, 'zhsj', '$2y$10$oSbO1tX/d8C.QcK3hwG22eE.afc88Dh5A5GqjEVK7DAKNzM/vDBHa', 'zczqb03@ucl.ac.uk', '2025-12-02', 'buyer'),
(10, 'JamYoung', '$2y$10$H9Se1RC7XXNByaJSaMgnDujsdwFm83MqHbOhfOQGYVkB6KDm4oQwi', '2636499039@qq.com', '2025-12-03', 'buyer'),
(11, 'zby', '$2y$10$Pq0k6nCvd/rC3mmJaFoaSebPEhcm3qomrlV3q2WZLiByuIOj5joY6', 'baiyan@maildrop.cc', '2025-12-03', 'seller'),
(12, 'curry', '$2y$10$HK119tB3UIa1..zAXB.Gje4lBHD3q57bKvLIwUTRgzdGlNAPyemge', 'curry@maildrop.cc', '2025-12-03', 'buyer');

--
-- 转储表的索引
--

--
-- 表的索引 `bid`
--
ALTER TABLE `bid`
  ADD PRIMARY KEY (`bidId`),
  ADD KEY `itemId` (`itemId`),
  ADD KEY `buyerId` (`buyerId`);

--
-- 表的索引 `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedbackId`),
  ADD KEY `fk_feedback_item` (`itemId`),
  ADD KEY `fk_feedback_buyer` (`buyerId`),
  ADD KEY `fk_feedback_seller` (`sellerId`);

--
-- 表的索引 `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`itemId`),
  ADD KEY `winnerId` (`winnerId`),
  ADD KEY `sellerId` (`sellerId`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userId`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `bid`
--
ALTER TABLE `bid`
  MODIFY `bidId` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- 使用表AUTO_INCREMENT `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedbackId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `items`
--
ALTER TABLE `items`
  MODIFY `itemId` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- 限制导出的表
--

--
-- 限制表 `bid`
--
ALTER TABLE `bid`
  ADD CONSTRAINT `FK_buyerid` FOREIGN KEY (`buyerId`) REFERENCES `users` (`userId`),
  ADD CONSTRAINT `FK_itemid` FOREIGN KEY (`itemId`) REFERENCES `items` (`itemId`);

--
-- 限制表 `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `fk_feedback_buyer` FOREIGN KEY (`buyerId`) REFERENCES `users` (`userId`),
  ADD CONSTRAINT `fk_feedback_item` FOREIGN KEY (`itemId`) REFERENCES `items` (`itemId`),
  ADD CONSTRAINT `fk_feedback_seller` FOREIGN KEY (`sellerId`) REFERENCES `users` (`userId`);

--
-- 限制表 `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `FK_sellerid` FOREIGN KEY (`sellerId`) REFERENCES `users` (`userId`),
  ADD CONSTRAINT `FK_winnerid` FOREIGN KEY (`winnerId`) REFERENCES `users` (`userId`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
