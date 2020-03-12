# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 192.168.95.100 (MySQL 5.6.34)
# Database: local
# Generation Time: 2020-03-12 05:15:18 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table wp_commentmeta
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_commentmeta`;

CREATE TABLE `wp_commentmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`meta_id`),
  KEY `comment_id` (`comment_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;



# Dump of table wp_comments
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_comments`;

CREATE TABLE `wp_comments` (
  `comment_ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_post_ID` bigint(20) unsigned NOT NULL DEFAULT '0',
  `comment_author` tinytext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `comment_author_email` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_author_url` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_author_IP` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_content` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `comment_karma` int(11) NOT NULL DEFAULT '0',
  `comment_approved` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '1',
  `comment_agent` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_type` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_ID`),
  KEY `comment_post_ID` (`comment_post_ID`),
  KEY `comment_approved_date_gmt` (`comment_approved`,`comment_date_gmt`),
  KEY `comment_date_gmt` (`comment_date_gmt`),
  KEY `comment_parent` (`comment_parent`),
  KEY `comment_author_email` (`comment_author_email`(10)),
  KEY `woo_idx_comment_type` (`comment_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;



# Dump of table wp_links
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_links`;

CREATE TABLE `wp_links` (
  `link_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `link_url` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_name` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_image` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_target` varchar(25) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_description` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_visible` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Y',
  `link_owner` bigint(20) unsigned NOT NULL DEFAULT '1',
  `link_rating` int(11) NOT NULL DEFAULT '0',
  `link_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `link_rel` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_notes` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `link_rss` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`link_id`),
  KEY `link_visible` (`link_visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;



# Dump of table wp_options
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_options`;

CREATE TABLE `wp_options` (
  `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `option_name` varchar(191) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `option_value` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `autoload` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`option_id`),
  UNIQUE KEY `option_name` (`option_name`),
  KEY `autoload` (`autoload`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

LOCK TABLES `wp_options` WRITE;
/*!40000 ALTER TABLE `wp_options` DISABLE KEYS */;

INSERT INTO `wp_options` (`option_id`, `option_name`, `option_value`, `autoload`)
VALUES
	(1,'siteurl','http://test.tribe.dev','yes'),
	(2,'home','http://test.tribe.dev','yes'),
	(3,'blogname','Tribe Commerce','yes'),
	(4,'blogdescription','Just another WordPress site','yes'),
	(5,'users_can_register','0','yes'),
	(6,'admin_email','admin@commerce.dev','yes'),
	(7,'start_of_week','1','yes'),
	(8,'use_balanceTags','0','yes'),
	(9,'use_smilies','1','yes'),
	(10,'require_name_email','1','yes'),
	(11,'comments_notify','1','yes'),
	(12,'posts_per_rss','10','yes'),
	(13,'rss_use_excerpt','0','yes'),
	(14,'mailserver_url','mail.example.com','yes'),
	(15,'mailserver_login','login@example.com','yes'),
	(16,'mailserver_pass','password','yes'),
	(17,'mailserver_port','110','yes'),
	(18,'default_category','1','yes'),
	(19,'default_comment_status','open','yes'),
	(20,'default_ping_status','open','yes'),
	(21,'default_pingback_flag','0','yes'),
	(22,'posts_per_page','10','yes'),
	(23,'date_format','F j, Y','yes'),
	(24,'time_format','g:i a','yes'),
	(25,'links_updated_date_format','F j, Y g:i a','yes'),
	(26,'comment_moderation','0','yes'),
	(27,'moderation_notify','1','yes'),
	(28,'permalink_structure','/%postname%/','yes'),
	(29,'rewrite_rules','a:492:{s:21:\"tickets/([0-9]{1,})/?\";s:43:\"index.php?p=$matches[1]&tribe-edit-orders=1\";s:28:\"event-aggregator/(insert)/?$\";s:53:\"index.php?tribe-aggregator=1&tribe-action=$matches[1]\";s:25:\"(?:event)/([^/]+)/ical/?$\";s:56:\"index.php?ical=1&name=$matches[1]&post_type=tribe_events\";s:28:\"(?:events)/(?:page)/(\\d+)/?$\";s:71:\"index.php?post_type=tribe_events&eventDisplay=default&paged=$matches[1]\";s:41:\"(?:events)/(?:featured)/(?:page)/(\\d+)/?$\";s:79:\"index.php?post_type=tribe_events&featured=1&eventDisplay=list&paged=$matches[1]\";s:38:\"(?:events)/(feed|rdf|rss|rss2|atom)/?$\";s:67:\"index.php?post_type=tribe_events&eventDisplay=list&feed=$matches[1]\";s:51:\"(?:events)/(?:featured)/(feed|rdf|rss|rss2|atom)/?$\";s:78:\"index.php?post_type=tribe_events&featured=1&eventDisplay=list&feed=$matches[1]\";s:23:\"(?:events)/(?:month)/?$\";s:51:\"index.php?post_type=tribe_events&eventDisplay=month\";s:36:\"(?:events)/(?:month)/(?:featured)/?$\";s:62:\"index.php?post_type=tribe_events&eventDisplay=month&featured=1\";s:37:\"(?:events)/(?:month)/(\\d{4}-\\d{2})/?$\";s:73:\"index.php?post_type=tribe_events&eventDisplay=month&eventDate=$matches[1]\";s:37:\"(?:events)/(?:list)/(?:page)/(\\d+)/?$\";s:68:\"index.php?post_type=tribe_events&eventDisplay=list&paged=$matches[1]\";s:50:\"(?:events)/(?:list)/(?:featured)/(?:page)/(\\d+)/?$\";s:79:\"index.php?post_type=tribe_events&eventDisplay=list&featured=1&paged=$matches[1]\";s:22:\"(?:events)/(?:list)/?$\";s:50:\"index.php?post_type=tribe_events&eventDisplay=list\";s:35:\"(?:events)/(?:list)/(?:featured)/?$\";s:61:\"index.php?post_type=tribe_events&eventDisplay=list&featured=1\";s:23:\"(?:events)/(?:today)/?$\";s:49:\"index.php?post_type=tribe_events&eventDisplay=day\";s:36:\"(?:events)/(?:today)/(?:featured)/?$\";s:60:\"index.php?post_type=tribe_events&eventDisplay=day&featured=1\";s:27:\"(?:events)/(\\d{4}-\\d{2})/?$\";s:73:\"index.php?post_type=tribe_events&eventDisplay=month&eventDate=$matches[1]\";s:40:\"(?:events)/(\\d{4}-\\d{2})/(?:featured)/?$\";s:84:\"index.php?post_type=tribe_events&eventDisplay=month&eventDate=$matches[1]&featured=1\";s:33:\"(?:events)/(\\d{4}-\\d{2}-\\d{2})/?$\";s:71:\"index.php?post_type=tribe_events&eventDisplay=day&eventDate=$matches[1]\";s:46:\"(?:events)/(\\d{4}-\\d{2}-\\d{2})/(?:featured)/?$\";s:82:\"index.php?post_type=tribe_events&eventDisplay=day&eventDate=$matches[1]&featured=1\";s:26:\"(?:events)/(?:featured)/?$\";s:43:\"index.php?post_type=tribe_events&featured=1\";s:13:\"(?:events)/?$\";s:53:\"index.php?post_type=tribe_events&eventDisplay=default\";s:18:\"(?:events)/ical/?$\";s:39:\"index.php?post_type=tribe_events&ical=1\";s:31:\"(?:events)/(?:featured)/ical/?$\";s:50:\"index.php?post_type=tribe_events&ical=1&featured=1\";s:38:\"(?:events)/(\\d{4}-\\d{2}-\\d{2})/ical/?$\";s:78:\"index.php?post_type=tribe_events&ical=1&eventDisplay=day&eventDate=$matches[1]\";s:47:\"(?:events)/(\\d{4}-\\d{2}-\\d{2})/ical/featured/?$\";s:89:\"index.php?post_type=tribe_events&ical=1&eventDisplay=day&eventDate=$matches[1]&featured=1\";s:60:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(?:page)/(\\d+)/?$\";s:97:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&eventDisplay=list&paged=$matches[2]\";s:73:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(?:featured)/(?:page)/(\\d+)/?$\";s:108:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&featured=1&eventDisplay=list&paged=$matches[2]\";s:55:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(?:month)/?$\";s:80:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&eventDisplay=month\";s:68:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(?:month)/(?:featured)/?$\";s:91:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&eventDisplay=month&featured=1\";s:69:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(?:list)/(?:page)/(\\d+)/?$\";s:97:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&eventDisplay=list&paged=$matches[2]\";s:82:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(?:list)/(?:featured)/(?:page)/(\\d+)/?$\";s:108:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&eventDisplay=list&featured=1&paged=$matches[2]\";s:54:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(?:list)/?$\";s:79:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&eventDisplay=list\";s:67:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(?:list)/(?:featured)/?$\";s:90:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&eventDisplay=list&featured=1\";s:55:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(?:today)/?$\";s:78:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&eventDisplay=day\";s:68:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(?:today)/(?:featured)/?$\";s:89:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&eventDisplay=day&featured=1\";s:73:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(?:day)/(\\d{4}-\\d{2}-\\d{2})/?$\";s:100:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&eventDisplay=day&eventDate=$matches[2]\";s:86:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(?:day)/(\\d{4}-\\d{2}-\\d{2})/(?:featured)/?$\";s:111:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&eventDisplay=day&eventDate=$matches[2]&featured=1\";s:59:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(\\d{4}-\\d{2})/?$\";s:102:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&eventDisplay=month&eventDate=$matches[2]\";s:72:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(\\d{4}-\\d{2})/(?:featured)/?$\";s:113:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&eventDisplay=month&eventDate=$matches[2]&featured=1\";s:65:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(\\d{4}-\\d{2}-\\d{2})/?$\";s:100:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&eventDisplay=day&eventDate=$matches[2]\";s:78:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(\\d{4}-\\d{2}-\\d{2})/(?:featured)/?$\";s:111:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&eventDisplay=day&eventDate=$matches[2]&featured=1\";s:50:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/feed/?$\";s:89:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&eventDisplay=list&feed=rss2\";s:63:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(?:featured)/feed/?$\";s:100:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&featured=1&eventDisplay=list&feed=rss2\";s:50:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/ical/?$\";s:68:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&ical=1\";s:63:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(?:featured)/ical/?$\";s:79:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&featured=1&ical=1\";s:75:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:78:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&feed=$matches[2]\";s:88:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(?:featured)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:89:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&featured=1&feed=$matches[2]\";s:58:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/(?:featured)/?$\";s:93:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&featured=1&eventDisplay=default\";s:45:\"(?:events)/(?:category)/(?:[^/]+/)*([^/]+)/?$\";s:82:\"index.php?post_type=tribe_events&tribe_events_cat=$matches[1]&eventDisplay=default\";s:44:\"(?:events)/(?:tag)/([^/]+)/(?:page)/(\\d+)/?$\";s:84:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=list&paged=$matches[2]\";s:57:\"(?:events)/(?:tag)/([^/]+)/(?:featured)/(?:page)/(\\d+)/?$\";s:95:\"index.php?post_type=tribe_events&tag=$matches[1]&featured=1&eventDisplay=list&paged=$matches[2]\";s:39:\"(?:events)/(?:tag)/([^/]+)/(?:month)/?$\";s:67:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=month\";s:52:\"(?:events)/(?:tag)/([^/]+)/(?:month)/(?:featured)/?$\";s:78:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=month&featured=1\";s:53:\"(?:events)/(?:tag)/([^/]+)/(?:list)/(?:page)/(\\d+)/?$\";s:84:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=list&paged=$matches[2]\";s:66:\"(?:events)/(?:tag)/([^/]+)/(?:list)/(?:featured)/(?:page)/(\\d+)/?$\";s:95:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=list&featured=1&paged=$matches[2]\";s:38:\"(?:events)/(?:tag)/([^/]+)/(?:list)/?$\";s:66:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=list\";s:51:\"(?:events)/(?:tag)/([^/]+)/(?:list)/(?:featured)/?$\";s:77:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=list&featured=1\";s:39:\"(?:events)/(?:tag)/([^/]+)/(?:today)/?$\";s:65:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=day\";s:52:\"(?:events)/(?:tag)/([^/]+)/(?:today)/(?:featured)/?$\";s:76:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=day&featured=1\";s:57:\"(?:events)/(?:tag)/([^/]+)/(?:day)/(\\d{4}-\\d{2}-\\d{2})/?$\";s:87:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=day&eventDate=$matches[2]\";s:70:\"(?:events)/(?:tag)/([^/]+)/(?:day)/(\\d{4}-\\d{2}-\\d{2})/(?:featured)/?$\";s:98:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=day&eventDate=$matches[2]&featured=1\";s:43:\"(?:events)/(?:tag)/([^/]+)/(\\d{4}-\\d{2})/?$\";s:89:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=month&eventDate=$matches[2]\";s:56:\"(?:events)/(?:tag)/([^/]+)/(\\d{4}-\\d{2})/(?:featured)/?$\";s:100:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=month&eventDate=$matches[2]&featured=1\";s:49:\"(?:events)/(?:tag)/([^/]+)/(\\d{4}-\\d{2}-\\d{2})/?$\";s:87:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=day&eventDate=$matches[2]\";s:62:\"(?:events)/(?:tag)/([^/]+)/(\\d{4}-\\d{2}-\\d{2})/(?:featured)/?$\";s:98:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=day&eventDate=$matches[2]&featured=1\";s:34:\"(?:events)/(?:tag)/([^/]+)/feed/?$\";s:76:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=list&feed=rss2\";s:47:\"(?:events)/(?:tag)/([^/]+)/(?:featured)/feed/?$\";s:87:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=list&feed=rss2&featured=1\";s:34:\"(?:events)/(?:tag)/([^/]+)/ical/?$\";s:55:\"index.php?post_type=tribe_events&tag=$matches[1]&ical=1\";s:47:\"(?:events)/(?:tag)/([^/]+)/(?:featured)/ical/?$\";s:66:\"index.php?post_type=tribe_events&tag=$matches[1]&featured=1&ical=1\";s:59:\"(?:events)/(?:tag)/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:65:\"index.php?post_type=tribe_events&tag=$matches[1]&feed=$matches[2]\";s:72:\"(?:events)/(?:tag)/([^/]+)/(?:featured)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:76:\"index.php?post_type=tribe_events&tag=$matches[1]&featured=1&feed=$matches[2]\";s:42:\"(?:events)/(?:tag)/([^/]+)/(?:featured)/?$\";s:59:\"index.php?post_type=tribe_events&tag=$matches[1]&featured=1\";s:29:\"(?:events)/(?:tag)/([^/]+)/?$\";s:69:\"index.php?post_type=tribe_events&tag=$matches[1]&eventDisplay=default\";s:32:\"(?:event)/([^/]+)/(?:tickets)/?$\";s:78:\"index.php?tribe_events=$matches[1]&post_type=tribe_events&eventDisplay=tickets\";s:52:\"(?:event)/([^/]+)/(\\d{4}-\\d{2}-\\d{2})/(?:tickets)/?$\";s:100:\"index.php?tribe_events=$matches[1]&eventDate=$matches[2]&post_type=tribe_events&eventDisplay=tickets\";s:29:\"(?:attendee\\-registration)/?$\";s:33:\"index.php?attendee-registration=1\";s:24:\"^wc-auth/v([1]{1})/(.*)?\";s:63:\"index.php?wc-auth-version=$matches[1]&wc-auth-route=$matches[2]\";s:22:\"^wc-api/v([1-3]{1})/?$\";s:51:\"index.php?wc-api-version=$matches[1]&wc-api-route=/\";s:24:\"^wc-api/v([1-3]{1})(.*)?\";s:61:\"index.php?wc-api-version=$matches[1]&wc-api-route=$matches[2]\";s:12:\"downloads/?$\";s:28:\"index.php?post_type=download\";s:42:\"downloads/feed/(feed|rdf|rss|rss2|atom)/?$\";s:45:\"index.php?post_type=download&feed=$matches[1]\";s:37:\"downloads/(feed|rdf|rss|rss2|atom)/?$\";s:45:\"index.php?post_type=download&feed=$matches[1]\";s:29:\"downloads/page/([0-9]{1,})/?$\";s:46:\"index.php?post_type=download&paged=$matches[1]\";s:7:\"shop/?$\";s:27:\"index.php?post_type=product\";s:37:\"shop/feed/(feed|rdf|rss|rss2|atom)/?$\";s:44:\"index.php?post_type=product&feed=$matches[1]\";s:32:\"shop/(feed|rdf|rss|rss2|atom)/?$\";s:44:\"index.php?post_type=product&feed=$matches[1]\";s:24:\"shop/page/([0-9]{1,})/?$\";s:45:\"index.php?post_type=product&paged=$matches[1]\";s:11:\"^wp-json/?$\";s:22:\"index.php?rest_route=/\";s:14:\"^wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:21:\"^index.php/wp-json/?$\";s:22:\"index.php?rest_route=/\";s:24:\"^index.php/wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:22:\"tribe-promoter-auth/?$\";s:37:\"index.php?tribe-promoter-auth-check=1\";s:8:\"event/?$\";s:32:\"index.php?post_type=tribe_events\";s:38:\"event/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?post_type=tribe_events&feed=$matches[1]\";s:33:\"event/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?post_type=tribe_events&feed=$matches[1]\";s:25:\"event/page/([0-9]{1,})/?$\";s:50:\"index.php?post_type=tribe_events&paged=$matches[1]\";s:47:\"category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:42:\"category/(.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:23:\"category/(.+?)/embed/?$\";s:46:\"index.php?category_name=$matches[1]&embed=true\";s:35:\"category/(.+?)/page/?([0-9]{1,})/?$\";s:53:\"index.php?category_name=$matches[1]&paged=$matches[2]\";s:32:\"category/(.+?)/wc-api(/(.*))?/?$\";s:54:\"index.php?category_name=$matches[1]&wc-api=$matches[3]\";s:33:\"category/(.+?)/edd-add(/(.*))?/?$\";s:55:\"index.php?category_name=$matches[1]&edd-add=$matches[3]\";s:36:\"category/(.+?)/edd-remove(/(.*))?/?$\";s:58:\"index.php?category_name=$matches[1]&edd-remove=$matches[3]\";s:33:\"category/(.+?)/edd-api(/(.*))?/?$\";s:55:\"index.php?category_name=$matches[1]&edd-api=$matches[3]\";s:17:\"category/(.+?)/?$\";s:35:\"index.php?category_name=$matches[1]\";s:44:\"tag/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:39:\"tag/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:20:\"tag/([^/]+)/embed/?$\";s:36:\"index.php?tag=$matches[1]&embed=true\";s:32:\"tag/([^/]+)/page/?([0-9]{1,})/?$\";s:43:\"index.php?tag=$matches[1]&paged=$matches[2]\";s:29:\"tag/([^/]+)/wc-api(/(.*))?/?$\";s:44:\"index.php?tag=$matches[1]&wc-api=$matches[3]\";s:30:\"tag/([^/]+)/edd-add(/(.*))?/?$\";s:45:\"index.php?tag=$matches[1]&edd-add=$matches[3]\";s:33:\"tag/([^/]+)/edd-remove(/(.*))?/?$\";s:48:\"index.php?tag=$matches[1]&edd-remove=$matches[3]\";s:30:\"tag/([^/]+)/edd-api(/(.*))?/?$\";s:45:\"index.php?tag=$matches[1]&edd-api=$matches[3]\";s:14:\"tag/([^/]+)/?$\";s:25:\"index.php?tag=$matches[1]\";s:45:\"type/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:40:\"type/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:21:\"type/([^/]+)/embed/?$\";s:44:\"index.php?post_format=$matches[1]&embed=true\";s:33:\"type/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?post_format=$matches[1]&paged=$matches[2]\";s:15:\"type/([^/]+)/?$\";s:33:\"index.php?post_format=$matches[1]\";s:57:\"downloads/category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:56:\"index.php?download_category=$matches[1]&feed=$matches[2]\";s:52:\"downloads/category/(.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:56:\"index.php?download_category=$matches[1]&feed=$matches[2]\";s:33:\"downloads/category/(.+?)/embed/?$\";s:50:\"index.php?download_category=$matches[1]&embed=true\";s:45:\"downloads/category/(.+?)/page/?([0-9]{1,})/?$\";s:57:\"index.php?download_category=$matches[1]&paged=$matches[2]\";s:27:\"downloads/category/(.+?)/?$\";s:39:\"index.php?download_category=$matches[1]\";s:54:\"downloads/tag/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:51:\"index.php?download_tag=$matches[1]&feed=$matches[2]\";s:49:\"downloads/tag/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:51:\"index.php?download_tag=$matches[1]&feed=$matches[2]\";s:30:\"downloads/tag/([^/]+)/embed/?$\";s:45:\"index.php?download_tag=$matches[1]&embed=true\";s:42:\"downloads/tag/([^/]+)/page/?([0-9]{1,})/?$\";s:52:\"index.php?download_tag=$matches[1]&paged=$matches[2]\";s:24:\"downloads/tag/([^/]+)/?$\";s:34:\"index.php?download_tag=$matches[1]\";s:53:\"edd_log_type/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:51:\"index.php?edd_log_type=$matches[1]&feed=$matches[2]\";s:48:\"edd_log_type/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:51:\"index.php?edd_log_type=$matches[1]&feed=$matches[2]\";s:29:\"edd_log_type/([^/]+)/embed/?$\";s:45:\"index.php?edd_log_type=$matches[1]&embed=true\";s:41:\"edd_log_type/([^/]+)/page/?([0-9]{1,})/?$\";s:52:\"index.php?edd_log_type=$matches[1]&paged=$matches[2]\";s:23:\"edd_log_type/([^/]+)/?$\";s:34:\"index.php?edd_log_type=$matches[1]\";s:37:\"downloads/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:47:\"downloads/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:67:\"downloads/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:62:\"downloads/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:62:\"downloads/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:43:\"downloads/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:26:\"downloads/([^/]+)/embed/?$\";s:41:\"index.php?download=$matches[1]&embed=true\";s:30:\"downloads/([^/]+)/trackback/?$\";s:35:\"index.php?download=$matches[1]&tb=1\";s:50:\"downloads/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?download=$matches[1]&feed=$matches[2]\";s:45:\"downloads/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?download=$matches[1]&feed=$matches[2]\";s:38:\"downloads/([^/]+)/page/?([0-9]{1,})/?$\";s:48:\"index.php?download=$matches[1]&paged=$matches[2]\";s:45:\"downloads/([^/]+)/comment-page-([0-9]{1,})/?$\";s:48:\"index.php?download=$matches[1]&cpage=$matches[2]\";s:35:\"downloads/([^/]+)/wc-api(/(.*))?/?$\";s:49:\"index.php?download=$matches[1]&wc-api=$matches[3]\";s:36:\"downloads/([^/]+)/edd-add(/(.*))?/?$\";s:50:\"index.php?download=$matches[1]&edd-add=$matches[3]\";s:39:\"downloads/([^/]+)/edd-remove(/(.*))?/?$\";s:53:\"index.php?download=$matches[1]&edd-remove=$matches[3]\";s:36:\"downloads/([^/]+)/edd-api(/(.*))?/?$\";s:50:\"index.php?download=$matches[1]&edd-api=$matches[3]\";s:41:\"downloads/[^/]+/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:52:\"downloads/[^/]+/attachment/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:42:\"downloads/[^/]+/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:53:\"downloads/[^/]+/attachment/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:45:\"downloads/[^/]+/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:56:\"downloads/[^/]+/attachment/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:42:\"downloads/[^/]+/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:53:\"downloads/[^/]+/attachment/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:34:\"downloads/([^/]+)(?:/([0-9]+))?/?$\";s:47:\"index.php?download=$matches[1]&page=$matches[2]\";s:26:\"downloads/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:36:\"downloads/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:56:\"downloads/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:51:\"downloads/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:51:\"downloads/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:32:\"downloads/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:55:\"product-category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?product_cat=$matches[1]&feed=$matches[2]\";s:50:\"product-category/(.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?product_cat=$matches[1]&feed=$matches[2]\";s:31:\"product-category/(.+?)/embed/?$\";s:44:\"index.php?product_cat=$matches[1]&embed=true\";s:43:\"product-category/(.+?)/page/?([0-9]{1,})/?$\";s:51:\"index.php?product_cat=$matches[1]&paged=$matches[2]\";s:25:\"product-category/(.+?)/?$\";s:33:\"index.php?product_cat=$matches[1]\";s:52:\"product-tag/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?product_tag=$matches[1]&feed=$matches[2]\";s:47:\"product-tag/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?product_tag=$matches[1]&feed=$matches[2]\";s:28:\"product-tag/([^/]+)/embed/?$\";s:44:\"index.php?product_tag=$matches[1]&embed=true\";s:40:\"product-tag/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?product_tag=$matches[1]&paged=$matches[2]\";s:22:\"product-tag/([^/]+)/?$\";s:33:\"index.php?product_tag=$matches[1]\";s:35:\"product/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:45:\"product/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:65:\"product/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:60:\"product/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:60:\"product/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:41:\"product/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:24:\"product/([^/]+)/embed/?$\";s:40:\"index.php?product=$matches[1]&embed=true\";s:28:\"product/([^/]+)/trackback/?$\";s:34:\"index.php?product=$matches[1]&tb=1\";s:48:\"product/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:46:\"index.php?product=$matches[1]&feed=$matches[2]\";s:43:\"product/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:46:\"index.php?product=$matches[1]&feed=$matches[2]\";s:36:\"product/([^/]+)/page/?([0-9]{1,})/?$\";s:47:\"index.php?product=$matches[1]&paged=$matches[2]\";s:43:\"product/([^/]+)/comment-page-([0-9]{1,})/?$\";s:47:\"index.php?product=$matches[1]&cpage=$matches[2]\";s:33:\"product/([^/]+)/wc-api(/(.*))?/?$\";s:48:\"index.php?product=$matches[1]&wc-api=$matches[3]\";s:34:\"product/([^/]+)/edd-add(/(.*))?/?$\";s:49:\"index.php?product=$matches[1]&edd-add=$matches[3]\";s:37:\"product/([^/]+)/edd-remove(/(.*))?/?$\";s:52:\"index.php?product=$matches[1]&edd-remove=$matches[3]\";s:34:\"product/([^/]+)/edd-api(/(.*))?/?$\";s:49:\"index.php?product=$matches[1]&edd-api=$matches[3]\";s:39:\"product/[^/]+/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:50:\"product/[^/]+/attachment/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:40:\"product/[^/]+/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:51:\"product/[^/]+/attachment/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:43:\"product/[^/]+/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:54:\"product/[^/]+/attachment/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:40:\"product/[^/]+/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:51:\"product/[^/]+/attachment/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:32:\"product/([^/]+)(?:/([0-9]+))?/?$\";s:46:\"index.php?product=$matches[1]&page=$matches[2]\";s:24:\"product/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:34:\"product/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:54:\"product/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:49:\"product/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:49:\"product/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:30:\"product/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:48:\"ticket-meta-fieldset/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:58:\"ticket-meta-fieldset/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:78:\"ticket-meta-fieldset/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:73:\"ticket-meta-fieldset/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:73:\"ticket-meta-fieldset/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:54:\"ticket-meta-fieldset/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:37:\"ticket-meta-fieldset/([^/]+)/embed/?$\";s:53:\"index.php?ticket-meta-fieldset=$matches[1]&embed=true\";s:41:\"ticket-meta-fieldset/([^/]+)/trackback/?$\";s:47:\"index.php?ticket-meta-fieldset=$matches[1]&tb=1\";s:49:\"ticket-meta-fieldset/([^/]+)/page/?([0-9]{1,})/?$\";s:60:\"index.php?ticket-meta-fieldset=$matches[1]&paged=$matches[2]\";s:56:\"ticket-meta-fieldset/([^/]+)/comment-page-([0-9]{1,})/?$\";s:60:\"index.php?ticket-meta-fieldset=$matches[1]&cpage=$matches[2]\";s:46:\"ticket-meta-fieldset/([^/]+)/wc-api(/(.*))?/?$\";s:61:\"index.php?ticket-meta-fieldset=$matches[1]&wc-api=$matches[3]\";s:47:\"ticket-meta-fieldset/([^/]+)/edd-add(/(.*))?/?$\";s:62:\"index.php?ticket-meta-fieldset=$matches[1]&edd-add=$matches[3]\";s:50:\"ticket-meta-fieldset/([^/]+)/edd-remove(/(.*))?/?$\";s:65:\"index.php?ticket-meta-fieldset=$matches[1]&edd-remove=$matches[3]\";s:47:\"ticket-meta-fieldset/([^/]+)/edd-api(/(.*))?/?$\";s:62:\"index.php?ticket-meta-fieldset=$matches[1]&edd-api=$matches[3]\";s:52:\"ticket-meta-fieldset/[^/]+/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:63:\"ticket-meta-fieldset/[^/]+/attachment/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:53:\"ticket-meta-fieldset/[^/]+/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:64:\"ticket-meta-fieldset/[^/]+/attachment/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:56:\"ticket-meta-fieldset/[^/]+/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:67:\"ticket-meta-fieldset/[^/]+/attachment/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:53:\"ticket-meta-fieldset/[^/]+/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:64:\"ticket-meta-fieldset/[^/]+/attachment/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:45:\"ticket-meta-fieldset/([^/]+)(?:/([0-9]+))?/?$\";s:59:\"index.php?ticket-meta-fieldset=$matches[1]&page=$matches[2]\";s:37:\"ticket-meta-fieldset/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:47:\"ticket-meta-fieldset/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:67:\"ticket-meta-fieldset/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:62:\"ticket-meta-fieldset/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:62:\"ticket-meta-fieldset/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:43:\"ticket-meta-fieldset/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:33:\"venue/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:43:\"venue/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:63:\"venue/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:58:\"venue/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:58:\"venue/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:39:\"venue/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:22:\"venue/([^/]+)/embed/?$\";s:44:\"index.php?tribe_venue=$matches[1]&embed=true\";s:26:\"venue/([^/]+)/trackback/?$\";s:38:\"index.php?tribe_venue=$matches[1]&tb=1\";s:34:\"venue/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?tribe_venue=$matches[1]&paged=$matches[2]\";s:41:\"venue/([^/]+)/comment-page-([0-9]{1,})/?$\";s:51:\"index.php?tribe_venue=$matches[1]&cpage=$matches[2]\";s:31:\"venue/([^/]+)/wc-api(/(.*))?/?$\";s:52:\"index.php?tribe_venue=$matches[1]&wc-api=$matches[3]\";s:32:\"venue/([^/]+)/edd-add(/(.*))?/?$\";s:53:\"index.php?tribe_venue=$matches[1]&edd-add=$matches[3]\";s:35:\"venue/([^/]+)/edd-remove(/(.*))?/?$\";s:56:\"index.php?tribe_venue=$matches[1]&edd-remove=$matches[3]\";s:32:\"venue/([^/]+)/edd-api(/(.*))?/?$\";s:53:\"index.php?tribe_venue=$matches[1]&edd-api=$matches[3]\";s:37:\"venue/[^/]+/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:48:\"venue/[^/]+/attachment/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:38:\"venue/[^/]+/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:49:\"venue/[^/]+/attachment/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:41:\"venue/[^/]+/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:52:\"venue/[^/]+/attachment/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:38:\"venue/[^/]+/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:49:\"venue/[^/]+/attachment/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:30:\"venue/([^/]+)(?:/([0-9]+))?/?$\";s:50:\"index.php?tribe_venue=$matches[1]&page=$matches[2]\";s:22:\"venue/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:32:\"venue/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:52:\"venue/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:47:\"venue/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:47:\"venue/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:28:\"venue/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:37:\"organizer/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:47:\"organizer/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:67:\"organizer/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:62:\"organizer/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:62:\"organizer/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:43:\"organizer/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:26:\"organizer/([^/]+)/embed/?$\";s:48:\"index.php?tribe_organizer=$matches[1]&embed=true\";s:30:\"organizer/([^/]+)/trackback/?$\";s:42:\"index.php?tribe_organizer=$matches[1]&tb=1\";s:38:\"organizer/([^/]+)/page/?([0-9]{1,})/?$\";s:55:\"index.php?tribe_organizer=$matches[1]&paged=$matches[2]\";s:45:\"organizer/([^/]+)/comment-page-([0-9]{1,})/?$\";s:55:\"index.php?tribe_organizer=$matches[1]&cpage=$matches[2]\";s:35:\"organizer/([^/]+)/wc-api(/(.*))?/?$\";s:56:\"index.php?tribe_organizer=$matches[1]&wc-api=$matches[3]\";s:36:\"organizer/([^/]+)/edd-add(/(.*))?/?$\";s:57:\"index.php?tribe_organizer=$matches[1]&edd-add=$matches[3]\";s:39:\"organizer/([^/]+)/edd-remove(/(.*))?/?$\";s:60:\"index.php?tribe_organizer=$matches[1]&edd-remove=$matches[3]\";s:36:\"organizer/([^/]+)/edd-api(/(.*))?/?$\";s:57:\"index.php?tribe_organizer=$matches[1]&edd-api=$matches[3]\";s:41:\"organizer/[^/]+/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:52:\"organizer/[^/]+/attachment/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:42:\"organizer/[^/]+/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:53:\"organizer/[^/]+/attachment/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:45:\"organizer/[^/]+/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:56:\"organizer/[^/]+/attachment/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:42:\"organizer/[^/]+/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:53:\"organizer/[^/]+/attachment/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:34:\"organizer/([^/]+)(?:/([0-9]+))?/?$\";s:54:\"index.php?tribe_organizer=$matches[1]&page=$matches[2]\";s:26:\"organizer/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:36:\"organizer/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:56:\"organizer/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:51:\"organizer/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:51:\"organizer/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:32:\"organizer/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:33:\"event/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:43:\"event/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:63:\"event/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:58:\"event/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:58:\"event/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:39:\"event/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:22:\"event/([^/]+)/embed/?$\";s:45:\"index.php?tribe_events=$matches[1]&embed=true\";s:26:\"event/([^/]+)/trackback/?$\";s:39:\"index.php?tribe_events=$matches[1]&tb=1\";s:46:\"event/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:51:\"index.php?tribe_events=$matches[1]&feed=$matches[2]\";s:41:\"event/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:51:\"index.php?tribe_events=$matches[1]&feed=$matches[2]\";s:34:\"event/([^/]+)/page/?([0-9]{1,})/?$\";s:52:\"index.php?tribe_events=$matches[1]&paged=$matches[2]\";s:41:\"event/([^/]+)/comment-page-([0-9]{1,})/?$\";s:52:\"index.php?tribe_events=$matches[1]&cpage=$matches[2]\";s:31:\"event/([^/]+)/wc-api(/(.*))?/?$\";s:53:\"index.php?tribe_events=$matches[1]&wc-api=$matches[3]\";s:32:\"event/([^/]+)/edd-add(/(.*))?/?$\";s:54:\"index.php?tribe_events=$matches[1]&edd-add=$matches[3]\";s:35:\"event/([^/]+)/edd-remove(/(.*))?/?$\";s:57:\"index.php?tribe_events=$matches[1]&edd-remove=$matches[3]\";s:32:\"event/([^/]+)/edd-api(/(.*))?/?$\";s:54:\"index.php?tribe_events=$matches[1]&edd-api=$matches[3]\";s:37:\"event/[^/]+/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:48:\"event/[^/]+/attachment/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:38:\"event/[^/]+/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:49:\"event/[^/]+/attachment/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:41:\"event/[^/]+/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:52:\"event/[^/]+/attachment/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:38:\"event/[^/]+/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:49:\"event/[^/]+/attachment/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:30:\"event/([^/]+)(?:/([0-9]+))?/?$\";s:51:\"index.php?tribe_events=$matches[1]&page=$matches[2]\";s:22:\"event/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:32:\"event/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:52:\"event/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:47:\"event/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:47:\"event/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:28:\"event/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:54:\"events/category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:55:\"index.php?tribe_events_cat=$matches[1]&feed=$matches[2]\";s:49:\"events/category/(.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:55:\"index.php?tribe_events_cat=$matches[1]&feed=$matches[2]\";s:30:\"events/category/(.+?)/embed/?$\";s:49:\"index.php?tribe_events_cat=$matches[1]&embed=true\";s:42:\"events/category/(.+?)/page/?([0-9]{1,})/?$\";s:56:\"index.php?tribe_events_cat=$matches[1]&paged=$matches[2]\";s:24:\"events/category/(.+?)/?$\";s:38:\"index.php?tribe_events_cat=$matches[1]\";s:41:\"deleted_event/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:51:\"deleted_event/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:71:\"deleted_event/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:66:\"deleted_event/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:66:\"deleted_event/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:47:\"deleted_event/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:30:\"deleted_event/([^/]+)/embed/?$\";s:46:\"index.php?deleted_event=$matches[1]&embed=true\";s:34:\"deleted_event/([^/]+)/trackback/?$\";s:40:\"index.php?deleted_event=$matches[1]&tb=1\";s:42:\"deleted_event/([^/]+)/page/?([0-9]{1,})/?$\";s:53:\"index.php?deleted_event=$matches[1]&paged=$matches[2]\";s:49:\"deleted_event/([^/]+)/comment-page-([0-9]{1,})/?$\";s:53:\"index.php?deleted_event=$matches[1]&cpage=$matches[2]\";s:39:\"deleted_event/([^/]+)/wc-api(/(.*))?/?$\";s:54:\"index.php?deleted_event=$matches[1]&wc-api=$matches[3]\";s:40:\"deleted_event/([^/]+)/edd-add(/(.*))?/?$\";s:55:\"index.php?deleted_event=$matches[1]&edd-add=$matches[3]\";s:43:\"deleted_event/([^/]+)/edd-remove(/(.*))?/?$\";s:58:\"index.php?deleted_event=$matches[1]&edd-remove=$matches[3]\";s:40:\"deleted_event/([^/]+)/edd-api(/(.*))?/?$\";s:55:\"index.php?deleted_event=$matches[1]&edd-api=$matches[3]\";s:45:\"deleted_event/[^/]+/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:56:\"deleted_event/[^/]+/attachment/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:46:\"deleted_event/[^/]+/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:57:\"deleted_event/[^/]+/attachment/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:49:\"deleted_event/[^/]+/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:60:\"deleted_event/[^/]+/attachment/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:46:\"deleted_event/[^/]+/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:57:\"deleted_event/[^/]+/attachment/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:38:\"deleted_event/([^/]+)(?:/([0-9]+))?/?$\";s:52:\"index.php?deleted_event=$matches[1]&page=$matches[2]\";s:30:\"deleted_event/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:40:\"deleted_event/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:60:\"deleted_event/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:55:\"deleted_event/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:55:\"deleted_event/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:36:\"deleted_event/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:12:\"robots\\.txt$\";s:18:\"index.php?robots=1\";s:48:\".*wp-(atom|rdf|rss|rss2|feed|commentsrss2)\\.php$\";s:18:\"index.php?feed=old\";s:20:\".*wp-app\\.php(/.*)?$\";s:19:\"index.php?error=403\";s:18:\".*wp-register.php$\";s:23:\"index.php?register=true\";s:32:\"feed/(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:27:\"(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:8:\"embed/?$\";s:21:\"index.php?&embed=true\";s:20:\"page/?([0-9]{1,})/?$\";s:28:\"index.php?&paged=$matches[1]\";s:17:\"wc-api(/(.*))?/?$\";s:29:\"index.php?&wc-api=$matches[2]\";s:18:\"edd-add(/(.*))?/?$\";s:30:\"index.php?&edd-add=$matches[2]\";s:21:\"edd-remove(/(.*))?/?$\";s:33:\"index.php?&edd-remove=$matches[2]\";s:18:\"edd-api(/(.*))?/?$\";s:30:\"index.php?&edd-api=$matches[2]\";s:41:\"comments/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:36:\"comments/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:17:\"comments/embed/?$\";s:21:\"index.php?&embed=true\";s:26:\"comments/wc-api(/(.*))?/?$\";s:29:\"index.php?&wc-api=$matches[2]\";s:27:\"comments/edd-add(/(.*))?/?$\";s:30:\"index.php?&edd-add=$matches[2]\";s:30:\"comments/edd-remove(/(.*))?/?$\";s:33:\"index.php?&edd-remove=$matches[2]\";s:27:\"comments/edd-api(/(.*))?/?$\";s:30:\"index.php?&edd-api=$matches[2]\";s:44:\"search/(.+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:39:\"search/(.+)/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:20:\"search/(.+)/embed/?$\";s:34:\"index.php?s=$matches[1]&embed=true\";s:32:\"search/(.+)/page/?([0-9]{1,})/?$\";s:41:\"index.php?s=$matches[1]&paged=$matches[2]\";s:29:\"search/(.+)/wc-api(/(.*))?/?$\";s:42:\"index.php?s=$matches[1]&wc-api=$matches[3]\";s:30:\"search/(.+)/edd-add(/(.*))?/?$\";s:43:\"index.php?s=$matches[1]&edd-add=$matches[3]\";s:33:\"search/(.+)/edd-remove(/(.*))?/?$\";s:46:\"index.php?s=$matches[1]&edd-remove=$matches[3]\";s:30:\"search/(.+)/edd-api(/(.*))?/?$\";s:43:\"index.php?s=$matches[1]&edd-api=$matches[3]\";s:14:\"search/(.+)/?$\";s:23:\"index.php?s=$matches[1]\";s:47:\"author/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:42:\"author/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:23:\"author/([^/]+)/embed/?$\";s:44:\"index.php?author_name=$matches[1]&embed=true\";s:35:\"author/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?author_name=$matches[1]&paged=$matches[2]\";s:32:\"author/([^/]+)/wc-api(/(.*))?/?$\";s:52:\"index.php?author_name=$matches[1]&wc-api=$matches[3]\";s:33:\"author/([^/]+)/edd-add(/(.*))?/?$\";s:53:\"index.php?author_name=$matches[1]&edd-add=$matches[3]\";s:36:\"author/([^/]+)/edd-remove(/(.*))?/?$\";s:56:\"index.php?author_name=$matches[1]&edd-remove=$matches[3]\";s:33:\"author/([^/]+)/edd-api(/(.*))?/?$\";s:53:\"index.php?author_name=$matches[1]&edd-api=$matches[3]\";s:17:\"author/([^/]+)/?$\";s:33:\"index.php?author_name=$matches[1]\";s:69:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:64:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:45:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/embed/?$\";s:74:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&embed=true\";s:57:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$\";s:81:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]\";s:54:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/wc-api(/(.*))?/?$\";s:82:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&wc-api=$matches[5]\";s:55:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/edd-add(/(.*))?/?$\";s:83:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&edd-add=$matches[5]\";s:58:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/edd-remove(/(.*))?/?$\";s:86:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&edd-remove=$matches[5]\";s:55:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/edd-api(/(.*))?/?$\";s:83:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&edd-api=$matches[5]\";s:39:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$\";s:63:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]\";s:56:\"([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:51:\"([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:32:\"([0-9]{4})/([0-9]{1,2})/embed/?$\";s:58:\"index.php?year=$matches[1]&monthnum=$matches[2]&embed=true\";s:44:\"([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$\";s:65:\"index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]\";s:41:\"([0-9]{4})/([0-9]{1,2})/wc-api(/(.*))?/?$\";s:66:\"index.php?year=$matches[1]&monthnum=$matches[2]&wc-api=$matches[4]\";s:42:\"([0-9]{4})/([0-9]{1,2})/edd-add(/(.*))?/?$\";s:67:\"index.php?year=$matches[1]&monthnum=$matches[2]&edd-add=$matches[4]\";s:45:\"([0-9]{4})/([0-9]{1,2})/edd-remove(/(.*))?/?$\";s:70:\"index.php?year=$matches[1]&monthnum=$matches[2]&edd-remove=$matches[4]\";s:42:\"([0-9]{4})/([0-9]{1,2})/edd-api(/(.*))?/?$\";s:67:\"index.php?year=$matches[1]&monthnum=$matches[2]&edd-api=$matches[4]\";s:26:\"([0-9]{4})/([0-9]{1,2})/?$\";s:47:\"index.php?year=$matches[1]&monthnum=$matches[2]\";s:43:\"([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:38:\"([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:19:\"([0-9]{4})/embed/?$\";s:37:\"index.php?year=$matches[1]&embed=true\";s:31:\"([0-9]{4})/page/?([0-9]{1,})/?$\";s:44:\"index.php?year=$matches[1]&paged=$matches[2]\";s:28:\"([0-9]{4})/wc-api(/(.*))?/?$\";s:45:\"index.php?year=$matches[1]&wc-api=$matches[3]\";s:29:\"([0-9]{4})/edd-add(/(.*))?/?$\";s:46:\"index.php?year=$matches[1]&edd-add=$matches[3]\";s:32:\"([0-9]{4})/edd-remove(/(.*))?/?$\";s:49:\"index.php?year=$matches[1]&edd-remove=$matches[3]\";s:29:\"([0-9]{4})/edd-api(/(.*))?/?$\";s:46:\"index.php?year=$matches[1]&edd-api=$matches[3]\";s:13:\"([0-9]{4})/?$\";s:26:\"index.php?year=$matches[1]\";s:27:\".?.+?/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:37:\".?.+?/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:57:\".?.+?/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:33:\".?.+?/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:16:\"(.?.+?)/embed/?$\";s:41:\"index.php?pagename=$matches[1]&embed=true\";s:20:\"(.?.+?)/trackback/?$\";s:35:\"index.php?pagename=$matches[1]&tb=1\";s:40:\"(.?.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:35:\"(.?.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:28:\"(.?.+?)/page/?([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&paged=$matches[2]\";s:35:\"(.?.+?)/comment-page-([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&cpage=$matches[2]\";s:25:\"(.?.+?)/wc-api(/(.*))?/?$\";s:49:\"index.php?pagename=$matches[1]&wc-api=$matches[3]\";s:26:\"(.?.+?)/edd-add(/(.*))?/?$\";s:50:\"index.php?pagename=$matches[1]&edd-add=$matches[3]\";s:29:\"(.?.+?)/edd-remove(/(.*))?/?$\";s:53:\"index.php?pagename=$matches[1]&edd-remove=$matches[3]\";s:26:\"(.?.+?)/edd-api(/(.*))?/?$\";s:50:\"index.php?pagename=$matches[1]&edd-api=$matches[3]\";s:28:\"(.?.+?)/order-pay(/(.*))?/?$\";s:52:\"index.php?pagename=$matches[1]&order-pay=$matches[3]\";s:33:\"(.?.+?)/order-received(/(.*))?/?$\";s:57:\"index.php?pagename=$matches[1]&order-received=$matches[3]\";s:25:\"(.?.+?)/orders(/(.*))?/?$\";s:49:\"index.php?pagename=$matches[1]&orders=$matches[3]\";s:29:\"(.?.+?)/view-order(/(.*))?/?$\";s:53:\"index.php?pagename=$matches[1]&view-order=$matches[3]\";s:28:\"(.?.+?)/downloads(/(.*))?/?$\";s:52:\"index.php?pagename=$matches[1]&downloads=$matches[3]\";s:31:\"(.?.+?)/edit-account(/(.*))?/?$\";s:55:\"index.php?pagename=$matches[1]&edit-account=$matches[3]\";s:31:\"(.?.+?)/edit-address(/(.*))?/?$\";s:55:\"index.php?pagename=$matches[1]&edit-address=$matches[3]\";s:34:\"(.?.+?)/payment-methods(/(.*))?/?$\";s:58:\"index.php?pagename=$matches[1]&payment-methods=$matches[3]\";s:32:\"(.?.+?)/lost-password(/(.*))?/?$\";s:56:\"index.php?pagename=$matches[1]&lost-password=$matches[3]\";s:34:\"(.?.+?)/customer-logout(/(.*))?/?$\";s:58:\"index.php?pagename=$matches[1]&customer-logout=$matches[3]\";s:37:\"(.?.+?)/add-payment-method(/(.*))?/?$\";s:61:\"index.php?pagename=$matches[1]&add-payment-method=$matches[3]\";s:40:\"(.?.+?)/delete-payment-method(/(.*))?/?$\";s:64:\"index.php?pagename=$matches[1]&delete-payment-method=$matches[3]\";s:45:\"(.?.+?)/set-default-payment-method(/(.*))?/?$\";s:69:\"index.php?pagename=$matches[1]&set-default-payment-method=$matches[3]\";s:31:\".?.+?/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:42:\".?.+?/attachment/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:32:\".?.+?/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:43:\".?.+?/attachment/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:35:\".?.+?/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:46:\".?.+?/attachment/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:32:\".?.+?/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:43:\".?.+?/attachment/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:24:\"(.?.+?)(?:/([0-9]+))?/?$\";s:47:\"index.php?pagename=$matches[1]&page=$matches[2]\";s:27:\"[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:37:\"[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:57:\"[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\"[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\"[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:33:\"[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:16:\"([^/]+)/embed/?$\";s:37:\"index.php?name=$matches[1]&embed=true\";s:20:\"([^/]+)/trackback/?$\";s:31:\"index.php?name=$matches[1]&tb=1\";s:40:\"([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?name=$matches[1]&feed=$matches[2]\";s:35:\"([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?name=$matches[1]&feed=$matches[2]\";s:28:\"([^/]+)/page/?([0-9]{1,})/?$\";s:44:\"index.php?name=$matches[1]&paged=$matches[2]\";s:35:\"([^/]+)/comment-page-([0-9]{1,})/?$\";s:44:\"index.php?name=$matches[1]&cpage=$matches[2]\";s:25:\"([^/]+)/wc-api(/(.*))?/?$\";s:45:\"index.php?name=$matches[1]&wc-api=$matches[3]\";s:26:\"([^/]+)/edd-add(/(.*))?/?$\";s:46:\"index.php?name=$matches[1]&edd-add=$matches[3]\";s:29:\"([^/]+)/edd-remove(/(.*))?/?$\";s:49:\"index.php?name=$matches[1]&edd-remove=$matches[3]\";s:26:\"([^/]+)/edd-api(/(.*))?/?$\";s:46:\"index.php?name=$matches[1]&edd-api=$matches[3]\";s:31:\"[^/]+/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:42:\"[^/]+/attachment/([^/]+)/wc-api(/(.*))?/?$\";s:51:\"index.php?attachment=$matches[1]&wc-api=$matches[3]\";s:32:\"[^/]+/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:43:\"[^/]+/attachment/([^/]+)/edd-add(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-add=$matches[3]\";s:35:\"[^/]+/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:46:\"[^/]+/attachment/([^/]+)/edd-remove(/(.*))?/?$\";s:55:\"index.php?attachment=$matches[1]&edd-remove=$matches[3]\";s:32:\"[^/]+/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:43:\"[^/]+/attachment/([^/]+)/edd-api(/(.*))?/?$\";s:52:\"index.php?attachment=$matches[1]&edd-api=$matches[3]\";s:24:\"([^/]+)(?:/([0-9]+))?/?$\";s:43:\"index.php?name=$matches[1]&page=$matches[2]\";s:16:\"[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:26:\"[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:46:\"[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:41:\"[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:41:\"[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:22:\"[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";}','yes'),
	(30,'hack_file','0','yes'),
	(31,'blog_charset','UTF-8','yes'),
	(32,'moderation_keys','','no'),
	(34,'category_base','','yes'),
	(35,'ping_sites','http://rpc.pingomatic.com/','yes'),
	(36,'comment_max_links','2','yes'),
	(37,'gmt_offset','0','yes'),
	(38,'default_email_category','1','yes'),
	(39,'recently_edited','','no'),
	(40,'template','twentyseventeen','yes'),
	(41,'stylesheet','twentyseventeen','yes'),
	(42,'comment_whitelist','1','yes'),
	(43,'blacklist_keys','','no'),
	(44,'comment_registration','0','yes'),
	(45,'html_type','text/html','yes'),
	(46,'use_trackback','0','yes'),
	(47,'default_role','subscriber','yes'),
	(48,'db_version','45805','yes'),
	(49,'uploads_use_yearmonth_folders','1','yes'),
	(50,'upload_path','','yes'),
	(51,'blog_public','0','yes'),
	(52,'default_link_category','2','yes'),
	(53,'show_on_front','posts','yes'),
	(54,'tag_base','','yes'),
	(55,'show_avatars','1','yes'),
	(56,'avatar_rating','G','yes'),
	(57,'upload_url_path','','yes'),
	(58,'thumbnail_size_w','150','yes'),
	(59,'thumbnail_size_h','150','yes'),
	(60,'thumbnail_crop','1','yes'),
	(61,'medium_size_w','300','yes'),
	(62,'medium_size_h','300','yes'),
	(63,'avatar_default','mystery','yes'),
	(64,'large_size_w','1024','yes'),
	(65,'large_size_h','1024','yes'),
	(66,'image_default_link_type','none','yes'),
	(67,'image_default_size','','yes'),
	(68,'image_default_align','','yes'),
	(69,'close_comments_for_old_posts','0','yes'),
	(70,'close_comments_days_old','14','yes'),
	(71,'thread_comments','1','yes'),
	(72,'thread_comments_depth','5','yes'),
	(73,'page_comments','0','yes'),
	(74,'comments_per_page','50','yes'),
	(75,'default_comments_page','newest','yes'),
	(76,'comment_order','asc','yes'),
	(77,'sticky_posts','a:0:{}','yes'),
	(78,'widget_categories','a:2:{i:2;a:4:{s:5:\"title\";s:0:\"\";s:5:\"count\";i:0;s:12:\"hierarchical\";i:0;s:8:\"dropdown\";i:0;}s:12:\"_multiwidget\";i:1;}','yes'),
	(79,'widget_text','a:0:{}','yes'),
	(80,'widget_rss','a:0:{}','yes'),
	(81,'uninstall_plugins','a:1:{s:45:\"woocommerce-services/woocommerce-services.php\";a:2:{i:0;s:17:\"WC_Connect_Loader\";i:1;s:16:\"plugin_uninstall\";}}','no'),
	(82,'timezone_string','','yes'),
	(83,'page_for_posts','0','yes'),
	(84,'page_on_front','0','yes'),
	(85,'default_post_format','0','yes'),
	(86,'link_manager_enabled','0','yes'),
	(87,'finished_splitting_shared_terms','1','yes'),
	(88,'site_icon','0','yes'),
	(89,'medium_large_size_w','768','yes'),
	(90,'medium_large_size_h','0','yes'),
	(91,'initial_db_version','38590','yes'),
	(92,'wp_user_roles','a:10:{s:13:\"administrator\";a:2:{s:4:\"name\";s:13:\"Administrator\";s:12:\"capabilities\";a:199:{s:13:\"switch_themes\";b:1;s:11:\"edit_themes\";b:1;s:16:\"activate_plugins\";b:1;s:12:\"edit_plugins\";b:1;s:10:\"edit_users\";b:1;s:10:\"edit_files\";b:1;s:14:\"manage_options\";b:1;s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:6:\"import\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:8:\"level_10\";b:1;s:7:\"level_9\";b:1;s:7:\"level_8\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;s:12:\"delete_users\";b:1;s:12:\"create_users\";b:1;s:17:\"unfiltered_upload\";b:1;s:14:\"edit_dashboard\";b:1;s:14:\"update_plugins\";b:1;s:14:\"delete_plugins\";b:1;s:15:\"install_plugins\";b:1;s:13:\"update_themes\";b:1;s:14:\"install_themes\";b:1;s:11:\"update_core\";b:1;s:10:\"list_users\";b:1;s:12:\"remove_users\";b:1;s:13:\"promote_users\";b:1;s:18:\"edit_theme_options\";b:1;s:13:\"delete_themes\";b:1;s:6:\"export\";b:1;s:25:\"read_private_tribe_events\";b:1;s:17:\"edit_tribe_events\";b:1;s:24:\"edit_others_tribe_events\";b:1;s:25:\"edit_private_tribe_events\";b:1;s:27:\"edit_published_tribe_events\";b:1;s:19:\"delete_tribe_events\";b:1;s:26:\"delete_others_tribe_events\";b:1;s:27:\"delete_private_tribe_events\";b:1;s:29:\"delete_published_tribe_events\";b:1;s:20:\"publish_tribe_events\";b:1;s:25:\"read_private_tribe_venues\";b:1;s:17:\"edit_tribe_venues\";b:1;s:24:\"edit_others_tribe_venues\";b:1;s:25:\"edit_private_tribe_venues\";b:1;s:27:\"edit_published_tribe_venues\";b:1;s:19:\"delete_tribe_venues\";b:1;s:26:\"delete_others_tribe_venues\";b:1;s:27:\"delete_private_tribe_venues\";b:1;s:29:\"delete_published_tribe_venues\";b:1;s:20:\"publish_tribe_venues\";b:1;s:29:\"read_private_tribe_organizers\";b:1;s:21:\"edit_tribe_organizers\";b:1;s:28:\"edit_others_tribe_organizers\";b:1;s:29:\"edit_private_tribe_organizers\";b:1;s:31:\"edit_published_tribe_organizers\";b:1;s:23:\"delete_tribe_organizers\";b:1;s:30:\"delete_others_tribe_organizers\";b:1;s:31:\"delete_private_tribe_organizers\";b:1;s:33:\"delete_published_tribe_organizers\";b:1;s:24:\"publish_tribe_organizers\";b:1;s:31:\"read_private_aggregator-records\";b:1;s:23:\"edit_aggregator-records\";b:1;s:30:\"edit_others_aggregator-records\";b:1;s:31:\"edit_private_aggregator-records\";b:1;s:33:\"edit_published_aggregator-records\";b:1;s:25:\"delete_aggregator-records\";b:1;s:32:\"delete_others_aggregator-records\";b:1;s:33:\"delete_private_aggregator-records\";b:1;s:35:\"delete_published_aggregator-records\";b:1;s:26:\"publish_aggregator-records\";b:1;s:17:\"view_shop_reports\";b:1;s:24:\"view_shop_sensitive_data\";b:1;s:19:\"export_shop_reports\";b:1;s:21:\"manage_shop_discounts\";b:1;s:20:\"manage_shop_settings\";b:1;s:18:\"view_product_stats\";b:1;s:15:\"import_products\";b:1;s:17:\"edit_shop_payment\";b:1;s:17:\"read_shop_payment\";b:1;s:19:\"delete_shop_payment\";b:1;s:18:\"edit_shop_payments\";b:1;s:25:\"edit_others_shop_payments\";b:1;s:21:\"publish_shop_payments\";b:1;s:26:\"read_private_shop_payments\";b:1;s:20:\"delete_shop_payments\";b:1;s:28:\"delete_private_shop_payments\";b:1;s:30:\"delete_published_shop_payments\";b:1;s:27:\"delete_others_shop_payments\";b:1;s:26:\"edit_private_shop_payments\";b:1;s:28:\"edit_published_shop_payments\";b:1;s:25:\"manage_shop_payment_terms\";b:1;s:23:\"edit_shop_payment_terms\";b:1;s:25:\"delete_shop_payment_terms\";b:1;s:25:\"assign_shop_payment_terms\";b:1;s:23:\"view_shop_payment_stats\";b:1;s:20:\"import_shop_payments\";b:1;s:18:\"edit_shop_discount\";b:1;s:18:\"read_shop_discount\";b:1;s:20:\"delete_shop_discount\";b:1;s:19:\"edit_shop_discounts\";b:1;s:26:\"edit_others_shop_discounts\";b:1;s:22:\"publish_shop_discounts\";b:1;s:27:\"read_private_shop_discounts\";b:1;s:21:\"delete_shop_discounts\";b:1;s:29:\"delete_private_shop_discounts\";b:1;s:31:\"delete_published_shop_discounts\";b:1;s:28:\"delete_others_shop_discounts\";b:1;s:27:\"edit_private_shop_discounts\";b:1;s:29:\"edit_published_shop_discounts\";b:1;s:26:\"manage_shop_discount_terms\";b:1;s:24:\"edit_shop_discount_terms\";b:1;s:26:\"delete_shop_discount_terms\";b:1;s:26:\"assign_shop_discount_terms\";b:1;s:24:\"view_shop_discount_stats\";b:1;s:21:\"import_shop_discounts\";b:1;s:18:\"manage_woocommerce\";b:1;s:24:\"view_woocommerce_reports\";b:1;s:12:\"edit_product\";b:1;s:12:\"read_product\";b:1;s:14:\"delete_product\";b:1;s:13:\"edit_products\";b:1;s:20:\"edit_others_products\";b:1;s:16:\"publish_products\";b:1;s:21:\"read_private_products\";b:1;s:15:\"delete_products\";b:1;s:23:\"delete_private_products\";b:1;s:25:\"delete_published_products\";b:1;s:22:\"delete_others_products\";b:1;s:21:\"edit_private_products\";b:1;s:23:\"edit_published_products\";b:1;s:20:\"manage_product_terms\";b:1;s:18:\"edit_product_terms\";b:1;s:20:\"delete_product_terms\";b:1;s:20:\"assign_product_terms\";b:1;s:15:\"edit_shop_order\";b:1;s:15:\"read_shop_order\";b:1;s:17:\"delete_shop_order\";b:1;s:16:\"edit_shop_orders\";b:1;s:23:\"edit_others_shop_orders\";b:1;s:19:\"publish_shop_orders\";b:1;s:24:\"read_private_shop_orders\";b:1;s:18:\"delete_shop_orders\";b:1;s:26:\"delete_private_shop_orders\";b:1;s:28:\"delete_published_shop_orders\";b:1;s:25:\"delete_others_shop_orders\";b:1;s:24:\"edit_private_shop_orders\";b:1;s:26:\"edit_published_shop_orders\";b:1;s:23:\"manage_shop_order_terms\";b:1;s:21:\"edit_shop_order_terms\";b:1;s:23:\"delete_shop_order_terms\";b:1;s:23:\"assign_shop_order_terms\";b:1;s:16:\"edit_shop_coupon\";b:1;s:16:\"read_shop_coupon\";b:1;s:18:\"delete_shop_coupon\";b:1;s:17:\"edit_shop_coupons\";b:1;s:24:\"edit_others_shop_coupons\";b:1;s:20:\"publish_shop_coupons\";b:1;s:25:\"read_private_shop_coupons\";b:1;s:19:\"delete_shop_coupons\";b:1;s:27:\"delete_private_shop_coupons\";b:1;s:29:\"delete_published_shop_coupons\";b:1;s:26:\"delete_others_shop_coupons\";b:1;s:25:\"edit_private_shop_coupons\";b:1;s:27:\"edit_published_shop_coupons\";b:1;s:24:\"manage_shop_coupon_terms\";b:1;s:22:\"edit_shop_coupon_terms\";b:1;s:24:\"delete_shop_coupon_terms\";b:1;s:24:\"assign_shop_coupon_terms\";b:1;}}s:6:\"editor\";a:2:{s:4:\"name\";s:6:\"Editor\";s:12:\"capabilities\";a:74:{s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;s:25:\"read_private_tribe_events\";b:1;s:17:\"edit_tribe_events\";b:1;s:24:\"edit_others_tribe_events\";b:1;s:25:\"edit_private_tribe_events\";b:1;s:27:\"edit_published_tribe_events\";b:1;s:19:\"delete_tribe_events\";b:1;s:26:\"delete_others_tribe_events\";b:1;s:27:\"delete_private_tribe_events\";b:1;s:29:\"delete_published_tribe_events\";b:1;s:20:\"publish_tribe_events\";b:1;s:25:\"read_private_tribe_venues\";b:1;s:17:\"edit_tribe_venues\";b:1;s:24:\"edit_others_tribe_venues\";b:1;s:25:\"edit_private_tribe_venues\";b:1;s:27:\"edit_published_tribe_venues\";b:1;s:19:\"delete_tribe_venues\";b:1;s:26:\"delete_others_tribe_venues\";b:1;s:27:\"delete_private_tribe_venues\";b:1;s:29:\"delete_published_tribe_venues\";b:1;s:20:\"publish_tribe_venues\";b:1;s:29:\"read_private_tribe_organizers\";b:1;s:21:\"edit_tribe_organizers\";b:1;s:28:\"edit_others_tribe_organizers\";b:1;s:29:\"edit_private_tribe_organizers\";b:1;s:31:\"edit_published_tribe_organizers\";b:1;s:23:\"delete_tribe_organizers\";b:1;s:30:\"delete_others_tribe_organizers\";b:1;s:31:\"delete_private_tribe_organizers\";b:1;s:33:\"delete_published_tribe_organizers\";b:1;s:24:\"publish_tribe_organizers\";b:1;s:31:\"read_private_aggregator-records\";b:1;s:23:\"edit_aggregator-records\";b:1;s:30:\"edit_others_aggregator-records\";b:1;s:31:\"edit_private_aggregator-records\";b:1;s:33:\"edit_published_aggregator-records\";b:1;s:25:\"delete_aggregator-records\";b:1;s:32:\"delete_others_aggregator-records\";b:1;s:33:\"delete_private_aggregator-records\";b:1;s:35:\"delete_published_aggregator-records\";b:1;s:26:\"publish_aggregator-records\";b:1;}}s:6:\"author\";a:2:{s:4:\"name\";s:6:\"Author\";s:12:\"capabilities\";a:30:{s:12:\"upload_files\";b:1;s:10:\"edit_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:17:\"edit_tribe_events\";b:1;s:27:\"edit_published_tribe_events\";b:1;s:19:\"delete_tribe_events\";b:1;s:29:\"delete_published_tribe_events\";b:1;s:20:\"publish_tribe_events\";b:1;s:17:\"edit_tribe_venues\";b:1;s:27:\"edit_published_tribe_venues\";b:1;s:19:\"delete_tribe_venues\";b:1;s:29:\"delete_published_tribe_venues\";b:1;s:20:\"publish_tribe_venues\";b:1;s:21:\"edit_tribe_organizers\";b:1;s:31:\"edit_published_tribe_organizers\";b:1;s:23:\"delete_tribe_organizers\";b:1;s:33:\"delete_published_tribe_organizers\";b:1;s:24:\"publish_tribe_organizers\";b:1;s:23:\"edit_aggregator-records\";b:1;s:33:\"edit_published_aggregator-records\";b:1;s:25:\"delete_aggregator-records\";b:1;s:35:\"delete_published_aggregator-records\";b:1;s:26:\"publish_aggregator-records\";b:1;}}s:11:\"contributor\";a:2:{s:4:\"name\";s:11:\"Contributor\";s:12:\"capabilities\";a:13:{s:10:\"edit_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;s:17:\"edit_tribe_events\";b:1;s:19:\"delete_tribe_events\";b:1;s:17:\"edit_tribe_venues\";b:1;s:19:\"delete_tribe_venues\";b:1;s:21:\"edit_tribe_organizers\";b:1;s:23:\"delete_tribe_organizers\";b:1;s:23:\"edit_aggregator-records\";b:1;s:25:\"delete_aggregator-records\";b:1;}}s:10:\"subscriber\";a:2:{s:4:\"name\";s:10:\"Subscriber\";s:12:\"capabilities\";a:2:{s:4:\"read\";b:1;s:7:\"level_0\";b:1;}}s:15:\"shop_accountant\";a:2:{s:4:\"name\";s:15:\"Shop Accountant\";s:12:\"capabilities\";a:8:{s:4:\"read\";b:1;s:10:\"edit_posts\";b:0;s:12:\"delete_posts\";b:0;s:13:\"edit_products\";b:1;s:21:\"read_private_products\";b:1;s:17:\"view_shop_reports\";b:1;s:19:\"export_shop_reports\";b:1;s:18:\"edit_shop_payments\";b:1;}}s:11:\"shop_worker\";a:2:{s:4:\"name\";s:11:\"Shop Worker\";s:12:\"capabilities\";a:61:{s:4:\"read\";b:1;s:10:\"edit_posts\";b:0;s:12:\"upload_files\";b:1;s:12:\"delete_posts\";b:0;s:12:\"edit_product\";b:1;s:12:\"read_product\";b:1;s:14:\"delete_product\";b:1;s:13:\"edit_products\";b:1;s:20:\"edit_others_products\";b:1;s:16:\"publish_products\";b:1;s:21:\"read_private_products\";b:1;s:15:\"delete_products\";b:1;s:23:\"delete_private_products\";b:1;s:25:\"delete_published_products\";b:1;s:22:\"delete_others_products\";b:1;s:21:\"edit_private_products\";b:1;s:23:\"edit_published_products\";b:1;s:20:\"manage_product_terms\";b:1;s:18:\"edit_product_terms\";b:1;s:20:\"delete_product_terms\";b:1;s:20:\"assign_product_terms\";b:1;s:18:\"view_product_stats\";b:1;s:15:\"import_products\";b:1;s:17:\"edit_shop_payment\";b:1;s:17:\"read_shop_payment\";b:1;s:19:\"delete_shop_payment\";b:1;s:18:\"edit_shop_payments\";b:1;s:25:\"edit_others_shop_payments\";b:1;s:21:\"publish_shop_payments\";b:1;s:26:\"read_private_shop_payments\";b:1;s:20:\"delete_shop_payments\";b:1;s:28:\"delete_private_shop_payments\";b:1;s:30:\"delete_published_shop_payments\";b:1;s:27:\"delete_others_shop_payments\";b:1;s:26:\"edit_private_shop_payments\";b:1;s:28:\"edit_published_shop_payments\";b:1;s:25:\"manage_shop_payment_terms\";b:1;s:23:\"edit_shop_payment_terms\";b:1;s:25:\"delete_shop_payment_terms\";b:1;s:25:\"assign_shop_payment_terms\";b:1;s:23:\"view_shop_payment_stats\";b:1;s:20:\"import_shop_payments\";b:1;s:18:\"edit_shop_discount\";b:1;s:18:\"read_shop_discount\";b:1;s:20:\"delete_shop_discount\";b:1;s:19:\"edit_shop_discounts\";b:1;s:26:\"edit_others_shop_discounts\";b:1;s:22:\"publish_shop_discounts\";b:1;s:27:\"read_private_shop_discounts\";b:1;s:21:\"delete_shop_discounts\";b:1;s:29:\"delete_private_shop_discounts\";b:1;s:31:\"delete_published_shop_discounts\";b:1;s:28:\"delete_others_shop_discounts\";b:1;s:27:\"edit_private_shop_discounts\";b:1;s:29:\"edit_published_shop_discounts\";b:1;s:26:\"manage_shop_discount_terms\";b:1;s:24:\"edit_shop_discount_terms\";b:1;s:26:\"delete_shop_discount_terms\";b:1;s:26:\"assign_shop_discount_terms\";b:1;s:24:\"view_shop_discount_stats\";b:1;s:21:\"import_shop_discounts\";b:1;}}s:11:\"shop_vendor\";a:2:{s:4:\"name\";s:11:\"Shop Vendor\";s:12:\"capabilities\";a:11:{s:4:\"read\";b:1;s:10:\"edit_posts\";b:0;s:12:\"upload_files\";b:1;s:12:\"delete_posts\";b:0;s:12:\"edit_product\";b:1;s:13:\"edit_products\";b:1;s:14:\"delete_product\";b:1;s:15:\"delete_products\";b:1;s:16:\"publish_products\";b:1;s:23:\"edit_published_products\";b:1;s:20:\"assign_product_terms\";b:1;}}s:8:\"customer\";a:2:{s:4:\"name\";s:8:\"Customer\";s:12:\"capabilities\";a:1:{s:4:\"read\";b:1;}}s:12:\"shop_manager\";a:2:{s:4:\"name\";s:12:\"Shop manager\";s:12:\"capabilities\";a:92:{s:7:\"level_9\";b:1;s:7:\"level_8\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:4:\"read\";b:1;s:18:\"read_private_pages\";b:1;s:18:\"read_private_posts\";b:1;s:10:\"edit_posts\";b:1;s:10:\"edit_pages\";b:1;s:20:\"edit_published_posts\";b:1;s:20:\"edit_published_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"edit_private_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:17:\"edit_others_pages\";b:1;s:13:\"publish_posts\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_posts\";b:1;s:12:\"delete_pages\";b:1;s:20:\"delete_private_pages\";b:1;s:20:\"delete_private_posts\";b:1;s:22:\"delete_published_pages\";b:1;s:22:\"delete_published_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:19:\"delete_others_pages\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:17:\"moderate_comments\";b:1;s:12:\"upload_files\";b:1;s:6:\"export\";b:1;s:6:\"import\";b:1;s:10:\"list_users\";b:1;s:18:\"edit_theme_options\";b:1;s:18:\"manage_woocommerce\";b:1;s:24:\"view_woocommerce_reports\";b:1;s:12:\"edit_product\";b:1;s:12:\"read_product\";b:1;s:14:\"delete_product\";b:1;s:13:\"edit_products\";b:1;s:20:\"edit_others_products\";b:1;s:16:\"publish_products\";b:1;s:21:\"read_private_products\";b:1;s:15:\"delete_products\";b:1;s:23:\"delete_private_products\";b:1;s:25:\"delete_published_products\";b:1;s:22:\"delete_others_products\";b:1;s:21:\"edit_private_products\";b:1;s:23:\"edit_published_products\";b:1;s:20:\"manage_product_terms\";b:1;s:18:\"edit_product_terms\";b:1;s:20:\"delete_product_terms\";b:1;s:20:\"assign_product_terms\";b:1;s:15:\"edit_shop_order\";b:1;s:15:\"read_shop_order\";b:1;s:17:\"delete_shop_order\";b:1;s:16:\"edit_shop_orders\";b:1;s:23:\"edit_others_shop_orders\";b:1;s:19:\"publish_shop_orders\";b:1;s:24:\"read_private_shop_orders\";b:1;s:18:\"delete_shop_orders\";b:1;s:26:\"delete_private_shop_orders\";b:1;s:28:\"delete_published_shop_orders\";b:1;s:25:\"delete_others_shop_orders\";b:1;s:24:\"edit_private_shop_orders\";b:1;s:26:\"edit_published_shop_orders\";b:1;s:23:\"manage_shop_order_terms\";b:1;s:21:\"edit_shop_order_terms\";b:1;s:23:\"delete_shop_order_terms\";b:1;s:23:\"assign_shop_order_terms\";b:1;s:16:\"edit_shop_coupon\";b:1;s:16:\"read_shop_coupon\";b:1;s:18:\"delete_shop_coupon\";b:1;s:17:\"edit_shop_coupons\";b:1;s:24:\"edit_others_shop_coupons\";b:1;s:20:\"publish_shop_coupons\";b:1;s:25:\"read_private_shop_coupons\";b:1;s:19:\"delete_shop_coupons\";b:1;s:27:\"delete_private_shop_coupons\";b:1;s:29:\"delete_published_shop_coupons\";b:1;s:26:\"delete_others_shop_coupons\";b:1;s:25:\"edit_private_shop_coupons\";b:1;s:27:\"edit_published_shop_coupons\";b:1;s:24:\"manage_shop_coupon_terms\";b:1;s:22:\"edit_shop_coupon_terms\";b:1;s:24:\"delete_shop_coupon_terms\";b:1;s:24:\"assign_shop_coupon_terms\";b:1;}}}','yes'),
	(93,'fresh_site','0','yes'),
	(94,'widget_search','a:2:{i:2;a:1:{s:5:\"title\";s:0:\"\";}s:12:\"_multiwidget\";i:1;}','yes'),
	(95,'widget_recent-posts','a:2:{i:2;a:2:{s:5:\"title\";s:0:\"\";s:6:\"number\";i:5;}s:12:\"_multiwidget\";i:1;}','yes'),
	(96,'widget_recent-comments','a:2:{i:2;a:2:{s:5:\"title\";s:0:\"\";s:6:\"number\";i:5;}s:12:\"_multiwidget\";i:1;}','yes'),
	(97,'widget_archives','a:2:{i:2;a:3:{s:5:\"title\";s:0:\"\";s:5:\"count\";i:0;s:8:\"dropdown\";i:0;}s:12:\"_multiwidget\";i:1;}','yes'),
	(98,'widget_meta','a:2:{i:2;a:1:{s:5:\"title\";s:0:\"\";}s:12:\"_multiwidget\";i:1;}','yes'),
	(99,'sidebars_widgets','a:5:{s:19:\"wp_inactive_widgets\";a:0:{}s:9:\"sidebar-1\";a:6:{i:0;s:8:\"search-2\";i:1;s:14:\"recent-posts-2\";i:2;s:17:\"recent-comments-2\";i:3;s:10:\"archives-2\";i:4;s:12:\"categories-2\";i:5;s:6:\"meta-2\";}s:9:\"sidebar-2\";a:0:{}s:9:\"sidebar-3\";a:0:{}s:13:\"array_version\";i:3;}','yes'),
	(100,'widget_pages','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(101,'widget_calendar','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(102,'widget_media_audio','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(103,'widget_media_image','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(104,'widget_media_gallery','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(105,'widget_media_video','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(106,'widget_tag_cloud','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(107,'widget_nav_menu','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(108,'widget_custom_html','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(109,'cron','a:18:{i:1583990192;a:1:{s:34:\"wp_privacy_delete_old_export_files\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}}i:1583990336;a:1:{s:39:\"tribe_aggregator_process_insert_records\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:17:\"tribe-every15mins\";s:4:\"args\";a:0:{}s:8:\"interval\";i:900;}}}i:1583991208;a:2:{s:32:\"woocommerce_cancel_unpaid_orders\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:2:{s:8:\"schedule\";b:0;s:4:\"args\";a:0:{}}}s:33:\"wc_admin_process_orders_milestone\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}}i:1583991238;a:1:{s:29:\"wc_admin_unsnooze_admin_notes\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}}i:1583993471;a:1:{s:20:\"jetpack_clean_nonces\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}}i:1583998408;a:1:{s:24:\"woocommerce_cleanup_logs\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1584004234;a:1:{s:30:\"wp_scheduled_auto_draft_delete\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1584008853;a:3:{s:16:\"wp_version_check\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:17:\"wp_update_plugins\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:16:\"wp_update_themes\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1584008857;a:2:{s:19:\"wp_scheduled_delete\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:25:\"delete_expired_transients\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1584008893;a:1:{s:24:\"tribe_common_log_cleanup\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1584009208;a:1:{s:28:\"woocommerce_cleanup_sessions\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1584030810;a:1:{s:30:\"tribe_schedule_transient_purge\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1584057600;a:1:{s:27:\"woocommerce_scheduled_sales\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1584074008;a:1:{s:32:\"recovery_mode_clean_expired_keys\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1584074018;a:2:{s:33:\"woocommerce_cleanup_personal_data\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:30:\"woocommerce_tracker_send_event\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1584074033;a:1:{s:14:\"wc_admin_daily\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1585285874;a:1:{s:25:\"woocommerce_geoip_updater\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:11:\"fifteendays\";s:4:\"args\";a:0:{}s:8:\"interval\";i:1296000;}}}s:7:\"version\";i:2;}','yes'),
	(133,'tribe_events_calendar_options','a:29:{s:25:\"ticket-enabled-post-types\";a:1:{i:0;s:4:\"page\";}s:31:\"previous_event_tickets_versions\";a:4:{i:0;s:1:\"0\";i:1;s:5:\"4.7.1\";i:2;s:5:\"4.7.3\";i:3;s:7:\"4.7.3.1\";}s:28:\"latest_event_tickets_version\";s:6:\"4.11.5\";s:33:\"last-update-message-event-tickets\";s:5:\"4.7.3\";s:34:\"ticket-authentication-requirements\";N;s:20:\"ticket-paypal-enable\";s:3:\"yes\";s:23:\"ticket-paypal-configure\";N;s:31:\"ticket-paypal-ipn-config-status\";N;s:21:\"ticket-paypal-sandbox\";b:1;s:28:\"ticket-paypal-notify-history\";N;s:24:\"ticket-paypal-notify-url\";s:25:\"http://01a73483.ngrok.io/\";s:19:\"ticket-paypal-email\";s:20:\"merchant@example.com\";s:25:\"ticket-paypal-ipn-enabled\";s:3:\"yes\";s:29:\"ticket-paypal-ipn-address-set\";s:3:\"yes\";s:29:\"ticket-commerce-currency-code\";s:3:\"USD\";s:28:\"ticket-paypal-stock-handling\";s:10:\"on-pending\";s:26:\"ticket-paypal-success-page\";s:1:\"2\";s:45:\"ticket-paypal-confirmation-email-sender-email\";s:18:\"admin@commerce.dev\";s:44:\"ticket-paypal-confirmation-email-sender-name\";s:5:\"admin\";s:40:\"ticket-paypal-confirmation-email-subject\";s:17:\"You have tickets!\";s:14:\"schema-version\";s:7:\"5.0.2.1\";s:21:\"previous_ecp_versions\";a:2:{i:0;s:1:\"0\";i:1;s:6:\"4.6.18\";}s:18:\"latest_ecp_version\";s:7:\"5.0.2.1\";s:27:\"recurring_events_are_hidden\";s:6:\"hidden\";s:16:\"tribeEnableViews\";a:3:{i:0;s:4:\"list\";i:1;s:5:\"month\";i:2;s:3:\"day\";}s:33:\"event-tickets-plus-schema-version\";s:6:\"4.11.4\";s:28:\"event-tickets-schema-version\";s:6:\"4.11.5\";s:36:\"previous_event_tickets_plus_versions\";a:1:{i:0;s:1:\"0\";}s:33:\"latest_event_tickets_plus_version\";s:6:\"4.11.4\";}','yes'),
	(158,'active_plugins','a:2:{i:2;s:31:\"event-tickets/event-tickets.php\";i:4;s:43:\"the-events-calendar/the-events-calendar.php\";}','yes'),
	(159,'theme_mods_twentyseventeen','a:1:{s:18:\"custom_css_post_id\";i:-1;}','yes'),
	(245,'_site_transient_update_themes','O:8:\"stdClass\":4:{s:12:\"last_checked\";i:1583987675;s:7:\"checked\";a:4:{s:6:\"Divi-4\";s:5:\"4.0.9\";s:14:\"twentynineteen\";s:3:\"1.4\";s:15:\"twentyseventeen\";s:3:\"2.2\";s:12:\"twentytwenty\";s:3:\"1.1\";}s:8:\"response\";a:0:{}s:12:\"translations\";a:0:{}}','no'),
	(247,'tribe_last_save_post','1583989807.0097','yes'),
	(248,'widget_tribe-events-list-widget','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(249,'tribe-skip-welcome','1','yes'),
	(251,'tribe_skip_welcome','1','yes'),
	(275,'woocommerce_store_address','1122, 5th Av.','yes'),
	(276,'woocommerce_store_address_2','','yes'),
	(277,'woocommerce_store_city','New York','yes'),
	(278,'woocommerce_default_country','IT:AG','yes'),
	(279,'woocommerce_store_postcode','10001','yes'),
	(280,'woocommerce_allowed_countries','all','yes'),
	(281,'woocommerce_all_except_countries','','yes'),
	(282,'woocommerce_specific_allowed_countries','','yes'),
	(283,'woocommerce_ship_to_countries','','yes'),
	(284,'woocommerce_specific_ship_to_countries','','yes'),
	(285,'woocommerce_default_customer_address','geolocation','yes'),
	(286,'woocommerce_calc_taxes','no','yes'),
	(287,'woocommerce_enable_coupons','yes','yes'),
	(288,'woocommerce_calc_discounts_sequentially','no','no'),
	(289,'woocommerce_currency','EUR','yes'),
	(290,'woocommerce_currency_pos','right','yes'),
	(291,'woocommerce_price_thousand_sep','.','yes'),
	(292,'woocommerce_price_decimal_sep',',','yes'),
	(293,'woocommerce_price_num_decimals','2','yes'),
	(294,'woocommerce_shop_page_id','6','yes'),
	(295,'woocommerce_cart_redirect_after_add','no','yes'),
	(296,'woocommerce_enable_ajax_add_to_cart','yes','yes'),
	(297,'woocommerce_weight_unit','kg','yes'),
	(298,'woocommerce_dimension_unit','cm','yes'),
	(299,'woocommerce_enable_reviews','yes','yes'),
	(300,'woocommerce_review_rating_verification_label','yes','no'),
	(301,'woocommerce_review_rating_verification_required','no','no'),
	(302,'woocommerce_enable_review_rating','yes','yes'),
	(303,'woocommerce_review_rating_required','yes','no'),
	(304,'woocommerce_manage_stock','yes','yes'),
	(305,'woocommerce_hold_stock_minutes','60','no'),
	(306,'woocommerce_notify_low_stock','yes','no'),
	(307,'woocommerce_notify_no_stock','yes','no'),
	(308,'woocommerce_stock_email_recipient','admin@commerce.dev','no'),
	(309,'woocommerce_notify_low_stock_amount','2','no'),
	(310,'woocommerce_notify_no_stock_amount','0','yes'),
	(311,'woocommerce_hide_out_of_stock_items','no','yes'),
	(312,'woocommerce_stock_format','','yes'),
	(313,'woocommerce_file_download_method','force','no'),
	(314,'woocommerce_downloads_require_login','no','no'),
	(315,'woocommerce_downloads_grant_access_after_payment','yes','no'),
	(316,'woocommerce_prices_include_tax','no','yes'),
	(317,'woocommerce_tax_based_on','shipping','yes'),
	(318,'woocommerce_shipping_tax_class','inherit','yes'),
	(319,'woocommerce_tax_round_at_subtotal','no','yes'),
	(321,'woocommerce_tax_display_shop','excl','yes'),
	(322,'woocommerce_tax_display_cart','excl','yes'),
	(323,'woocommerce_price_display_suffix','','yes'),
	(324,'woocommerce_tax_total_display','itemized','no'),
	(325,'woocommerce_enable_shipping_calc','yes','no'),
	(326,'woocommerce_shipping_cost_requires_address','no','yes'),
	(327,'woocommerce_ship_to_destination','billing','no'),
	(328,'woocommerce_shipping_debug_mode','no','yes'),
	(329,'woocommerce_enable_guest_checkout','yes','no'),
	(330,'woocommerce_enable_checkout_login_reminder','no','no'),
	(331,'woocommerce_enable_signup_and_login_from_checkout','no','no'),
	(332,'woocommerce_enable_myaccount_registration','no','no'),
	(333,'woocommerce_registration_generate_username','yes','no'),
	(334,'woocommerce_registration_generate_password','yes','no'),
	(335,'woocommerce_erasure_request_removes_order_data','no','no'),
	(336,'woocommerce_erasure_request_removes_download_data','no','no'),
	(337,'wp_page_for_privacy_policy','','yes'),
	(338,'woocommerce_registration_privacy_policy_text','Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our [privacy_policy].','yes'),
	(339,'woocommerce_checkout_privacy_policy_text','Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our [privacy_policy].','yes'),
	(340,'woocommerce_delete_inactive_accounts','a:2:{s:6:\"number\";s:0:\"\";s:4:\"unit\";s:6:\"months\";}','no'),
	(341,'woocommerce_trash_pending_orders','','no'),
	(342,'woocommerce_trash_failed_orders','','no'),
	(343,'woocommerce_trash_cancelled_orders','','no'),
	(344,'woocommerce_anonymize_completed_orders','a:2:{s:6:\"number\";s:0:\"\";s:4:\"unit\";s:6:\"months\";}','no'),
	(345,'woocommerce_email_from_name','Tribe Commerce','no'),
	(346,'woocommerce_email_from_address','admin@commerce.dev','no'),
	(347,'woocommerce_email_header_image','','no'),
	(348,'woocommerce_email_footer_text','{site_title}','no'),
	(349,'woocommerce_email_base_color','#96588a','no'),
	(350,'woocommerce_email_background_color','#f7f7f7','no'),
	(351,'woocommerce_email_body_background_color','#ffffff','no'),
	(352,'woocommerce_email_text_color','#3c3c3c','no'),
	(353,'woocommerce_cart_page_id','7','yes'),
	(354,'woocommerce_checkout_page_id','8','yes'),
	(355,'woocommerce_myaccount_page_id','9','yes'),
	(356,'woocommerce_terms_page_id','','no'),
	(357,'woocommerce_checkout_pay_endpoint','order-pay','yes'),
	(358,'woocommerce_checkout_order_received_endpoint','order-received','yes'),
	(359,'woocommerce_myaccount_add_payment_method_endpoint','add-payment-method','yes'),
	(360,'woocommerce_myaccount_delete_payment_method_endpoint','delete-payment-method','yes'),
	(361,'woocommerce_myaccount_set_default_payment_method_endpoint','set-default-payment-method','yes'),
	(362,'woocommerce_myaccount_orders_endpoint','orders','yes'),
	(363,'woocommerce_myaccount_view_order_endpoint','view-order','yes'),
	(364,'woocommerce_myaccount_downloads_endpoint','downloads','yes'),
	(365,'woocommerce_myaccount_edit_account_endpoint','edit-account','yes'),
	(366,'woocommerce_myaccount_edit_address_endpoint','edit-address','yes'),
	(367,'woocommerce_myaccount_payment_methods_endpoint','payment-methods','yes'),
	(368,'woocommerce_myaccount_lost_password_endpoint','lost-password','yes'),
	(369,'woocommerce_logout_endpoint','customer-logout','yes'),
	(370,'woocommerce_api_enabled','no','yes'),
	(371,'woocommerce_single_image_width','600','yes'),
	(372,'woocommerce_thumbnail_image_width','300','yes'),
	(373,'woocommerce_checkout_highlight_required_fields','yes','yes'),
	(374,'woocommerce_demo_store','no','no'),
	(375,'woocommerce_permalinks','a:5:{s:12:\"product_base\";s:7:\"product\";s:13:\"category_base\";s:16:\"product-category\";s:8:\"tag_base\";s:11:\"product-tag\";s:14:\"attribute_base\";s:0:\"\";s:22:\"use_verbose_page_rules\";b:0;}','yes'),
	(376,'current_theme_supports_woocommerce','yes','yes'),
	(377,'woocommerce_queue_flush_rewrite_rules','no','yes'),
	(379,'product_cat_children','a:0:{}','yes'),
	(380,'default_product_cat','15','yes'),
	(385,'woocommerce_admin_notices','a:1:{i:0;s:6:\"update\";}','yes'),
	(388,'_transient_woocommerce_webhook_ids','a:0:{}','yes'),
	(389,'widget_woocommerce_widget_cart','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(390,'widget_woocommerce_layered_nav_filters','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(391,'widget_woocommerce_layered_nav','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(392,'widget_woocommerce_price_filter','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(393,'widget_woocommerce_product_categories','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(394,'widget_woocommerce_product_search','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(395,'widget_woocommerce_product_tag_cloud','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(396,'widget_woocommerce_products','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(397,'widget_woocommerce_recently_viewed_products','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(398,'widget_woocommerce_top_rated_products','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(399,'widget_woocommerce_recent_reviews','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(400,'widget_woocommerce_rating_filter','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(403,'edd_settings','a:4:{s:13:\"purchase_page\";i:2;s:12:\"success_page\";i:3;s:12:\"failure_page\";i:4;s:21:\"purchase_history_page\";i:5;}','yes'),
	(404,'edd_use_php_sessions','1','yes'),
	(405,'edd_version','2.9.21','yes'),
	(408,'edd_default_api_version','v2','yes'),
	(409,'wp_edd_customers_db_version','1.0','yes'),
	(410,'wp_edd_customermeta_db_version','1.0','yes'),
	(413,'edd_completed_upgrades','a:5:{i:0;s:21:\"upgrade_payment_taxes\";i:1;s:37:\"upgrade_customer_payments_association\";i:2;s:21:\"upgrade_user_api_keys\";i:3;s:25:\"remove_refunded_sale_logs\";i:4;s:29:\"update_file_download_log_data\";}','yes'),
	(414,'_transient_edd_cache_excluded_uris','a:4:{i:0;s:3:\"p=2\";i:1;s:3:\"p=3\";i:2;s:9:\"/checkout\";i:3;s:22:\"/purchase-confirmation\";}','yes'),
	(415,'widget_edd_cart_widget','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(416,'widget_edd_categories_tags_widget','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(417,'widget_edd_product_details','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),
	(419,'_transient_wc_count_comments','O:8:\"stdClass\":7:{s:14:\"total_comments\";i:0;s:3:\"all\";i:0;s:9:\"moderated\";i:0;s:8:\"approved\";i:0;s:4:\"spam\";i:0;s:5:\"trash\";i:0;s:12:\"post-trashed\";i:0;}','yes'),
	(420,'_edd_table_check','1584592433','yes'),
	(421,'woocommerce_meta_box_errors','a:0:{}','yes'),
	(422,'woocommerce_product_type','virtual','yes'),
	(423,'woocommerce_allow_tracking','no','yes'),
	(427,'woocommerce_ppec_paypal_settings','a:3:{s:7:\"enabled\";s:3:\"yes\";s:16:\"reroute_requests\";s:3:\"yes\";s:5:\"email\";s:18:\"admin@commerce.dev\";}','yes'),
	(428,'woocommerce_cheque_settings','a:1:{s:7:\"enabled\";s:2:\"no\";}','yes'),
	(429,'woocommerce_bacs_settings','a:1:{s:7:\"enabled\";s:3:\"yes\";}','yes'),
	(430,'woocommerce_cod_settings','a:1:{s:7:\"enabled\";s:3:\"yes\";}','yes'),
	(433,'jetpack_activated','1','yes'),
	(436,'jetpack_activation_source','a:2:{i:0;s:7:\"unknown\";i:1;N;}','yes'),
	(437,'jetpack_sync_settings_disable','0','yes'),
	(440,'jetpack_available_modules','a:1:{s:5:\"6.2.1\";a:43:{s:18:\"after-the-deadline\";s:3:\"1.1\";s:8:\"carousel\";s:3:\"1.5\";s:13:\"comment-likes\";s:3:\"5.1\";s:8:\"comments\";s:3:\"1.4\";s:12:\"contact-form\";s:3:\"1.3\";s:20:\"custom-content-types\";s:3:\"3.1\";s:10:\"custom-css\";s:3:\"1.7\";s:21:\"enhanced-distribution\";s:3:\"1.2\";s:16:\"google-analytics\";s:3:\"4.5\";s:19:\"gravatar-hovercards\";s:3:\"1.1\";s:15:\"infinite-scroll\";s:3:\"2.0\";s:8:\"json-api\";s:3:\"1.9\";s:5:\"latex\";s:3:\"1.1\";s:11:\"lazy-images\";s:5:\"5.6.0\";s:5:\"likes\";s:3:\"2.2\";s:6:\"manage\";s:3:\"3.4\";s:8:\"markdown\";s:3:\"2.8\";s:9:\"masterbar\";s:3:\"4.8\";s:9:\"minileven\";s:3:\"1.8\";s:7:\"monitor\";s:3:\"2.6\";s:5:\"notes\";s:3:\"1.9\";s:6:\"photon\";s:3:\"2.0\";s:13:\"post-by-email\";s:3:\"2.0\";s:7:\"protect\";s:3:\"3.4\";s:9:\"publicize\";s:3:\"2.0\";s:3:\"pwa\";s:5:\"5.6.0\";s:13:\"related-posts\";s:3:\"2.9\";s:6:\"search\";s:3:\"5.0\";s:9:\"seo-tools\";s:3:\"4.4\";s:10:\"sharedaddy\";s:3:\"1.1\";s:10:\"shortcodes\";s:3:\"1.1\";s:10:\"shortlinks\";s:3:\"1.1\";s:8:\"sitemaps\";s:3:\"3.9\";s:3:\"sso\";s:3:\"2.6\";s:5:\"stats\";s:3:\"1.1\";s:13:\"subscriptions\";s:3:\"1.2\";s:13:\"tiled-gallery\";s:3:\"2.1\";s:10:\"vaultpress\";s:5:\"0:1.2\";s:18:\"verification-tools\";s:3:\"3.0\";s:10:\"videopress\";s:3:\"2.5\";s:17:\"widget-visibility\";s:3:\"2.4\";s:7:\"widgets\";s:3:\"1.2\";s:7:\"wordads\";s:5:\"4.5.0\";}}','yes'),
	(441,'jetpack_options','a:2:{s:7:\"version\";s:16:\"6.2.1:1530195071\";s:11:\"old_version\";s:16:\"6.2.1:1530195071\";}','yes'),
	(442,'wc_ppec_version','1.5.6','yes'),
	(443,'external_updates-event-tickets-plus','O:8:\"stdClass\":3:{s:9:\"lastCheck\";i:1583987676;s:14:\"checkedVersion\";s:6:\"4.11.4\";s:6:\"update\";O:19:\"Tribe__PUE__Utility\":14:{s:2:\"id\";i:0;s:6:\"plugin\";N;s:4:\"slug\";N;s:7:\"version\";s:6:\"4.11.3\";s:8:\"homepage\";N;s:12:\"download_url\";N;s:8:\"sections\";O:8:\"stdClass\":1:{s:9:\"changelog\";s:562:\"<p>= [4.11.3] 2020-02-26 =</p>\r\n\r\n<ul>\r\n<li>Fix - The script to check if required Attendee Information exists before purchasing a ticket no longer conflicts with the actual form submission. [ET-686]</li>\r\n<li>Fix - Save initial shared capacity value for global stock correctly on first WooCommerce/Easy Digital Downloads ticket so availability shows as expected instead of zero. [ETP-221]</li>\r\n<li>Fix - Prevent fatal error when deleting WooCommerce tickets. [ETP-229]</li>\r\n<li>Language - 0 new strings added, 21 updated, 0 fuzzied, and 0 obsoleted</li>\r\n</ul>\";}s:14:\"upgrade_notice\";N;s:13:\"custom_update\";N;s:11:\"api_expired\";b:1;s:11:\"api_invalid\";b:1;s:19:\"api_invalid_message\";s:331:\"<p>You are using %plugin_name% but your license key is expired. Visit <a href=\"https://theeventscalendar.com/license-keys/?utm_medium=pue&utm_campaign=in-app&utm_content=expired\">the Events Calendar website</a> to renew your license. You\'ll need to renew your license in order to have access to updates, downloads, and support.</p>\";s:26:\"api_inline_invalid_message\";s:387:\"<p>There is a new version of %plugin_name% available but your license key is expired. <a href=\"https://theeventscalendar.com/license-keys/?utm_medium=pue&utm_campaign=in-app&utm_content=expired-api-key\">Visit The Events Calendar website</a> to renew your license and have access to updates, downloads, and support. Learn what\'s new in the latest release of %plugin_name%. %changelog%</p>\";s:13:\"license_error\";s:566:\"<p>There is a new version of Event Tickets Plus available but your license key is expired. <a href=\"https://theeventscalendar.com/license-keys/?utm_medium=pue&amp;utm_campaign=in-app&amp;utm_content=expired-api-key\">Visit The Events Calendar website</a> to renew your license and have access to updates, downloads, and support. Learn what\'s new in the latest release of Event Tickets Plus. <a class=\"thickbox\" title=\"Event Tickets Plus\" href=\"plugin-install.php?tab=plugin-information&plugin=event-tickets-plus&TB_iframe=true&width=640&height=808\">what\'s new</a></p>\";}}','no'),
	(444,'tribe_pue_key_notices','a:1:{s:11:\"expired_key\";a:1:{s:18:\"Event Tickets Plus\";b:1;}}','yes'),
	(452,'do_activate','0','yes'),
	(457,'edd_tracking_notice','1','yes'),
	(472,'edd_earnings_total','0','yes'),
	(473,'tribe_last_updated_option','1583990062.6263','yes'),
	(474,'tribe_feature_support_check_lock','1','yes'),
	(475,'woocommerce_maxmind_geolocation_settings','a:1:{s:15:\"database_prefix\";s:32:\"P9ElpWZBAHDLizmhWz78Mlp5U85Pl8S0\";}','yes'),
	(476,'_transient_woocommerce_webhook_ids_status_active','a:0:{}','yes'),
	(477,'schema-ActionScheduler_StoreSchema','3.0.1583987608','yes'),
	(478,'schema-ActionScheduler_LoggerSchema','2.0.1583987608','yes'),
	(479,'woocommerce_onboarding_opt_in','no','yes'),
	(482,'woocommerce_placeholder_image','13','yes'),
	(483,'woocommerce_downloads_add_hash_to_filename','yes','yes'),
	(484,'woocommerce_allow_bulk_remove_personal_data','no','no'),
	(485,'woocommerce_force_ssl_checkout','no','yes'),
	(486,'woocommerce_unforce_ssl_checkout','no','yes'),
	(487,'woocommerce_show_marketplace_suggestions','yes','no'),
	(488,'woocommerce_version','4.0.0','yes'),
	(489,'woocommerce_onboarding_profile','a:1:{s:9:\"completed\";b:1;}','yes'),
	(490,'woocommerce_task_list_hidden','yes','yes'),
	(491,'_transient_timeout_as-post-store-dependencies-met','1584074009','no'),
	(492,'_transient_as-post-store-dependencies-met','yes','no'),
	(493,'tribe_last_generate_rewrite_rules','1583989806.9924','yes'),
	(494,'_transient_wc_attribute_taxonomies','a:0:{}','yes'),
	(497,'_transient_timeout_external_ip_address_192.168.95.1','1584592410','no'),
	(498,'_transient_external_ip_address_192.168.95.1','72.182.189.161','no'),
	(499,'fs_active_plugins','O:8:\"stdClass\":3:{s:7:\"plugins\";a:1:{s:36:\"event-tickets/common/vendor/freemius\";O:8:\"stdClass\":4:{s:7:\"version\";s:5:\"2.3.2\";s:4:\"type\";s:6:\"plugin\";s:9:\"timestamp\";i:1583987632;s:11:\"plugin_path\";s:31:\"event-tickets/event-tickets.php\";}}s:7:\"abspath\";s:12:\"/app/public/\";s:6:\"newest\";O:8:\"stdClass\":5:{s:11:\"plugin_path\";s:31:\"event-tickets/event-tickets.php\";s:8:\"sdk_path\";s:36:\"event-tickets/common/vendor/freemius\";s:7:\"version\";s:5:\"2.3.2\";s:13:\"in_activation\";b:0;s:9:\"timestamp\";i:1583987632;}}','yes'),
	(500,'fs_debug_mode','','yes'),
	(501,'fs_accounts','a:6:{s:21:\"id_slug_type_path_map\";a:2:{i:3841;a:3:{s:4:\"slug\";s:13:\"event-tickets\";s:4:\"type\";s:6:\"plugin\";s:4:\"path\";s:31:\"event-tickets/event-tickets.php\";}i:3069;a:3:{s:4:\"slug\";s:19:\"the-events-calendar\";s:4:\"type\";s:6:\"plugin\";s:4:\"path\";s:43:\"the-events-calendar/the-events-calendar.php\";}}s:11:\"plugin_data\";a:2:{s:13:\"event-tickets\";a:17:{s:16:\"plugin_main_file\";O:8:\"stdClass\":1:{s:4:\"path\";s:31:\"event-tickets/event-tickets.php\";}s:20:\"is_network_activated\";b:0;s:17:\"install_timestamp\";i:1583987632;s:17:\"was_plugin_loaded\";b:1;s:21:\"is_plugin_new_install\";b:0;s:16:\"sdk_last_version\";N;s:11:\"sdk_version\";s:5:\"2.3.2\";s:16:\"sdk_upgrade_mode\";b:1;s:18:\"sdk_downgrade_mode\";b:0;s:19:\"plugin_last_version\";N;s:14:\"plugin_version\";s:6:\"4.11.5\";s:19:\"plugin_upgrade_mode\";b:1;s:21:\"plugin_downgrade_mode\";b:0;s:17:\"connectivity_test\";a:6:{s:12:\"is_connected\";b:1;s:4:\"host\";s:14:\"test.tribe.dev\";s:9:\"server_ip\";s:12:\"192.168.95.1\";s:9:\"is_active\";b:1;s:9:\"timestamp\";i:1583987632;s:7:\"version\";s:6:\"4.11.5\";}s:15:\"prev_is_premium\";b:0;s:18:\"sticky_optin_added\";b:1;s:12:\"is_anonymous\";a:3:{s:2:\"is\";b:1;s:9:\"timestamp\";i:1583989929;s:7:\"version\";s:6:\"4.11.5\";}}s:19:\"the-events-calendar\";a:17:{s:16:\"plugin_main_file\";O:8:\"stdClass\":1:{s:4:\"path\";s:43:\"the-events-calendar/the-events-calendar.php\";}s:20:\"is_network_activated\";b:0;s:17:\"install_timestamp\";i:1583987632;s:17:\"was_plugin_loaded\";b:1;s:21:\"is_plugin_new_install\";b:0;s:16:\"sdk_last_version\";N;s:11:\"sdk_version\";s:5:\"2.3.2\";s:16:\"sdk_upgrade_mode\";b:1;s:18:\"sdk_downgrade_mode\";b:0;s:19:\"plugin_last_version\";N;s:14:\"plugin_version\";s:7:\"5.0.2.1\";s:19:\"plugin_upgrade_mode\";b:1;s:21:\"plugin_downgrade_mode\";b:0;s:17:\"connectivity_test\";a:6:{s:12:\"is_connected\";b:1;s:4:\"host\";s:14:\"test.tribe.dev\";s:9:\"server_ip\";s:12:\"192.168.95.1\";s:9:\"is_active\";b:1;s:9:\"timestamp\";i:1583987632;s:7:\"version\";s:7:\"5.0.2.1\";}s:15:\"prev_is_premium\";b:0;s:18:\"sticky_optin_added\";b:1;s:12:\"is_anonymous\";a:3:{s:2:\"is\";b:1;s:9:\"timestamp\";i:1583989926;s:7:\"version\";s:7:\"5.0.2.1\";}}}s:13:\"file_slug_map\";a:2:{s:31:\"event-tickets/event-tickets.php\";s:13:\"event-tickets\";s:43:\"the-events-calendar/the-events-calendar.php\";s:19:\"the-events-calendar\";}s:7:\"plugins\";a:2:{s:13:\"event-tickets\";O:9:\"FS_Plugin\":23:{s:16:\"parent_plugin_id\";N;s:5:\"title\";s:13:\"Event Tickets\";s:4:\"slug\";s:13:\"event-tickets\";s:12:\"premium_slug\";s:21:\"event-tickets-premium\";s:4:\"type\";s:6:\"plugin\";s:20:\"affiliate_moderation\";b:0;s:19:\"is_wp_org_compliant\";b:1;s:22:\"premium_releases_count\";N;s:4:\"file\";s:31:\"event-tickets/event-tickets.php\";s:7:\"version\";s:6:\"4.11.5\";s:11:\"auto_update\";N;s:4:\"info\";N;s:10:\"is_premium\";b:0;s:14:\"premium_suffix\";s:9:\"(Premium)\";s:7:\"is_live\";b:1;s:9:\"bundle_id\";N;s:17:\"bundle_public_key\";N;s:10:\"public_key\";s:32:\"pk_6dd9310b57c62871c59e58b8e739e\";s:10:\"secret_key\";N;s:2:\"id\";s:4:\"3841\";s:7:\"updated\";N;s:7:\"created\";N;s:22:\"\0FS_Entity\0_is_updated\";b:0;}s:19:\"the-events-calendar\";O:9:\"FS_Plugin\":23:{s:16:\"parent_plugin_id\";N;s:5:\"title\";s:19:\"The Events Calendar\";s:4:\"slug\";s:19:\"the-events-calendar\";s:12:\"premium_slug\";s:27:\"the-events-calendar-premium\";s:4:\"type\";s:6:\"plugin\";s:20:\"affiliate_moderation\";b:0;s:19:\"is_wp_org_compliant\";b:1;s:22:\"premium_releases_count\";N;s:4:\"file\";s:43:\"the-events-calendar/the-events-calendar.php\";s:7:\"version\";s:7:\"5.0.2.1\";s:11:\"auto_update\";N;s:4:\"info\";N;s:10:\"is_premium\";b:0;s:14:\"premium_suffix\";s:9:\"(Premium)\";s:7:\"is_live\";b:1;s:9:\"bundle_id\";N;s:17:\"bundle_public_key\";N;s:10:\"public_key\";s:32:\"pk_e32061abc28cfedf231f3e5c4e626\";s:10:\"secret_key\";N;s:2:\"id\";s:4:\"3069\";s:7:\"updated\";N;s:7:\"created\";N;s:22:\"\0FS_Entity\0_is_updated\";b:0;}}s:9:\"unique_id\";s:32:\"bf3bc79b49d8bb633232c26db0e6a4f1\";s:13:\"admin_notices\";a:2:{s:13:\"event-tickets\";a:0:{}s:19:\"the-events-calendar\";a:0:{}}}','yes'),
	(502,'fs_gdpr','a:2:{s:2:\"u0\";a:1:{s:8:\"required\";b:0;}s:2:\"u1\";a:1:{s:8:\"required\";b:0;}}','yes'),
	(505,'woocommerce_admin_version','1.0.0','yes'),
	(506,'woocommerce_admin_install_timestamp','1583987633','yes'),
	(507,'_transient_timeout_edd_check_protection_files','1584074033','no'),
	(508,'_transient_edd_check_protection_files','1','no'),
	(509,'action_scheduler_lock_async-request-runner','1583989997','yes'),
	(510,'_transient_timeout_external_ip_address_172.18.0.4','1584592434','no'),
	(511,'_transient_external_ip_address_172.18.0.4','72.182.189.161','no'),
	(518,'_transient_timeout__woocommerce_helper_updates','1584030874','no'),
	(519,'_transient__woocommerce_helper_updates','a:4:{s:4:\"hash\";s:32:\"d751713988987e9331980363e24189ce\";s:7:\"updated\";i:1583987674;s:8:\"products\";a:0:{}s:6:\"errors\";a:1:{i:0;s:10:\"http-error\";}}','no'),
	(520,'_site_transient_update_plugins','O:8:\"stdClass\":5:{s:12:\"last_checked\";i:1583987675;s:7:\"checked\";a:32:{s:35:\"advanced-post-manager/tribe-apm.php\";s:3:\"4.5\";s:33:\"classic-editor/classic-editor.php\";s:3:\"1.5\";s:49:\"easy-digital-downloads/easy-digital-downloads.php\";s:6:\"2.9.21\";s:31:\"event-tickets/event-tickets.php\";s:6:\"4.11.5\";s:40:\"event-tickets-ajax-400/event-tickets.php\";s:6:\"4.11.1\";s:47:\"tribe-ext-pdf-tickets/tribe-ext-pdf-tickets.php\";s:5:\"1.2.0\";s:41:\"event-tickets-plus/event-tickets-plus.php\";s:6:\"4.11.4\";s:50:\"event-tickets-plus-ajax-400/event-tickets-plus.php\";s:6:\"4.11.1\";s:43:\"exports-and-reports/exports-and-reports.php\";s:5:\"0.8.5\";s:21:\"gigpress/gigpress.php\";s:6:\"2.3.23\";s:33:\"github-updater/github-updater.php\";s:5:\"8.7.2\";s:29:\"image-widget/image-widget.php\";s:5:\"4.4.7\";s:39:\"image-widget-plus/image-widget-plus.php\";s:5:\"1.0.3\";s:48:\"php-compatibility-checker/wpengine-phpcompat.php\";s:5:\"1.5.0\";s:31:\"query-monitor/query-monitor.php\";s:5:\"3.5.2\";s:51:\"rewrite-rules-inspector/rewrite-rules-inspector.php\";s:5:\"1.2.1\";s:50:\"the-events-calendar-stable/the-events-calendar.php\";s:6:\"4.9.14\";s:52:\"the-events-calendar-ajax-400/the-events-calendar.php\";s:6:\"4.9.13\";s:43:\"the-events-calendar/the-events-calendar.php\";s:7:\"5.0.2.1\";s:43:\"events-community/tribe-community-events.php\";s:5:\"4.6.7\";s:53:\"events-community-tickets/events-community-tickets.php\";s:5:\"4.7.2\";s:38:\"events-eventbrite/tribe-eventbrite.php\";s:3:\"4.6\";s:52:\"events-filterbar/the-events-calendar-filter-view.php\";s:3:\"4.6\";s:34:\"events-pro/events-calendar-pro.php\";s:5:\"5.0.1\";s:52:\"events-calendar-pro-ajax-400/events-calendar-pro.php\";s:6:\"4.7.10\";s:55:\"tk-exclude-vcs-updates-1.0.0/tk-exclude-vcs-updates.php\";s:5:\"1.0.0\";s:41:\"transients-manager/transients-manager.php\";s:3:\"1.8\";s:23:\"tribe-cli/tribe-cli.php\";s:5:\"0.2.5\";s:29:\"tribe-common/tribe-common.php\";s:6:\"4.11.4\";s:49:\"tribe-product-qa-tools/tribe-product-qa-tools.php\";s:3:\"0.1\";s:27:\"woocommerce/woocommerce.php\";s:5:\"4.0.0\";s:27:\"wp-crontrol/wp-crontrol.php\";s:5:\"1.7.1\";}s:8:\"response\";a:3:{s:40:\"event-tickets-ajax-400/event-tickets.php\";O:8:\"stdClass\":12:{s:2:\"id\";s:27:\"w.org/plugins/event-tickets\";s:4:\"slug\";s:13:\"event-tickets\";s:6:\"plugin\";s:40:\"event-tickets-ajax-400/event-tickets.php\";s:11:\"new_version\";s:6:\"4.11.4\";s:3:\"url\";s:44:\"https://wordpress.org/plugins/event-tickets/\";s:7:\"package\";s:63:\"https://downloads.wordpress.org/plugin/event-tickets.4.11.4.zip\";s:5:\"icons\";a:3:{s:2:\"2x\";s:66:\"https://ps.w.org/event-tickets/assets/icon-256x256.png?rev=2259359\";s:2:\"1x\";s:58:\"https://ps.w.org/event-tickets/assets/icon.svg?rev=2259340\";s:3:\"svg\";s:58:\"https://ps.w.org/event-tickets/assets/icon.svg?rev=2259340\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:69:\"https://ps.w.org/event-tickets/assets/banner-1544x500.png?rev=2257626\";s:2:\"1x\";s:68:\"https://ps.w.org/event-tickets/assets/banner-772x250.png?rev=2257626\";}s:11:\"banners_rtl\";a:0:{}s:6:\"tested\";s:5:\"5.3.2\";s:12:\"requires_php\";s:3:\"5.6\";s:13:\"compatibility\";O:8:\"stdClass\":0:{}}s:50:\"the-events-calendar-stable/the-events-calendar.php\";O:8:\"stdClass\":12:{s:2:\"id\";s:33:\"w.org/plugins/the-events-calendar\";s:4:\"slug\";s:19:\"the-events-calendar\";s:6:\"plugin\";s:50:\"the-events-calendar-stable/the-events-calendar.php\";s:11:\"new_version\";s:7:\"5.0.2.1\";s:3:\"url\";s:50:\"https://wordpress.org/plugins/the-events-calendar/\";s:7:\"package\";s:70:\"https://downloads.wordpress.org/plugin/the-events-calendar.5.0.2.1.zip\";s:5:\"icons\";a:3:{s:2:\"2x\";s:72:\"https://ps.w.org/the-events-calendar/assets/icon-256x256.png?rev=2259358\";s:2:\"1x\";s:64:\"https://ps.w.org/the-events-calendar/assets/icon.svg?rev=2259343\";s:3:\"svg\";s:64:\"https://ps.w.org/the-events-calendar/assets/icon.svg?rev=2259343\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:75:\"https://ps.w.org/the-events-calendar/assets/banner-1544x500.png?rev=2257622\";s:2:\"1x\";s:74:\"https://ps.w.org/the-events-calendar/assets/banner-772x250.png?rev=2257622\";}s:11:\"banners_rtl\";a:0:{}s:6:\"tested\";s:5:\"5.3.2\";s:12:\"requires_php\";s:3:\"5.6\";s:13:\"compatibility\";O:8:\"stdClass\":0:{}}s:52:\"the-events-calendar-ajax-400/the-events-calendar.php\";O:8:\"stdClass\":12:{s:2:\"id\";s:33:\"w.org/plugins/the-events-calendar\";s:4:\"slug\";s:19:\"the-events-calendar\";s:6:\"plugin\";s:52:\"the-events-calendar-ajax-400/the-events-calendar.php\";s:11:\"new_version\";s:7:\"5.0.2.1\";s:3:\"url\";s:50:\"https://wordpress.org/plugins/the-events-calendar/\";s:7:\"package\";s:70:\"https://downloads.wordpress.org/plugin/the-events-calendar.5.0.2.1.zip\";s:5:\"icons\";a:3:{s:2:\"2x\";s:72:\"https://ps.w.org/the-events-calendar/assets/icon-256x256.png?rev=2259358\";s:2:\"1x\";s:64:\"https://ps.w.org/the-events-calendar/assets/icon.svg?rev=2259343\";s:3:\"svg\";s:64:\"https://ps.w.org/the-events-calendar/assets/icon.svg?rev=2259343\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:75:\"https://ps.w.org/the-events-calendar/assets/banner-1544x500.png?rev=2257622\";s:2:\"1x\";s:74:\"https://ps.w.org/the-events-calendar/assets/banner-772x250.png?rev=2257622\";}s:11:\"banners_rtl\";a:0:{}s:6:\"tested\";s:5:\"5.3.2\";s:12:\"requires_php\";s:3:\"5.6\";s:13:\"compatibility\";O:8:\"stdClass\":0:{}}}s:12:\"translations\";a:0:{}s:9:\"no_update\";a:14:{s:35:\"advanced-post-manager/tribe-apm.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:35:\"w.org/plugins/advanced-post-manager\";s:4:\"slug\";s:21:\"advanced-post-manager\";s:6:\"plugin\";s:35:\"advanced-post-manager/tribe-apm.php\";s:11:\"new_version\";s:3:\"4.5\";s:3:\"url\";s:52:\"https://wordpress.org/plugins/advanced-post-manager/\";s:7:\"package\";s:70:\"https://downloads.wordpress.org/plugin/advanced-post-manager.4.5.0.zip\";s:5:\"icons\";a:1:{s:7:\"default\";s:72:\"https://s.w.org/plugins/geopattern-icon/advanced-post-manager_66b8d2.svg\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:76:\"https://ps.w.org/advanced-post-manager/assets/banner-1544x500.jpg?rev=593014\";s:2:\"1x\";s:75:\"https://ps.w.org/advanced-post-manager/assets/banner-772x250.png?rev=517740\";}s:11:\"banners_rtl\";a:0:{}}s:33:\"classic-editor/classic-editor.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:28:\"w.org/plugins/classic-editor\";s:4:\"slug\";s:14:\"classic-editor\";s:6:\"plugin\";s:33:\"classic-editor/classic-editor.php\";s:11:\"new_version\";s:3:\"1.5\";s:3:\"url\";s:45:\"https://wordpress.org/plugins/classic-editor/\";s:7:\"package\";s:61:\"https://downloads.wordpress.org/plugin/classic-editor.1.5.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:67:\"https://ps.w.org/classic-editor/assets/icon-256x256.png?rev=1998671\";s:2:\"1x\";s:67:\"https://ps.w.org/classic-editor/assets/icon-128x128.png?rev=1998671\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:70:\"https://ps.w.org/classic-editor/assets/banner-1544x500.png?rev=1998671\";s:2:\"1x\";s:69:\"https://ps.w.org/classic-editor/assets/banner-772x250.png?rev=1998676\";}s:11:\"banners_rtl\";a:0:{}}s:49:\"easy-digital-downloads/easy-digital-downloads.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:36:\"w.org/plugins/easy-digital-downloads\";s:4:\"slug\";s:22:\"easy-digital-downloads\";s:6:\"plugin\";s:49:\"easy-digital-downloads/easy-digital-downloads.php\";s:11:\"new_version\";s:6:\"2.9.21\";s:3:\"url\";s:53:\"https://wordpress.org/plugins/easy-digital-downloads/\";s:7:\"package\";s:72:\"https://downloads.wordpress.org/plugin/easy-digital-downloads.2.9.21.zip\";s:5:\"icons\";a:3:{s:2:\"2x\";s:74:\"https://ps.w.org/easy-digital-downloads/assets/icon-256x256.png?rev=971967\";s:2:\"1x\";s:66:\"https://ps.w.org/easy-digital-downloads/assets/icon.svg?rev=971968\";s:3:\"svg\";s:66:\"https://ps.w.org/easy-digital-downloads/assets/icon.svg?rev=971968\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:78:\"https://ps.w.org/easy-digital-downloads/assets/banner-1544x500.png?rev=1728279\";s:2:\"1x\";s:77:\"https://ps.w.org/easy-digital-downloads/assets/banner-772x250.png?rev=1728282\";}s:11:\"banners_rtl\";a:0:{}}s:31:\"event-tickets/event-tickets.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:27:\"w.org/plugins/event-tickets\";s:4:\"slug\";s:13:\"event-tickets\";s:6:\"plugin\";s:31:\"event-tickets/event-tickets.php\";s:11:\"new_version\";s:6:\"4.11.4\";s:3:\"url\";s:44:\"https://wordpress.org/plugins/event-tickets/\";s:7:\"package\";s:63:\"https://downloads.wordpress.org/plugin/event-tickets.4.11.4.zip\";s:5:\"icons\";a:3:{s:2:\"2x\";s:66:\"https://ps.w.org/event-tickets/assets/icon-256x256.png?rev=2259359\";s:2:\"1x\";s:58:\"https://ps.w.org/event-tickets/assets/icon.svg?rev=2259340\";s:3:\"svg\";s:58:\"https://ps.w.org/event-tickets/assets/icon.svg?rev=2259340\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:69:\"https://ps.w.org/event-tickets/assets/banner-1544x500.png?rev=2257626\";s:2:\"1x\";s:68:\"https://ps.w.org/event-tickets/assets/banner-772x250.png?rev=2257626\";}s:11:\"banners_rtl\";a:0:{}}s:43:\"exports-and-reports/exports-and-reports.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:33:\"w.org/plugins/exports-and-reports\";s:4:\"slug\";s:19:\"exports-and-reports\";s:6:\"plugin\";s:43:\"exports-and-reports/exports-and-reports.php\";s:11:\"new_version\";s:5:\"0.8.5\";s:3:\"url\";s:50:\"https://wordpress.org/plugins/exports-and-reports/\";s:7:\"package\";s:68:\"https://downloads.wordpress.org/plugin/exports-and-reports.0.8.5.zip\";s:5:\"icons\";a:1:{s:7:\"default\";s:63:\"https://s.w.org/plugins/geopattern-icon/exports-and-reports.svg\";}s:7:\"banners\";a:0:{}s:11:\"banners_rtl\";a:0:{}}s:21:\"gigpress/gigpress.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:22:\"w.org/plugins/gigpress\";s:4:\"slug\";s:8:\"gigpress\";s:6:\"plugin\";s:21:\"gigpress/gigpress.php\";s:11:\"new_version\";s:6:\"2.3.23\";s:3:\"url\";s:39:\"https://wordpress.org/plugins/gigpress/\";s:7:\"package\";s:58:\"https://downloads.wordpress.org/plugin/gigpress.2.3.23.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:60:\"https://ps.w.org/gigpress/assets/icon-256x256.jpg?rev=979213\";s:2:\"1x\";s:60:\"https://ps.w.org/gigpress/assets/icon-128x128.jpg?rev=979213\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:63:\"https://ps.w.org/gigpress/assets/banner-1544x500.jpg?rev=979213\";s:2:\"1x\";s:62:\"https://ps.w.org/gigpress/assets/banner-772x250.jpg?rev=979213\";}s:11:\"banners_rtl\";a:0:{}}s:29:\"image-widget/image-widget.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:26:\"w.org/plugins/image-widget\";s:4:\"slug\";s:12:\"image-widget\";s:6:\"plugin\";s:29:\"image-widget/image-widget.php\";s:11:\"new_version\";s:5:\"4.4.7\";s:3:\"url\";s:43:\"https://wordpress.org/plugins/image-widget/\";s:7:\"package\";s:61:\"https://downloads.wordpress.org/plugin/image-widget.4.4.7.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:64:\"https://ps.w.org/image-widget/assets/icon-256x256.jpg?rev=985707\";s:2:\"1x\";s:64:\"https://ps.w.org/image-widget/assets/icon-128x128.jpg?rev=985707\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:67:\"https://ps.w.org/image-widget/assets/banner-1544x500.jpg?rev=593018\";s:2:\"1x\";s:66:\"https://ps.w.org/image-widget/assets/banner-772x250.png?rev=517739\";}s:11:\"banners_rtl\";a:0:{}}s:48:\"php-compatibility-checker/wpengine-phpcompat.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:39:\"w.org/plugins/php-compatibility-checker\";s:4:\"slug\";s:25:\"php-compatibility-checker\";s:6:\"plugin\";s:48:\"php-compatibility-checker/wpengine-phpcompat.php\";s:11:\"new_version\";s:5:\"1.5.0\";s:3:\"url\";s:56:\"https://wordpress.org/plugins/php-compatibility-checker/\";s:7:\"package\";s:74:\"https://downloads.wordpress.org/plugin/php-compatibility-checker.1.5.0.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:78:\"https://ps.w.org/php-compatibility-checker/assets/icon-256x256.png?rev=1446087\";s:2:\"1x\";s:78:\"https://ps.w.org/php-compatibility-checker/assets/icon-128x128.png?rev=1446087\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:81:\"https://ps.w.org/php-compatibility-checker/assets/banner-1544x500.png?rev=1446087\";s:2:\"1x\";s:80:\"https://ps.w.org/php-compatibility-checker/assets/banner-772x250.png?rev=1446087\";}s:11:\"banners_rtl\";a:0:{}}s:31:\"query-monitor/query-monitor.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:27:\"w.org/plugins/query-monitor\";s:4:\"slug\";s:13:\"query-monitor\";s:6:\"plugin\";s:31:\"query-monitor/query-monitor.php\";s:11:\"new_version\";s:5:\"3.5.2\";s:3:\"url\";s:44:\"https://wordpress.org/plugins/query-monitor/\";s:7:\"package\";s:62:\"https://downloads.wordpress.org/plugin/query-monitor.3.5.2.zip\";s:5:\"icons\";a:3:{s:2:\"2x\";s:66:\"https://ps.w.org/query-monitor/assets/icon-256x256.png?rev=2056073\";s:2:\"1x\";s:58:\"https://ps.w.org/query-monitor/assets/icon.svg?rev=2056073\";s:3:\"svg\";s:58:\"https://ps.w.org/query-monitor/assets/icon.svg?rev=2056073\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:69:\"https://ps.w.org/query-monitor/assets/banner-1544x500.png?rev=1629576\";s:2:\"1x\";s:68:\"https://ps.w.org/query-monitor/assets/banner-772x250.png?rev=1731469\";}s:11:\"banners_rtl\";a:0:{}}s:51:\"rewrite-rules-inspector/rewrite-rules-inspector.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:37:\"w.org/plugins/rewrite-rules-inspector\";s:4:\"slug\";s:23:\"rewrite-rules-inspector\";s:6:\"plugin\";s:51:\"rewrite-rules-inspector/rewrite-rules-inspector.php\";s:11:\"new_version\";s:5:\"1.2.1\";s:3:\"url\";s:54:\"https://wordpress.org/plugins/rewrite-rules-inspector/\";s:7:\"package\";s:72:\"https://downloads.wordpress.org/plugin/rewrite-rules-inspector.1.2.1.zip\";s:5:\"icons\";a:1:{s:7:\"default\";s:74:\"https://s.w.org/plugins/geopattern-icon/rewrite-rules-inspector_c6d9dd.svg\";}s:7:\"banners\";a:1:{s:2:\"1x\";s:77:\"https://ps.w.org/rewrite-rules-inspector/assets/banner-772x250.jpg?rev=542084\";}s:11:\"banners_rtl\";a:0:{}}s:43:\"the-events-calendar/the-events-calendar.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:33:\"w.org/plugins/the-events-calendar\";s:4:\"slug\";s:19:\"the-events-calendar\";s:6:\"plugin\";s:43:\"the-events-calendar/the-events-calendar.php\";s:11:\"new_version\";s:7:\"5.0.2.1\";s:3:\"url\";s:50:\"https://wordpress.org/plugins/the-events-calendar/\";s:7:\"package\";s:70:\"https://downloads.wordpress.org/plugin/the-events-calendar.5.0.2.1.zip\";s:5:\"icons\";a:3:{s:2:\"2x\";s:72:\"https://ps.w.org/the-events-calendar/assets/icon-256x256.png?rev=2259358\";s:2:\"1x\";s:64:\"https://ps.w.org/the-events-calendar/assets/icon.svg?rev=2259343\";s:3:\"svg\";s:64:\"https://ps.w.org/the-events-calendar/assets/icon.svg?rev=2259343\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:75:\"https://ps.w.org/the-events-calendar/assets/banner-1544x500.png?rev=2257622\";s:2:\"1x\";s:74:\"https://ps.w.org/the-events-calendar/assets/banner-772x250.png?rev=2257622\";}s:11:\"banners_rtl\";a:0:{}}s:41:\"transients-manager/transients-manager.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:32:\"w.org/plugins/transients-manager\";s:4:\"slug\";s:18:\"transients-manager\";s:6:\"plugin\";s:41:\"transients-manager/transients-manager.php\";s:11:\"new_version\";s:3:\"1.8\";s:3:\"url\";s:49:\"https://wordpress.org/plugins/transients-manager/\";s:7:\"package\";s:61:\"https://downloads.wordpress.org/plugin/transients-manager.zip\";s:5:\"icons\";a:3:{s:2:\"2x\";s:71:\"https://ps.w.org/transients-manager/assets/icon-256x256.png?rev=1671074\";s:2:\"1x\";s:63:\"https://ps.w.org/transients-manager/assets/icon.svg?rev=1671074\";s:3:\"svg\";s:63:\"https://ps.w.org/transients-manager/assets/icon.svg?rev=1671074\";}s:7:\"banners\";a:1:{s:2:\"1x\";s:73:\"https://ps.w.org/transients-manager/assets/banner-772x250.png?rev=1671074\";}s:11:\"banners_rtl\";a:0:{}}s:27:\"woocommerce/woocommerce.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:25:\"w.org/plugins/woocommerce\";s:4:\"slug\";s:11:\"woocommerce\";s:6:\"plugin\";s:27:\"woocommerce/woocommerce.php\";s:11:\"new_version\";s:5:\"4.0.0\";s:3:\"url\";s:42:\"https://wordpress.org/plugins/woocommerce/\";s:7:\"package\";s:60:\"https://downloads.wordpress.org/plugin/woocommerce.4.0.0.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:64:\"https://ps.w.org/woocommerce/assets/icon-256x256.png?rev=2075035\";s:2:\"1x\";s:64:\"https://ps.w.org/woocommerce/assets/icon-128x128.png?rev=2075035\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:67:\"https://ps.w.org/woocommerce/assets/banner-1544x500.png?rev=2075035\";s:2:\"1x\";s:66:\"https://ps.w.org/woocommerce/assets/banner-772x250.png?rev=2075035\";}s:11:\"banners_rtl\";a:0:{}}s:27:\"wp-crontrol/wp-crontrol.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:25:\"w.org/plugins/wp-crontrol\";s:4:\"slug\";s:11:\"wp-crontrol\";s:6:\"plugin\";s:27:\"wp-crontrol/wp-crontrol.php\";s:11:\"new_version\";s:5:\"1.7.1\";s:3:\"url\";s:42:\"https://wordpress.org/plugins/wp-crontrol/\";s:7:\"package\";s:60:\"https://downloads.wordpress.org/plugin/wp-crontrol.1.7.1.zip\";s:5:\"icons\";a:3:{s:2:\"2x\";s:64:\"https://ps.w.org/wp-crontrol/assets/icon-256x256.png?rev=2202246\";s:2:\"1x\";s:56:\"https://ps.w.org/wp-crontrol/assets/icon.svg?rev=2202246\";s:3:\"svg\";s:56:\"https://ps.w.org/wp-crontrol/assets/icon.svg?rev=2202246\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:67:\"https://ps.w.org/wp-crontrol/assets/banner-1544x500.jpg?rev=2214619\";s:2:\"1x\";s:66:\"https://ps.w.org/wp-crontrol/assets/banner-772x250.jpg?rev=2214619\";}s:11:\"banners_rtl\";a:0:{}}}}','no'),
	(521,'recovery_keys','a:0:{}','yes'),
	(522,'woocommerce_admin_last_orders_milestone','0','yes'),
	(562,'show_comments_cookies_opt_in','1','yes'),
	(563,'admin_email_lifespan','1599541805','yes'),
	(564,'db_upgraded','','yes'),
	(565,'_site_transient_update_core','O:8:\"stdClass\":4:{s:7:\"updates\";a:1:{i:0;O:8:\"stdClass\":10:{s:8:\"response\";s:6:\"latest\";s:8:\"download\";s:59:\"https://downloads.wordpress.org/release/wordpress-5.3.2.zip\";s:6:\"locale\";s:5:\"en_US\";s:8:\"packages\";O:8:\"stdClass\":5:{s:4:\"full\";s:59:\"https://downloads.wordpress.org/release/wordpress-5.3.2.zip\";s:10:\"no_content\";s:70:\"https://downloads.wordpress.org/release/wordpress-5.3.2-no-content.zip\";s:11:\"new_bundled\";s:71:\"https://downloads.wordpress.org/release/wordpress-5.3.2-new-bundled.zip\";s:7:\"partial\";b:0;s:8:\"rollback\";b:0;}s:7:\"current\";s:5:\"5.3.2\";s:7:\"version\";s:5:\"5.3.2\";s:11:\"php_version\";s:6:\"5.6.20\";s:13:\"mysql_version\";s:3:\"5.0\";s:11:\"new_bundled\";s:3:\"5.3\";s:15:\"partial_version\";s:0:\"\";}}s:12:\"last_checked\";i:1583989807;s:15:\"version_checked\";s:5:\"5.3.2\";s:12:\"translations\";a:0:{}}','no'),
	(566,'_transient_timeout_plugin_slugs','1584076462','no'),
	(567,'_transient_plugin_slugs','a:32:{i:0;s:35:\"advanced-post-manager/tribe-apm.php\";i:1;s:33:\"classic-editor/classic-editor.php\";i:2;s:49:\"easy-digital-downloads/easy-digital-downloads.php\";i:3;s:31:\"event-tickets/event-tickets.php\";i:4;s:40:\"event-tickets-ajax-400/event-tickets.php\";i:5;s:47:\"tribe-ext-pdf-tickets/tribe-ext-pdf-tickets.php\";i:6;s:41:\"event-tickets-plus/event-tickets-plus.php\";i:7;s:50:\"event-tickets-plus-ajax-400/event-tickets-plus.php\";i:8;s:43:\"exports-and-reports/exports-and-reports.php\";i:9;s:21:\"gigpress/gigpress.php\";i:10;s:33:\"github-updater/github-updater.php\";i:11;s:29:\"image-widget/image-widget.php\";i:12;s:39:\"image-widget-plus/image-widget-plus.php\";i:13;s:48:\"php-compatibility-checker/wpengine-phpcompat.php\";i:14;s:31:\"query-monitor/query-monitor.php\";i:15;s:51:\"rewrite-rules-inspector/rewrite-rules-inspector.php\";i:16;s:50:\"the-events-calendar-stable/the-events-calendar.php\";i:17;s:52:\"the-events-calendar-ajax-400/the-events-calendar.php\";i:18;s:43:\"the-events-calendar/the-events-calendar.php\";i:19;s:43:\"events-community/tribe-community-events.php\";i:20;s:53:\"events-community-tickets/events-community-tickets.php\";i:21;s:38:\"events-eventbrite/tribe-eventbrite.php\";i:22;s:52:\"events-filterbar/the-events-calendar-filter-view.php\";i:23;s:34:\"events-pro/events-calendar-pro.php\";i:24;s:52:\"events-calendar-pro-ajax-400/events-calendar-pro.php\";i:25;s:55:\"tk-exclude-vcs-updates-1.0.0/tk-exclude-vcs-updates.php\";i:26;s:41:\"transients-manager/transients-manager.php\";i:27;s:23:\"tribe-cli/tribe-cli.php\";i:28;s:29:\"tribe-common/tribe-common.php\";i:29;s:49:\"tribe-product-qa-tools/tribe-product-qa-tools.php\";i:30;s:27:\"woocommerce/woocommerce.php\";i:31;s:27:\"wp-crontrol/wp-crontrol.php\";}','no'),
	(568,'recently_activated','a:3:{s:41:\"event-tickets-plus/event-tickets-plus.php\";i:1583990062;s:27:\"woocommerce/woocommerce.php\";i:1583989974;s:49:\"easy-digital-downloads/easy-digital-downloads.php\";i:1583989967;}','yes'),
	(569,'_transient_timeout_tribe_aggregator_services_list','1584076207','no'),
	(570,'_transient_tribe_aggregator_services_list','a:1:{s:6:\"origin\";a:1:{i:0;O:8:\"stdClass\":2:{s:2:\"id\";s:3:\"csv\";s:4:\"name\";s:8:\"CSV File\";}}}','no'),
	(571,'can_compress_scripts','1','no'),
	(583,'_transient_timeout_woocommerce_admin_low_out_of_stock_count','1583993478','no'),
	(584,'_transient_woocommerce_admin_low_out_of_stock_count','0','no'),
	(587,'_transient_timeout_woocommerce_test_remote_post','1583993513','no'),
	(588,'_transient_woocommerce_test_remote_post','200','no'),
	(589,'_transient_timeout_woocommerce_test_remote_get','1583993514','no'),
	(590,'_transient_woocommerce_test_remote_get','200','no'),
	(591,'_transient_timeout_woocommerce_system_status_wp_version_check','1584076314','no'),
	(592,'_transient_woocommerce_system_status_wp_version_check','5.3.2','no'),
	(595,'woocommerce_db_version','4.0.0','yes');

/*!40000 ALTER TABLE `wp_options` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table wp_postmeta
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_postmeta`;

CREATE TABLE `wp_postmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`meta_id`),
  KEY `post_id` (`post_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

LOCK TABLES `wp_postmeta` WRITE;
/*!40000 ALTER TABLE `wp_postmeta` DISABLE KEYS */;

INSERT INTO `wp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`)
VALUES
	(9,12,'_tribe_eddticket_for_event','10'),
	(10,12,'ticket_price','1'),
	(11,12,'edd_price','1.00'),
	(12,12,'_stock','100'),
	(13,12,'_tribe_ticket_capacity','100'),
	(14,12,'_tribe_ticket_version','4.11.5'),
	(15,12,'_manage_stock','yes'),
	(16,12,'_ticket_start_date','2020-03-11 04:33:28'),
	(17,12,'_ticket_end_date','2020-03-13 04:33:28'),
	(18,12,'_global_stock_mode','own'),
	(19,10,'_tribe_modified_fields','a:1:{s:30:\"_tribe_default_ticket_provider\";i:1583987608;}'),
	(20,10,'_tribe_default_ticket_provider','Tribe__Tickets_Plus__Commerce__EDD__Main'),
	(21,13,'_wp_attached_file','woocommerce-placeholder.png'),
	(22,13,'_wp_attachment_metadata','a:5:{s:5:\"width\";i:1200;s:6:\"height\";i:1200;s:4:\"file\";s:27:\"woocommerce-placeholder.png\";s:5:\"sizes\";a:5:{s:6:\"medium\";a:4:{s:4:\"file\";s:35:\"woocommerce-placeholder-300x300.png\";s:5:\"width\";i:300;s:6:\"height\";i:300;s:9:\"mime-type\";s:9:\"image/png\";}s:5:\"large\";a:4:{s:4:\"file\";s:37:\"woocommerce-placeholder-1024x1024.png\";s:5:\"width\";i:1024;s:6:\"height\";i:1024;s:9:\"mime-type\";s:9:\"image/png\";}s:9:\"thumbnail\";a:4:{s:4:\"file\";s:35:\"woocommerce-placeholder-150x150.png\";s:5:\"width\";i:150;s:6:\"height\";i:150;s:9:\"mime-type\";s:9:\"image/png\";}s:12:\"medium_large\";a:4:{s:4:\"file\";s:35:\"woocommerce-placeholder-768x768.png\";s:5:\"width\";i:768;s:6:\"height\";i:768;s:9:\"mime-type\";s:9:\"image/png\";}s:32:\"twentyseventeen-thumbnail-avatar\";a:4:{s:4:\"file\";s:35:\"woocommerce-placeholder-100x100.png\";s:5:\"width\";i:100;s:6:\"height\";i:100;s:9:\"mime-type\";s:9:\"image/png\";}}s:10:\"image_meta\";a:12:{s:8:\"aperture\";s:1:\"0\";s:6:\"credit\";s:0:\"\";s:6:\"camera\";s:0:\"\";s:7:\"caption\";s:0:\"\";s:17:\"created_timestamp\";s:1:\"0\";s:9:\"copyright\";s:0:\"\";s:12:\"focal_length\";s:1:\"0\";s:3:\"iso\";s:1:\"0\";s:13:\"shutter_speed\";s:1:\"0\";s:5:\"title\";s:0:\"\";s:11:\"orientation\";s:1:\"0\";s:8:\"keywords\";a:0:{}}}');

/*!40000 ALTER TABLE `wp_postmeta` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table wp_posts
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_posts`;

CREATE TABLE `wp_posts` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_author` bigint(20) unsigned NOT NULL DEFAULT '0',
  `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_title` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_excerpt` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_status` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'publish',
  `comment_status` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'open',
  `ping_status` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'open',
  `post_password` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `post_name` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `to_ping` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `pinged` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content_filtered` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `guid` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `menu_order` int(11) NOT NULL DEFAULT '0',
  `post_type` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'post',
  `post_mime_type` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_count` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `post_name` (`post_name`(191)),
  KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`),
  KEY `post_parent` (`post_parent`),
  KEY `post_author` (`post_author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

LOCK TABLES `wp_posts` WRITE;
/*!40000 ALTER TABLE `wp_posts` DISABLE KEYS */;

INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`)
VALUES
	(2,1,'2018-06-28 14:10:01','2018-06-28 14:10:01','[download_checkout]','Checkout','','publish','closed','closed','','checkout','','','2018-06-28 14:10:01','2018-06-28 14:10:01','',0,'http://test.tribe.dev/checkout/',0,'page','',0),
	(3,1,'2018-06-28 14:10:01','2018-06-28 14:10:01','Thank you for your purchase! [edd_receipt]','Purchase Confirmation','','publish','closed','closed','','purchase-confirmation','','','2018-06-28 14:10:01','2018-06-28 14:10:01','',2,'http://test.tribe.dev/checkout/purchase-confirmation/',0,'page','',0),
	(4,1,'2018-06-28 14:10:01','2018-06-28 14:10:01','Your transaction failed, please try again or contact site support.','Transaction Failed','','publish','closed','closed','','transaction-failed','','','2018-06-28 14:10:01','2018-06-28 14:10:01','',2,'http://test.tribe.dev/checkout/transaction-failed/',0,'page','',0),
	(5,1,'2018-06-28 14:10:01','2018-06-28 14:10:01','[purchase_history]','Purchase History','','publish','closed','closed','','purchase-history','','','2018-06-28 14:10:01','2018-06-28 14:10:01','',2,'http://test.tribe.dev/checkout/purchase-history/',0,'page','',0),
	(6,1,'2018-06-28 14:10:55','2018-06-28 14:10:55','','Shop','','publish','closed','closed','','shop','','','2018-06-28 14:10:55','2018-06-28 14:10:55','',0,'http://test.tribe.dev/shop/',0,'page','',0),
	(7,1,'2018-06-28 14:10:55','2018-06-28 14:10:55','[woocommerce_cart]','Cart','','publish','closed','closed','','cart','','','2018-06-28 14:10:55','2018-06-28 14:10:55','',0,'http://test.tribe.dev/cart/',0,'page','',0),
	(8,1,'2018-06-28 14:10:55','2018-06-28 14:10:55','[woocommerce_checkout]','Checkout','','publish','closed','closed','','checkout-2','','','2018-06-28 14:10:55','2018-06-28 14:10:55','',0,'http://test.tribe.dev/checkout-2/',0,'page','',0),
	(9,1,'2018-06-28 14:10:55','2018-06-28 14:10:55','[woocommerce_my_account]','My account','','publish','closed','closed','','my-account','','','2018-06-28 14:10:55','2018-06-28 14:10:55','',0,'http://test.tribe.dev/my-account/',0,'page','',0),
	(12,0,'2020-03-12 04:33:28','2020-03-12 04:33:28','Test Easy Digital Downloads ticket description for 10','Test Easy Digital Downloads ticket for 10','Ticket Easy Digital Downloads ticket excerpt for 10','publish','closed','closed','','test-easy-digital-downloads-ticket-for-10','','','2020-03-12 04:33:28','2020-03-12 04:33:28','',0,'http://test.tribe.dev/event/post-10-title/',0,'download','',0),
	(13,0,'2020-03-12 04:33:28','2020-03-12 04:33:28','','woocommerce-placeholder','','inherit','open','closed','','woocommerce-placeholder','','','2020-03-12 04:33:28','2020-03-12 04:33:28','',0,'http://test.tribe.dev/wp-content/uploads/2020/03/woocommerce-placeholder.png',0,'attachment','image/png',0);

/*!40000 ALTER TABLE `wp_posts` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table wp_term_relationships
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_term_relationships`;

CREATE TABLE `wp_term_relationships` (
  `object_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `term_taxonomy_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `term_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`object_id`,`term_taxonomy_id`),
  KEY `term_taxonomy_id` (`term_taxonomy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;



# Dump of table wp_term_taxonomy
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_term_taxonomy`;

CREATE TABLE `wp_term_taxonomy` (
  `term_taxonomy_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `taxonomy` varchar(32) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `parent` bigint(20) unsigned NOT NULL DEFAULT '0',
  `count` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`term_taxonomy_id`),
  UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
  KEY `taxonomy` (`taxonomy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

LOCK TABLES `wp_term_taxonomy` WRITE;
/*!40000 ALTER TABLE `wp_term_taxonomy` DISABLE KEYS */;

INSERT INTO `wp_term_taxonomy` (`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`)
VALUES
	(1,1,'category','',0,0),
	(2,2,'product_type','',0,0),
	(3,3,'product_type','',0,0),
	(4,4,'product_type','',0,0),
	(5,5,'product_type','',0,0),
	(6,6,'product_visibility','',0,0),
	(7,7,'product_visibility','',0,0),
	(8,8,'product_visibility','',0,0),
	(9,9,'product_visibility','',0,0),
	(10,10,'product_visibility','',0,0),
	(11,11,'product_visibility','',0,0),
	(12,12,'product_visibility','',0,0),
	(13,13,'product_visibility','',0,0),
	(14,14,'product_visibility','',0,0),
	(15,15,'product_cat','',0,0);

/*!40000 ALTER TABLE `wp_term_taxonomy` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table wp_termmeta
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_termmeta`;

CREATE TABLE `wp_termmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`meta_id`),
  KEY `term_id` (`term_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;



# Dump of table wp_terms
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_terms`;

CREATE TABLE `wp_terms` (
  `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `slug` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `term_group` bigint(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`term_id`),
  KEY `slug` (`slug`(191)),
  KEY `name` (`name`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

LOCK TABLES `wp_terms` WRITE;
/*!40000 ALTER TABLE `wp_terms` DISABLE KEYS */;

INSERT INTO `wp_terms` (`term_id`, `name`, `slug`, `term_group`)
VALUES
	(1,'Uncategorized','uncategorized',0),
	(2,'simple','simple',0),
	(3,'grouped','grouped',0),
	(4,'variable','variable',0),
	(5,'external','external',0),
	(6,'exclude-from-search','exclude-from-search',0),
	(7,'exclude-from-catalog','exclude-from-catalog',0),
	(8,'featured','featured',0),
	(9,'outofstock','outofstock',0),
	(10,'rated-1','rated-1',0),
	(11,'rated-2','rated-2',0),
	(12,'rated-3','rated-3',0),
	(13,'rated-4','rated-4',0),
	(14,'rated-5','rated-5',0),
	(15,'Uncategorized','uncategorized',0);

/*!40000 ALTER TABLE `wp_terms` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table wp_usermeta
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_usermeta`;

CREATE TABLE `wp_usermeta` (
  `umeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `meta_key` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`umeta_id`),
  KEY `user_id` (`user_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

LOCK TABLES `wp_usermeta` WRITE;
/*!40000 ALTER TABLE `wp_usermeta` DISABLE KEYS */;

INSERT INTO `wp_usermeta` (`umeta_id`, `user_id`, `meta_key`, `meta_value`)
VALUES
	(1,1,'nickname','admin'),
	(2,1,'first_name',''),
	(3,1,'last_name',''),
	(4,1,'description',''),
	(5,1,'rich_editing','true'),
	(6,1,'syntax_highlighting','true'),
	(7,1,'comment_shortcuts','false'),
	(8,1,'admin_color','fresh'),
	(9,1,'use_ssl','0'),
	(10,1,'show_admin_bar_front','true'),
	(11,1,'locale',''),
	(12,1,'wp_capabilities','a:1:{s:13:\"administrator\";b:1;}'),
	(13,1,'wp_user_level','10'),
	(14,1,'dismissed_wp_pointers','wp496_privacy'),
	(15,1,'show_welcome_panel','1'),
	(16,1,'session_tokens','a:1:{s:64:\"c1e34ec34914ac88efe86c49b18c5ee4445e4373a04fac7d0789c6411faf021a\";a:4:{s:10:\"expiration\";i:1584162601;s:2:\"ip\";s:10:\"172.17.0.1\";s:2:\"ua\";s:121:\"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.122 Safari/537.36\";s:5:\"login\";i:1583989801;}}'),
	(17,1,'wp_user-settings','libraryContent=browse'),
	(18,1,'wp_user-settings-time','1521628054'),
	(19,1,'wp_dashboard_quick_press_last_post_id','1'),
	(20,1,'community-events-location','a:1:{s:2:\"ip\";s:12:\"192.168.92.0\";}'),
	(21,1,'_woocommerce_persistent_cart_1','a:1:{s:4:\"cart\";a:0:{}}'),
	(22,1,'wc_last_active','1583971200'),
	(24,1,'_edd_nginx_redirect_dismissed','1'),
	(25,1,'tribe-dismiss-notice','maybe_display_ar_modal_options_notice'),
	(26,1,'dismissed_no_secure_connection_notice','1'),
	(27,1,'dismissed_maxmind_license_key_notice','1'),
	(28,1,'dismissed_update_notice','1'),
	(29,1,'tribe-dismiss-notice','pue_key-expired_key');

/*!40000 ALTER TABLE `wp_usermeta` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table wp_users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `wp_users`;

CREATE TABLE `wp_users` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_login` varchar(60) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_pass` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_nicename` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_email` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_url` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_registered` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_activation_key` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `user_status` int(11) NOT NULL DEFAULT '0',
  `display_name` varchar(250) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `user_login_key` (`user_login`),
  KEY `user_nicename` (`user_nicename`),
  KEY `user_email` (`user_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

LOCK TABLES `wp_users` WRITE;
/*!40000 ALTER TABLE `wp_users` DISABLE KEYS */;

INSERT INTO `wp_users` (`ID`, `user_login`, `user_pass`, `user_nicename`, `user_email`, `user_url`, `user_registered`, `user_activation_key`, `user_status`, `display_name`)
VALUES
	(1,'admin','$P$BijszyAe0q1K3kE1Rf4djq09hBYyY9.','admin','admin@commerce.dev','','2018-03-21 10:27:29','',0,'admin');

/*!40000 ALTER TABLE `wp_users` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
