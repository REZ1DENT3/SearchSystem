-- phpMyAdmin SQL Dump
-- version 4.0.10.6
-- http://www.phpmyadmin.net
--
-- Хост: 127.0.0.1:3306
-- Время создания: Апр 03 2015 г., 12:09
-- Версия сервера: 5.6.22-log
-- Версия PHP: 5.6.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `habr-search`
--

-- --------------------------------------------------------

--
-- Структура таблицы `indices`
--

CREATE TABLE IF NOT EXISTS `indices` (
  `word_id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `table_index` int(11) NOT NULL,
  KEY `word_id` (`word_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `indices`
--

INSERT INTO `indices` (`word_id`, `table_id`, `table_index`) VALUES
(1, 1, 1),
(2, 1, 2),
(1, 1, 2);

--
-- Триггеры `indices`
--
DROP TRIGGER IF EXISTS `set_word_weight`;
DELIMITER //
CREATE TRIGGER `set_word_weight` AFTER INSERT ON `indices`
 FOR EACH ROW UPDATE `words` SET `weight`=(
    SELECT count(*) 
    FROM `indices`
    WHERE `word_id`=NEW.word_id
)
WHERE `id`=NEW.word_id
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `tables`
--

CREATE TABLE IF NOT EXISTS `tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(255) NOT NULL,
  `full_value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `full_value` (`full_value`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `tables`
--

INSERT INTO `tables` (`id`, `value`, `full_value`) VALUES
(1, 'test', 'tests');

-- --------------------------------------------------------

--
-- Структура таблицы `tests`
--

CREATE TABLE IF NOT EXISTS `tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Дамп данных таблицы `tests`
--

INSERT INTO `tests` (`id`, `value`) VALUES
(1, 'Привет, мир!'),
(2, 'Привет, Хабр!');

-- --------------------------------------------------------

--
-- Структура таблицы `words`
--

CREATE TABLE IF NOT EXISTS `words` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(255) NOT NULL,
  `length` int(11) DEFAULT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `value` (`value`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Дамп данных таблицы `words`
--

INSERT INTO `words` (`id`, `value`, `length`, `weight`) VALUES
(1, 'ПРИВЕТ', 6, 2),
(2, 'ХАБР', 4, 1);

--
-- Триггеры `words`
--
DROP TRIGGER IF EXISTS `set_length`;
DELIMITER //
CREATE TRIGGER `set_length` BEFORE INSERT ON `words`
 FOR EACH ROW SET NEW.length = CHAR_LENGTH(NEW.value)
//
DELIMITER ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
