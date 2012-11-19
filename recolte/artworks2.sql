-- phpMyAdmin SQL Dump
-- version 2.6.4-pl3
-- http://www.phpmyadmin.net
-- 
-- Serveur: db431068739.db.1and1.com
-- Généré le : Lundi 19 Novembre 2012 à 01:31
-- Version du serveur: 5.0.95
-- Version de PHP: 5.3.3-7+squeeze14
-- 
-- Base de données: `db431068739`
-- 

-- --------------------------------------------------------

-- 
-- Structure de la table `artworks2`
-- 

CREATE TABLE `artworks2` (
  `id` int(11) NOT NULL auto_increment,
  `source` varchar(8) default '',
  `de` varchar(255) default '',
  `el` varchar(255) default '',
  `en` varchar(255) default '',
  `fr` varchar(255) default '',
  `nl` varchar(255) default '',
  `thumb` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4145 DEFAULT CHARSET=utf8 AUTO_INCREMENT=4145 ;
