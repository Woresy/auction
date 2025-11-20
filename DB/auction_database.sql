-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2025-11-20 13:33:54
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
-- 数据库： `auction_database`
--

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
  `winnerId` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `userId` int(10) NOT NULL,
  `userName` varchar(20) NOT NULL,
  `password` varchar(16) NOT NULL,
  `email` varchar(50) NOT NULL,
  `registerDate` date NOT NULL,
  `role` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- 限制导出的表
--

--
-- 限制表 `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `FK_itemid` FOREIGN KEY (`itemId`) REFERENCES `bid` (`itemId`);

--
-- 限制表 `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `FK_buyerid` FOREIGN KEY (`userId`) REFERENCES `bid` (`buyerId`),
  ADD CONSTRAINT `FK_sellerid` FOREIGN KEY (`userId`) REFERENCES `items` (`sellerId`),
  ADD CONSTRAINT `FK_winnerid` FOREIGN KEY (`userId`) REFERENCES `items` (`winnerId`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
