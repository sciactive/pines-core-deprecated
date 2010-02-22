SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

/* CREATE USER 'pines'@'localhost' IDENTIFIED BY 'password';

GRANT USAGE ON *.* TO 'pines'@'localhost'
WITH
	MAX_QUERIES_PER_HOUR 0
	MAX_CONNECTIONS_PER_HOUR 0
	MAX_UPDATES_PER_HOUR 0
	MAX_USER_CONNECTIONS 0; */

CREATE DATABASE IF NOT EXISTS `pines`;

USE `pines`;

/* GRANT ALL PRIVILEGES ON `pines`.* TO 'pines'@'localhost'; */

CREATE TABLE IF NOT EXISTS `pin_com_entity_data` (
  `id` bigint(20) NOT NULL auto_increment,
  `guid` bigint(20) NOT NULL,
  `name` text NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `id_guid` (`guid`)
) AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `pin_com_entity_entities` (
  `guid` bigint(20) NOT NULL auto_increment,
  `parent` bigint(20) default NULL,
  `tags` text,
  PRIMARY KEY  (`guid`),
  KEY `id_parent` (`parent`)
) AUTO_INCREMENT=1 ;