/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0 */;
/*!40101 SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES = @@SQL_NOTES, SQL_NOTES = 0 */;

CREATE DATABASE IF NOT EXISTS `auth` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `auth`;

CREATE TABLE IF NOT EXISTS `account_locks`
(
    `ip`           varchar(124) NOT NULL DEFAULT '',
    `locked_until` datetime              DEFAULT NULL,
    PRIMARY KEY (`ip`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1;

CREATE TABLE IF NOT EXISTS `activate_keys`
(
    `uid` int(11)     DEFAULT NULL,
    `key` varchar(50) DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1;

CREATE TABLE IF NOT EXISTS `login_attempts`
(
    `id`       int(11)     NOT NULL AUTO_INCREMENT,
    `username` varchar(24) NOT NULL,
    `ip`       varchar(124) DEFAULT NULL,
    `datetime` datetime     DEFAULT current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1;

CREATE TABLE IF NOT EXISTS `users`
(
    `uid`           int(11) NOT NULL AUTO_INCREMENT,
    `username`      varchar(24)  DEFAULT NULL,
    `password`      varchar(255) DEFAULT NULL,
    `email`         varchar(60)  DEFAULT NULL,
    `login_count`   int(11)      DEFAULT 0,
    `login_fails`   int(11)      DEFAULT 0,
    `logged_out`    datetime     DEFAULT NULL,
    `created`       datetime     DEFAULT current_timestamp(),
    `last_login_ip` varchar(50)  DEFAULT NULL,
    `last_login_at` datetime     DEFAULT NULL,
    `activated`     tinyint(1)   DEFAULT 0,
    `last_fail`     datetime     DEFAULT NULL,
    PRIMARY KEY (`uid`),
    UNIQUE KEY `user_key` (`username`, `email`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1;

/*!40101 SET SQL_MODE = IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS = IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES = @OLD_SQL_NOTES */;