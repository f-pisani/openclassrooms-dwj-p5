-- phpMyAdmin SQL Dump
-- version 4.7.9
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le :  sam. 08 juin 2019 à 23:28
-- Version du serveur :  5.7.21
-- Version de PHP :  7.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `projectflow`
--
CREATE DATABASE IF NOT EXISTS `projectflow` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `projectflow`;

-- --------------------------------------------------------

--
-- Structure de la table `user_logins`
--

DROP TABLE IF EXISTS `user_logins`;
CREATE TABLE IF NOT EXISTS `user_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nickname` varchar(64) COLLATE utf8_bin NOT NULL,
  `email` varchar(256) COLLATE utf8_bin NOT NULL,
  `password` varchar(256) COLLATE utf8_bin NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nickname_unique` (`nickname`),
  UNIQUE KEY `email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
