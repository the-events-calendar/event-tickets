/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.5.26-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: db    Database: test
-- ------------------------------------------------------
-- Server version	10.7.8-MariaDB-1:10.7.8+maria~ubu2004

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `wp_commentmeta`
--

DROP TABLE IF EXISTS `wp_commentmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_commentmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `comment_id` (`comment_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_commentmeta`
--

LOCK TABLES `wp_commentmeta` WRITE;
/*!40000 ALTER TABLE `wp_commentmeta` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_commentmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_comments`
--

DROP TABLE IF EXISTS `wp_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_comments` (
  `comment_ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_post_ID` bigint(20) unsigned NOT NULL DEFAULT 0,
  `comment_author` tinytext NOT NULL,
  `comment_author_email` varchar(100) NOT NULL DEFAULT '',
  `comment_author_url` varchar(200) NOT NULL DEFAULT '',
  `comment_author_IP` varchar(100) NOT NULL DEFAULT '',
  `comment_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_content` text NOT NULL,
  `comment_karma` int(11) NOT NULL DEFAULT 0,
  `comment_approved` varchar(20) NOT NULL DEFAULT '1',
  `comment_agent` varchar(255) NOT NULL DEFAULT '',
  `comment_type` varchar(20) NOT NULL DEFAULT 'comment',
  `comment_parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`comment_ID`),
  KEY `comment_post_ID` (`comment_post_ID`),
  KEY `comment_approved_date_gmt` (`comment_approved`,`comment_date_gmt`),
  KEY `comment_date_gmt` (`comment_date_gmt`),
  KEY `comment_parent` (`comment_parent`),
  KEY `comment_author_email` (`comment_author_email`(10))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_comments`
--

LOCK TABLES `wp_comments` WRITE;
/*!40000 ALTER TABLE `wp_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_links`
--

DROP TABLE IF EXISTS `wp_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_links` (
  `link_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `link_url` varchar(255) NOT NULL DEFAULT '',
  `link_name` varchar(255) NOT NULL DEFAULT '',
  `link_image` varchar(255) NOT NULL DEFAULT '',
  `link_target` varchar(25) NOT NULL DEFAULT '',
  `link_description` varchar(255) NOT NULL DEFAULT '',
  `link_visible` varchar(20) NOT NULL DEFAULT 'Y',
  `link_owner` bigint(20) unsigned NOT NULL DEFAULT 1,
  `link_rating` int(11) NOT NULL DEFAULT 0,
  `link_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `link_rel` varchar(255) NOT NULL DEFAULT '',
  `link_notes` mediumtext NOT NULL,
  `link_rss` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`link_id`),
  KEY `link_visible` (`link_visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_links`
--

LOCK TABLES `wp_links` WRITE;
/*!40000 ALTER TABLE `wp_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_options`
--

DROP TABLE IF EXISTS `wp_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_options` (
  `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `option_name` varchar(191) NOT NULL DEFAULT '',
  `option_value` longtext NOT NULL,
  `autoload` varchar(20) NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`option_id`),
  UNIQUE KEY `option_name` (`option_name`),
  KEY `autoload` (`autoload`)
) ENGINE=InnoDB AUTO_INCREMENT=177 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_options`
--

LOCK TABLES `wp_options` WRITE;
/*!40000 ALTER TABLE `wp_options` DISABLE KEYS */;
INSERT INTO `wp_options` VALUES (1,'siteurl','http://wordpress.test','yes'),(2,'home','http://wordpress.test','yes'),(3,'blogname','Tribe','yes'),(4,'blogdescription','Just another WordPress site','yes'),(5,'users_can_register','0','yes'),(6,'admin_email','admin@tribe.local','yes'),(7,'start_of_week','1','yes'),(8,'use_balanceTags','0','yes'),(9,'use_smilies','1','yes'),(10,'require_name_email','1','yes'),(11,'comments_notify','1','yes'),(12,'posts_per_rss','10','yes'),(13,'rss_use_excerpt','0','yes'),(14,'mailserver_url','mail.example.com','yes'),(15,'mailserver_login','login@example.com','yes'),(16,'mailserver_pass','password','yes'),(17,'mailserver_port','110','yes'),(18,'default_category','1','yes'),(19,'default_comment_status','open','yes'),(20,'default_ping_status','open','yes'),(21,'default_pingback_flag','1','yes'),(22,'posts_per_page','10','yes'),(23,'date_format','F j, Y','yes'),(24,'time_format','g:i a','yes'),(25,'links_updated_date_format','F j, Y g:i a','yes'),(26,'comment_moderation','0','yes'),(27,'moderation_notify','1','yes'),(28,'permalink_structure','/%year%/%monthnum%/%day%/%postname%/','yes'),(29,'rewrite_rules','a:98:{s:21:\"tickets/([0-9]{1,})/?\";s:43:\"index.php?p=$matches[1]&tribe-edit-orders=1\";s:11:\"^wp-json/?$\";s:22:\"index.php?rest_route=/\";s:14:\"^wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:21:\"^index.php/wp-json/?$\";s:22:\"index.php?rest_route=/\";s:24:\"^index.php/wp-json/(.*)?\";s:33:\"index.php?rest_route=/$matches[1]\";s:17:\"^wp-sitemap\\.xml$\";s:23:\"index.php?sitemap=index\";s:17:\"^wp-sitemap\\.xsl$\";s:36:\"index.php?sitemap-stylesheet=sitemap\";s:23:\"^wp-sitemap-index\\.xsl$\";s:34:\"index.php?sitemap-stylesheet=index\";s:48:\"^wp-sitemap-([a-z]+?)-([a-z\\d_-]+?)-(\\d+?)\\.xml$\";s:75:\"index.php?sitemap=$matches[1]&sitemap-subtype=$matches[2]&paged=$matches[3]\";s:34:\"^wp-sitemap-([a-z]+?)-(\\d+?)\\.xml$\";s:47:\"index.php?sitemap=$matches[1]&paged=$matches[2]\";s:22:\"tribe-promoter-auth/?$\";s:37:\"index.php?tribe-promoter-auth-check=1\";s:47:\"category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:42:\"category/(.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:52:\"index.php?category_name=$matches[1]&feed=$matches[2]\";s:23:\"category/(.+?)/embed/?$\";s:46:\"index.php?category_name=$matches[1]&embed=true\";s:35:\"category/(.+?)/page/?([0-9]{1,})/?$\";s:53:\"index.php?category_name=$matches[1]&paged=$matches[2]\";s:17:\"category/(.+?)/?$\";s:35:\"index.php?category_name=$matches[1]\";s:44:\"tag/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:39:\"tag/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?tag=$matches[1]&feed=$matches[2]\";s:20:\"tag/([^/]+)/embed/?$\";s:36:\"index.php?tag=$matches[1]&embed=true\";s:32:\"tag/([^/]+)/page/?([0-9]{1,})/?$\";s:43:\"index.php?tag=$matches[1]&paged=$matches[2]\";s:14:\"tag/([^/]+)/?$\";s:25:\"index.php?tag=$matches[1]\";s:45:\"type/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:40:\"type/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?post_format=$matches[1]&feed=$matches[2]\";s:21:\"type/([^/]+)/embed/?$\";s:44:\"index.php?post_format=$matches[1]&embed=true\";s:33:\"type/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?post_format=$matches[1]&paged=$matches[2]\";s:15:\"type/([^/]+)/?$\";s:33:\"index.php?post_format=$matches[1]\";s:12:\"robots\\.txt$\";s:18:\"index.php?robots=1\";s:13:\"favicon\\.ico$\";s:19:\"index.php?favicon=1\";s:48:\".*wp-(atom|rdf|rss|rss2|feed|commentsrss2)\\.php$\";s:18:\"index.php?feed=old\";s:20:\".*wp-app\\.php(/.*)?$\";s:19:\"index.php?error=403\";s:18:\".*wp-register.php$\";s:23:\"index.php?register=true\";s:32:\"feed/(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:27:\"(feed|rdf|rss|rss2|atom)/?$\";s:27:\"index.php?&feed=$matches[1]\";s:8:\"embed/?$\";s:21:\"index.php?&embed=true\";s:20:\"page/?([0-9]{1,})/?$\";s:28:\"index.php?&paged=$matches[1]\";s:41:\"comments/feed/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:36:\"comments/(feed|rdf|rss|rss2|atom)/?$\";s:42:\"index.php?&feed=$matches[1]&withcomments=1\";s:17:\"comments/embed/?$\";s:21:\"index.php?&embed=true\";s:44:\"search/(.+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:39:\"search/(.+)/(feed|rdf|rss|rss2|atom)/?$\";s:40:\"index.php?s=$matches[1]&feed=$matches[2]\";s:20:\"search/(.+)/embed/?$\";s:34:\"index.php?s=$matches[1]&embed=true\";s:32:\"search/(.+)/page/?([0-9]{1,})/?$\";s:41:\"index.php?s=$matches[1]&paged=$matches[2]\";s:14:\"search/(.+)/?$\";s:23:\"index.php?s=$matches[1]\";s:47:\"author/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:42:\"author/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:50:\"index.php?author_name=$matches[1]&feed=$matches[2]\";s:23:\"author/([^/]+)/embed/?$\";s:44:\"index.php?author_name=$matches[1]&embed=true\";s:35:\"author/([^/]+)/page/?([0-9]{1,})/?$\";s:51:\"index.php?author_name=$matches[1]&paged=$matches[2]\";s:17:\"author/([^/]+)/?$\";s:33:\"index.php?author_name=$matches[1]\";s:69:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:64:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$\";s:80:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]\";s:45:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/embed/?$\";s:74:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&embed=true\";s:57:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$\";s:81:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]\";s:39:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$\";s:63:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]\";s:56:\"([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:51:\"([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$\";s:64:\"index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]\";s:32:\"([0-9]{4})/([0-9]{1,2})/embed/?$\";s:58:\"index.php?year=$matches[1]&monthnum=$matches[2]&embed=true\";s:44:\"([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$\";s:65:\"index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]\";s:26:\"([0-9]{4})/([0-9]{1,2})/?$\";s:47:\"index.php?year=$matches[1]&monthnum=$matches[2]\";s:43:\"([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:38:\"([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$\";s:43:\"index.php?year=$matches[1]&feed=$matches[2]\";s:19:\"([0-9]{4})/embed/?$\";s:37:\"index.php?year=$matches[1]&embed=true\";s:31:\"([0-9]{4})/page/?([0-9]{1,})/?$\";s:44:\"index.php?year=$matches[1]&paged=$matches[2]\";s:13:\"([0-9]{4})/?$\";s:26:\"index.php?year=$matches[1]\";s:58:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:68:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:88:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:83:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:83:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:64:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:53:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/embed/?$\";s:91:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&embed=true\";s:57:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/trackback/?$\";s:85:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&tb=1\";s:77:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:97:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&feed=$matches[5]\";s:72:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:97:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&feed=$matches[5]\";s:65:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/page/?([0-9]{1,})/?$\";s:98:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&paged=$matches[5]\";s:72:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/comment-page-([0-9]{1,})/?$\";s:98:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&cpage=$matches[5]\";s:61:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)(?:/([0-9]+))?/?$\";s:97:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&page=$matches[5]\";s:47:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:57:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:77:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:72:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:72:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:53:\"[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:64:\"([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/comment-page-([0-9]{1,})/?$\";s:81:\"index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&cpage=$matches[4]\";s:51:\"([0-9]{4})/([0-9]{1,2})/comment-page-([0-9]{1,})/?$\";s:65:\"index.php?year=$matches[1]&monthnum=$matches[2]&cpage=$matches[3]\";s:38:\"([0-9]{4})/comment-page-([0-9]{1,})/?$\";s:44:\"index.php?year=$matches[1]&cpage=$matches[2]\";s:27:\".?.+?/attachment/([^/]+)/?$\";s:32:\"index.php?attachment=$matches[1]\";s:37:\".?.+?/attachment/([^/]+)/trackback/?$\";s:37:\"index.php?attachment=$matches[1]&tb=1\";s:57:\".?.+?/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$\";s:49:\"index.php?attachment=$matches[1]&feed=$matches[2]\";s:52:\".?.+?/attachment/([^/]+)/comment-page-([0-9]{1,})/?$\";s:50:\"index.php?attachment=$matches[1]&cpage=$matches[2]\";s:33:\".?.+?/attachment/([^/]+)/embed/?$\";s:43:\"index.php?attachment=$matches[1]&embed=true\";s:16:\"(.?.+?)/embed/?$\";s:41:\"index.php?pagename=$matches[1]&embed=true\";s:20:\"(.?.+?)/trackback/?$\";s:35:\"index.php?pagename=$matches[1]&tb=1\";s:40:\"(.?.+?)/feed/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:35:\"(.?.+?)/(feed|rdf|rss|rss2|atom)/?$\";s:47:\"index.php?pagename=$matches[1]&feed=$matches[2]\";s:28:\"(.?.+?)/page/?([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&paged=$matches[2]\";s:35:\"(.?.+?)/comment-page-([0-9]{1,})/?$\";s:48:\"index.php?pagename=$matches[1]&cpage=$matches[2]\";s:24:\"(.?.+?)(?:/([0-9]+))?/?$\";s:47:\"index.php?pagename=$matches[1]&page=$matches[2]\";}','yes'),(30,'hack_file','0','yes'),(31,'blog_charset','UTF-8','yes'),(32,'moderation_keys','','no'),(33,'active_plugins','a:1:{i:0;s:31:\"event-tickets/event-tickets.php\";}','yes'),(34,'category_base','','yes'),(35,'ping_sites','http://rpc.pingomatic.com/','yes'),(36,'comment_max_links','2','yes'),(37,'gmt_offset','0','yes'),(38,'default_email_category','1','yes'),(39,'recently_edited','','no'),(40,'template','twentytwenty','yes'),(41,'stylesheet','twentytwenty','yes'),(42,'comment_whitelist','1','yes'),(43,'blacklist_keys','','no'),(44,'comment_registration','0','yes'),(45,'html_type','text/html','yes'),(46,'use_trackback','0','yes'),(47,'default_role','subscriber','yes'),(48,'db_version','56657','yes'),(49,'uploads_use_yearmonth_folders','1','yes'),(50,'upload_path','','yes'),(51,'blog_public','1','yes'),(52,'default_link_category','2','yes'),(53,'show_on_front','posts','yes'),(54,'tag_base','','yes'),(55,'show_avatars','1','yes'),(56,'avatar_rating','G','yes'),(57,'upload_url_path','','yes'),(58,'thumbnail_size_w','150','yes'),(59,'thumbnail_size_h','150','yes'),(60,'thumbnail_crop','1','yes'),(61,'medium_size_w','300','yes'),(62,'medium_size_h','300','yes'),(63,'avatar_default','mystery','yes'),(64,'large_size_w','1024','yes'),(65,'large_size_h','1024','yes'),(66,'image_default_link_type','none','yes'),(67,'image_default_size','','yes'),(68,'image_default_align','','yes'),(69,'close_comments_for_old_posts','0','yes'),(70,'close_comments_days_old','14','yes'),(71,'thread_comments','1','yes'),(72,'thread_comments_depth','5','yes'),(73,'page_comments','0','yes'),(74,'comments_per_page','50','yes'),(75,'default_comments_page','newest','yes'),(76,'comment_order','asc','yes'),(77,'sticky_posts','a:0:{}','yes'),(78,'widget_categories','a:2:{i:2;a:4:{s:5:\"title\";s:0:\"\";s:5:\"count\";i:0;s:12:\"hierarchical\";i:0;s:8:\"dropdown\";i:0;}s:12:\"_multiwidget\";i:1;}','yes'),(79,'widget_text','a:0:{}','yes'),(80,'widget_rss','a:0:{}','yes'),(81,'uninstall_plugins','a:0:{}','no'),(82,'timezone_string','','yes'),(83,'page_for_posts','0','yes'),(84,'page_on_front','0','yes'),(85,'default_post_format','0','yes'),(86,'link_manager_enabled','0','yes'),(87,'finished_splitting_shared_terms','1','yes'),(88,'site_icon','0','yes'),(89,'medium_large_size_w','768','yes'),(90,'medium_large_size_h','0','yes'),(91,'initial_db_version','38590','yes'),(92,'wp_user_roles','a:5:{s:13:\"administrator\";a:2:{s:4:\"name\";s:13:\"Administrator\";s:12:\"capabilities\";a:61:{s:13:\"switch_themes\";b:1;s:11:\"edit_themes\";b:1;s:16:\"activate_plugins\";b:1;s:12:\"edit_plugins\";b:1;s:10:\"edit_users\";b:1;s:10:\"edit_files\";b:1;s:14:\"manage_options\";b:1;s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:6:\"import\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:8:\"level_10\";b:1;s:7:\"level_9\";b:1;s:7:\"level_8\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;s:12:\"delete_users\";b:1;s:12:\"create_users\";b:1;s:17:\"unfiltered_upload\";b:1;s:14:\"edit_dashboard\";b:1;s:14:\"update_plugins\";b:1;s:14:\"delete_plugins\";b:1;s:15:\"install_plugins\";b:1;s:13:\"update_themes\";b:1;s:14:\"install_themes\";b:1;s:11:\"update_core\";b:1;s:10:\"list_users\";b:1;s:12:\"remove_users\";b:1;s:13:\"promote_users\";b:1;s:18:\"edit_theme_options\";b:1;s:13:\"delete_themes\";b:1;s:6:\"export\";b:1;}}s:6:\"editor\";a:2:{s:4:\"name\";s:6:\"Editor\";s:12:\"capabilities\";a:34:{s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;}}s:6:\"author\";a:2:{s:4:\"name\";s:6:\"Author\";s:12:\"capabilities\";a:10:{s:12:\"upload_files\";b:1;s:10:\"edit_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;s:22:\"delete_published_posts\";b:1;}}s:11:\"contributor\";a:2:{s:4:\"name\";s:11:\"Contributor\";s:12:\"capabilities\";a:5:{s:10:\"edit_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;}}s:10:\"subscriber\";a:2:{s:4:\"name\";s:10:\"Subscriber\";s:12:\"capabilities\";a:2:{s:4:\"read\";b:1;s:7:\"level_0\";b:1;}}}','yes'),(93,'fresh_site','1','yes'),(94,'widget_search','a:2:{i:2;a:1:{s:5:\"title\";s:0:\"\";}s:12:\"_multiwidget\";i:1;}','yes'),(95,'widget_recent-posts','a:2:{i:2;a:2:{s:5:\"title\";s:0:\"\";s:6:\"number\";i:5;}s:12:\"_multiwidget\";i:1;}','yes'),(96,'widget_recent-comments','a:2:{i:2;a:2:{s:5:\"title\";s:0:\"\";s:6:\"number\";i:5;}s:12:\"_multiwidget\";i:1;}','yes'),(97,'widget_archives','a:2:{i:2;a:3:{s:5:\"title\";s:0:\"\";s:5:\"count\";i:0;s:8:\"dropdown\";i:0;}s:12:\"_multiwidget\";i:1;}','yes'),(98,'widget_meta','a:2:{i:2;a:1:{s:5:\"title\";s:0:\"\";}s:12:\"_multiwidget\";i:1;}','yes'),(99,'sidebars_widgets','a:5:{s:19:\"wp_inactive_widgets\";a:0:{}s:9:\"sidebar-1\";a:6:{i:0;s:8:\"search-2\";i:1;s:14:\"recent-posts-2\";i:2;s:17:\"recent-comments-2\";i:3;s:10:\"archives-2\";i:4;s:12:\"categories-2\";i:5;s:6:\"meta-2\";}s:9:\"sidebar-2\";a:0:{}s:9:\"sidebar-3\";a:0:{}s:13:\"array_version\";i:3;}','yes'),(100,'widget_pages','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(101,'widget_calendar','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(102,'widget_media_audio','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(103,'widget_media_image','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(104,'widget_media_gallery','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(105,'widget_media_video','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(106,'widget_tag_cloud','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(107,'widget_nav_menu','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(108,'widget_custom_html','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(109,'cron','a:6:{i:1524665866;a:1:{s:24:\"tribe_common_log_cleanup\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1586395360;a:2:{s:32:\"recovery_mode_clean_expired_keys\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:34:\"wp_privacy_delete_old_export_files\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}}i:1586481760;a:1:{s:30:\"wp_site_health_scheduled_check\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"weekly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:604800;}}}i:1734032327;a:9:{s:30:\"tribe_schedule_transient_purge\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:16:\"tribe_daily_cron\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:31:\"tec_tickets_seating_tables_cron\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}s:26:\"tribe_tickets_migrate_4_12\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}s:19:\"wp_scheduled_delete\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:25:\"delete_expired_transients\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:21:\"wp_update_user_counts\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:46:\"tec_tickets_update_glance_item_attendee_counts\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:2:{s:8:\"schedule\";b:0;s:4:\"args\";a:0:{}}}s:30:\"wp_scheduled_auto_draft_delete\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1734032387;a:1:{s:28:\"wp_update_comment_type_batch\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:2:{s:8:\"schedule\";b:0;s:4:\"args\";a:0:{}}}}s:7:\"version\";i:2;}','yes'),(110,'theme_mods_twentyseventeen','a:1:{s:18:\"custom_css_post_id\";i:-1;}','yes'),(123,'_site_transient_update_core','O:8:\"stdClass\":4:{s:7:\"updates\";a:1:{i:0;O:8:\"stdClass\":10:{s:8:\"response\";s:6:\"latest\";s:8:\"download\";s:59:\"https://downloads.wordpress.org/release/wordpress-4.9.5.zip\";s:6:\"locale\";s:5:\"en_US\";s:8:\"packages\";O:8:\"stdClass\":5:{s:4:\"full\";s:59:\"https://downloads.wordpress.org/release/wordpress-4.9.5.zip\";s:10:\"no_content\";s:70:\"https://downloads.wordpress.org/release/wordpress-4.9.5-no-content.zip\";s:11:\"new_bundled\";s:71:\"https://downloads.wordpress.org/release/wordpress-4.9.5-new-bundled.zip\";s:7:\"partial\";b:0;s:8:\"rollback\";b:0;}s:7:\"current\";s:5:\"4.9.5\";s:7:\"version\";s:5:\"4.9.5\";s:11:\"php_version\";s:5:\"5.2.4\";s:13:\"mysql_version\";s:3:\"5.0\";s:11:\"new_bundled\";s:3:\"4.7\";s:15:\"partial_version\";s:0:\"\";}}s:12:\"last_checked\";i:1524568230;s:15:\"version_checked\";s:5:\"4.9.5\";s:12:\"translations\";a:0:{}}','no'),(124,'_site_transient_update_themes','O:8:\"stdClass\":4:{s:12:\"last_checked\";i:1524568230;s:7:\"checked\";a:3:{s:13:\"twentyfifteen\";s:3:\"1.9\";s:15:\"twentyseventeen\";s:3:\"1.4\";s:13:\"twentysixteen\";s:3:\"1.4\";}s:8:\"response\";a:1:{s:15:\"twentyseventeen\";a:4:{s:5:\"theme\";s:15:\"twentyseventeen\";s:11:\"new_version\";s:3:\"1.5\";s:3:\"url\";s:45:\"https://wordpress.org/themes/twentyseventeen/\";s:7:\"package\";s:61:\"https://downloads.wordpress.org/theme/twentyseventeen.1.5.zip\";}}s:12:\"translations\";a:0:{}}','no'),(125,'_site_transient_update_plugins','O:8:\"stdClass\":5:{s:12:\"last_checked\";i:1524568231;s:7:\"checked\";a:12:{s:35:\"advanced-post-manager/tribe-apm.php\";s:3:\"4.4\";s:31:\"event-tickets/event-tickets.php\";s:5:\"4.7.2\";s:41:\"event-tickets-plus/event-tickets-plus.php\";s:5:\"4.7.2\";s:21:\"gigpress/gigpress.php\";s:6:\"2.3.20\";s:23:\"gutenberg/gutenberg.php\";s:5:\"2.2.0\";s:29:\"image-widget/image-widget.php\";s:5:\"4.4.7\";s:39:\"image-widget-plus/image-widget-plus.php\";s:5:\"1.0.2\";s:35:\"rest-api-tester/rest-api-tester.php\";s:5:\"0.1.0\";s:43:\"the-events-calendar/the-events-calendar.php\";s:6:\"4.8dev\";s:57:\"events-facebook/the-events-calendar-facebook-importer.php\";s:3:\"4.3\";s:58:\"events-importer-ical/the-events-calendar-ical-importer.php\";s:3:\"4.3\";s:34:\"events-pro/events-calendar-pro.php\";s:6:\"4.5dev\";}s:8:\"response\";a:1:{s:23:\"gutenberg/gutenberg.php\";O:8:\"stdClass\":12:{s:2:\"id\";s:23:\"w.org/plugins/gutenberg\";s:4:\"slug\";s:9:\"gutenberg\";s:6:\"plugin\";s:23:\"gutenberg/gutenberg.php\";s:11:\"new_version\";s:5:\"2.7.0\";s:3:\"url\";s:40:\"https://wordpress.org/plugins/gutenberg/\";s:7:\"package\";s:58:\"https://downloads.wordpress.org/plugin/gutenberg.2.7.0.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:62:\"https://ps.w.org/gutenberg/assets/icon-256x256.jpg?rev=1776042\";s:2:\"1x\";s:62:\"https://ps.w.org/gutenberg/assets/icon-128x128.jpg?rev=1776042\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:65:\"https://ps.w.org/gutenberg/assets/banner-1544x500.jpg?rev=1718710\";s:2:\"1x\";s:64:\"https://ps.w.org/gutenberg/assets/banner-772x250.jpg?rev=1718710\";}s:11:\"banners_rtl\";a:0:{}s:6:\"tested\";s:5:\"4.9.5\";s:12:\"requires_php\";N;s:13:\"compatibility\";O:8:\"stdClass\":0:{}}}s:12:\"translations\";a:0:{}s:9:\"no_update\";a:5:{s:35:\"advanced-post-manager/tribe-apm.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:35:\"w.org/plugins/advanced-post-manager\";s:4:\"slug\";s:21:\"advanced-post-manager\";s:6:\"plugin\";s:35:\"advanced-post-manager/tribe-apm.php\";s:11:\"new_version\";s:3:\"4.4\";s:3:\"url\";s:52:\"https://wordpress.org/plugins/advanced-post-manager/\";s:7:\"package\";s:68:\"https://downloads.wordpress.org/plugin/advanced-post-manager.4.4.zip\";s:5:\"icons\";a:1:{s:7:\"default\";s:72:\"https://s.w.org/plugins/geopattern-icon/advanced-post-manager_66b8d2.svg\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:76:\"https://ps.w.org/advanced-post-manager/assets/banner-1544x500.jpg?rev=593014\";s:2:\"1x\";s:75:\"https://ps.w.org/advanced-post-manager/assets/banner-772x250.png?rev=517740\";}s:11:\"banners_rtl\";a:0:{}}s:31:\"event-tickets/event-tickets.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:27:\"w.org/plugins/event-tickets\";s:4:\"slug\";s:13:\"event-tickets\";s:6:\"plugin\";s:31:\"event-tickets/event-tickets.php\";s:11:\"new_version\";s:5:\"4.7.2\";s:3:\"url\";s:44:\"https://wordpress.org/plugins/event-tickets/\";s:7:\"package\";s:62:\"https://downloads.wordpress.org/plugin/event-tickets.4.7.2.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:66:\"https://ps.w.org/event-tickets/assets/icon-256x256.png?rev=1299138\";s:2:\"1x\";s:66:\"https://ps.w.org/event-tickets/assets/icon-128x128.png?rev=1299138\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:69:\"https://ps.w.org/event-tickets/assets/banner-1544x500.png?rev=1299128\";s:2:\"1x\";s:68:\"https://ps.w.org/event-tickets/assets/banner-772x250.png?rev=1299128\";}s:11:\"banners_rtl\";a:0:{}}s:21:\"gigpress/gigpress.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:22:\"w.org/plugins/gigpress\";s:4:\"slug\";s:8:\"gigpress\";s:6:\"plugin\";s:21:\"gigpress/gigpress.php\";s:11:\"new_version\";s:6:\"2.3.20\";s:3:\"url\";s:39:\"https://wordpress.org/plugins/gigpress/\";s:7:\"package\";s:51:\"https://downloads.wordpress.org/plugin/gigpress.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:60:\"https://ps.w.org/gigpress/assets/icon-256x256.jpg?rev=979213\";s:2:\"1x\";s:60:\"https://ps.w.org/gigpress/assets/icon-128x128.jpg?rev=979213\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:63:\"https://ps.w.org/gigpress/assets/banner-1544x500.jpg?rev=979213\";s:2:\"1x\";s:62:\"https://ps.w.org/gigpress/assets/banner-772x250.jpg?rev=979213\";}s:11:\"banners_rtl\";a:0:{}}s:29:\"image-widget/image-widget.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:26:\"w.org/plugins/image-widget\";s:4:\"slug\";s:12:\"image-widget\";s:6:\"plugin\";s:29:\"image-widget/image-widget.php\";s:11:\"new_version\";s:5:\"4.4.7\";s:3:\"url\";s:43:\"https://wordpress.org/plugins/image-widget/\";s:7:\"package\";s:61:\"https://downloads.wordpress.org/plugin/image-widget.4.4.7.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:64:\"https://ps.w.org/image-widget/assets/icon-256x256.jpg?rev=985707\";s:2:\"1x\";s:64:\"https://ps.w.org/image-widget/assets/icon-128x128.jpg?rev=985707\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:67:\"https://ps.w.org/image-widget/assets/banner-1544x500.jpg?rev=593018\";s:2:\"1x\";s:66:\"https://ps.w.org/image-widget/assets/banner-772x250.png?rev=517739\";}s:11:\"banners_rtl\";a:0:{}}s:43:\"the-events-calendar/the-events-calendar.php\";O:8:\"stdClass\":9:{s:2:\"id\";s:33:\"w.org/plugins/the-events-calendar\";s:4:\"slug\";s:19:\"the-events-calendar\";s:6:\"plugin\";s:43:\"the-events-calendar/the-events-calendar.php\";s:11:\"new_version\";s:8:\"4.6.14.1\";s:3:\"url\";s:50:\"https://wordpress.org/plugins/the-events-calendar/\";s:7:\"package\";s:71:\"https://downloads.wordpress.org/plugin/the-events-calendar.4.6.14.1.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:72:\"https://ps.w.org/the-events-calendar/assets/icon-256x256.png?rev=1679210\";s:2:\"1x\";s:72:\"https://ps.w.org/the-events-calendar/assets/icon-128x128.png?rev=1679210\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:75:\"https://ps.w.org/the-events-calendar/assets/banner-1544x500.png?rev=1679210\";s:2:\"1x\";s:74:\"https://ps.w.org/the-events-calendar/assets/banner-772x250.png?rev=1679210\";}s:11:\"banners_rtl\";a:0:{}}}}','no'),(126,'auto_core_update_notified','a:4:{s:4:\"type\";s:7:\"success\";s:5:\"email\";s:17:\"admin@tribe.local\";s:7:\"version\";s:5:\"4.9.5\";s:9:\"timestamp\";i:1524568231;}','no'),(127,'tribe_skip_welcome','1','yes'),(129,'tribe_events_calendar_options','a:4:{s:25:\"ticket-enabled-post-types\";a:1:{i:0;s:12:\"tribe_events\";}s:31:\"previous_event_tickets_versions\";a:3:{i:0;s:1:\"0\";i:1;s:5:\"4.7.2\";i:2;s:6:\"4.12.0\";}s:28:\"latest_event_tickets_version\";s:8:\"5.17.0.1\";s:28:\"event-tickets-schema-version\";s:8:\"5.17.0.1\";}','yes'),(130,'recently_activated','a:2:{s:31:\"event-tickets/event-tickets.php\";i:1524579502;i:0;b:0;}','yes'),(132,'wp_page_for_privacy_policy','0','yes'),(133,'show_comments_cookies_opt_in','1','yes'),(135,'db_upgraded','1','yes'),(137,'fs_active_plugins','O:8:\"stdClass\":2:{s:7:\"plugins\";a:0:{}s:7:\"abspath\";s:26:\"/shared/httpd/main/htdocs/\";}','yes'),(138,'fs_debug_mode','','yes'),(139,'fs_accounts','a:5:{s:21:\"id_slug_type_path_map\";a:1:{i:3841;a:3:{s:4:\"slug\";s:13:\"event-tickets\";s:4:\"type\";s:6:\"plugin\";s:4:\"path\";s:31:\"event-tickets/event-tickets.php\";}}s:11:\"plugin_data\";a:1:{s:13:\"event-tickets\";a:15:{s:16:\"plugin_main_file\";O:8:\"stdClass\":1:{s:4:\"path\";s:31:\"event-tickets/event-tickets.php\";}s:20:\"is_network_activated\";b:0;s:17:\"install_timestamp\";i:1586395381;s:17:\"was_plugin_loaded\";b:1;s:21:\"is_plugin_new_install\";b:0;s:16:\"sdk_last_version\";N;s:11:\"sdk_version\";s:5:\"2.3.2\";s:16:\"sdk_upgrade_mode\";b:1;s:18:\"sdk_downgrade_mode\";b:0;s:19:\"plugin_last_version\";N;s:14:\"plugin_version\";s:6:\"4.12.0\";s:19:\"plugin_upgrade_mode\";b:1;s:21:\"plugin_downgrade_mode\";b:0;s:17:\"connectivity_test\";a:6:{s:12:\"is_connected\";b:1;s:4:\"host\";s:8:\"main.loc\";s:9:\"server_ip\";s:12:\"172.16.238.1\";s:9:\"is_active\";b:1;s:9:\"timestamp\";i:1586395381;s:7:\"version\";s:6:\"4.12.0\";}s:15:\"prev_is_premium\";b:0;}}s:13:\"file_slug_map\";a:1:{s:31:\"event-tickets/event-tickets.php\";s:13:\"event-tickets\";}s:7:\"plugins\";a:1:{s:13:\"event-tickets\";O:9:\"FS_Plugin\":23:{s:16:\"parent_plugin_id\";N;s:5:\"title\";s:13:\"Event Tickets\";s:4:\"slug\";s:13:\"event-tickets\";s:12:\"premium_slug\";s:21:\"event-tickets-premium\";s:4:\"type\";s:6:\"plugin\";s:20:\"affiliate_moderation\";b:0;s:19:\"is_wp_org_compliant\";b:1;s:22:\"premium_releases_count\";N;s:4:\"file\";s:31:\"event-tickets/event-tickets.php\";s:7:\"version\";s:6:\"4.12.0\";s:11:\"auto_update\";N;s:4:\"info\";N;s:10:\"is_premium\";b:0;s:14:\"premium_suffix\";s:9:\"(Premium)\";s:7:\"is_live\";b:1;s:9:\"bundle_id\";N;s:17:\"bundle_public_key\";N;s:10:\"public_key\";s:32:\"pk_6dd9310b57c62871c59e58b8e739e\";s:10:\"secret_key\";N;s:2:\"id\";s:4:\"3841\";s:7:\"updated\";N;s:7:\"created\";N;s:22:\"\0FS_Entity\0_is_updated\";b:1;}}s:9:\"unique_id\";s:32:\"78b69501cc592dd8ec16180286bc2604\";}','yes'),(142,'fs_gdpr','a:1:{s:2:\"u0\";a:1:{s:8:\"required\";b:0;}}','yes'),(145,'tribe_last_updated_option','1734032327.1325','yes'),(146,'stellar_schema_version_tec-slr-maps','1.0.0','yes'),(147,'stellar_schema_version_tec-slr-layouts','1.0.0','yes'),(148,'stellar_schema_version_tec-slr-seat-types','1.0.0','yes'),(149,'stellar_schema_version_tec-slr-sessions','1.1.0','yes'),(150,'tec_freemius_accounts_archive','s:1763:\"a:5:{s:21:\"id_slug_type_path_map\";a:1:{i:3841;a:3:{s:4:\"slug\";s:13:\"event-tickets\";s:4:\"type\";s:6:\"plugin\";s:4:\"path\";s:31:\"event-tickets/event-tickets.php\";}}s:11:\"plugin_data\";a:1:{s:13:\"event-tickets\";a:15:{s:16:\"plugin_main_file\";O:8:\"stdClass\":1:{s:4:\"path\";s:31:\"event-tickets/event-tickets.php\";}s:20:\"is_network_activated\";b:0;s:17:\"install_timestamp\";i:1586395381;s:17:\"was_plugin_loaded\";b:1;s:21:\"is_plugin_new_install\";b:0;s:16:\"sdk_last_version\";N;s:11:\"sdk_version\";s:5:\"2.3.2\";s:16:\"sdk_upgrade_mode\";b:1;s:18:\"sdk_downgrade_mode\";b:0;s:19:\"plugin_last_version\";N;s:14:\"plugin_version\";s:6:\"4.12.0\";s:19:\"plugin_upgrade_mode\";b:1;s:21:\"plugin_downgrade_mode\";b:0;s:17:\"connectivity_test\";a:6:{s:12:\"is_connected\";b:1;s:4:\"host\";s:8:\"main.loc\";s:9:\"server_ip\";s:12:\"172.16.238.1\";s:9:\"is_active\";b:1;s:9:\"timestamp\";i:1586395381;s:7:\"version\";s:6:\"4.12.0\";}s:15:\"prev_is_premium\";b:0;}}s:13:\"file_slug_map\";a:1:{s:31:\"event-tickets/event-tickets.php\";s:13:\"event-tickets\";}s:7:\"plugins\";a:1:{s:13:\"event-tickets\";O:9:\"FS_Plugin\":23:{s:16:\"parent_plugin_id\";N;s:5:\"title\";s:13:\"Event Tickets\";s:4:\"slug\";s:13:\"event-tickets\";s:12:\"premium_slug\";s:21:\"event-tickets-premium\";s:4:\"type\";s:6:\"plugin\";s:20:\"affiliate_moderation\";b:0;s:19:\"is_wp_org_compliant\";b:1;s:22:\"premium_releases_count\";N;s:4:\"file\";s:31:\"event-tickets/event-tickets.php\";s:7:\"version\";s:6:\"4.12.0\";s:11:\"auto_update\";N;s:4:\"info\";N;s:10:\"is_premium\";b:0;s:14:\"premium_suffix\";s:9:\"(Premium)\";s:7:\"is_live\";b:1;s:9:\"bundle_id\";N;s:17:\"bundle_public_key\";N;s:10:\"public_key\";s:32:\"pk_6dd9310b57c62871c59e58b8e739e\";s:10:\"secret_key\";N;s:2:\"id\";s:4:\"3841\";s:7:\"updated\";N;s:7:\"created\";N;s:22:\"\0FS_Entity\0_is_updated\";b:1;}}s:9:\"unique_id\";s:32:\"78b69501cc592dd8ec16180286bc2604\";}\";','yes'),(151,'tec_freemius_accounts_data_archive','a:5:{s:21:\"id_slug_type_path_map\";a:1:{i:3841;a:3:{s:4:\"slug\";s:13:\"event-tickets\";s:4:\"type\";s:6:\"plugin\";s:4:\"path\";s:31:\"event-tickets/event-tickets.php\";}}s:11:\"plugin_data\";a:1:{s:13:\"event-tickets\";a:15:{s:16:\"plugin_main_file\";O:8:\"stdClass\":1:{s:4:\"path\";s:31:\"event-tickets/event-tickets.php\";}s:20:\"is_network_activated\";b:0;s:17:\"install_timestamp\";i:1586395381;s:17:\"was_plugin_loaded\";b:1;s:21:\"is_plugin_new_install\";b:0;s:16:\"sdk_last_version\";N;s:11:\"sdk_version\";s:5:\"2.3.2\";s:16:\"sdk_upgrade_mode\";b:1;s:18:\"sdk_downgrade_mode\";b:0;s:19:\"plugin_last_version\";N;s:14:\"plugin_version\";s:6:\"4.12.0\";s:19:\"plugin_upgrade_mode\";b:1;s:21:\"plugin_downgrade_mode\";b:0;s:17:\"connectivity_test\";a:6:{s:12:\"is_connected\";b:1;s:4:\"host\";s:8:\"main.loc\";s:9:\"server_ip\";s:12:\"172.16.238.1\";s:9:\"is_active\";b:1;s:9:\"timestamp\";i:1586395381;s:7:\"version\";s:6:\"4.12.0\";}s:15:\"prev_is_premium\";b:0;}}s:13:\"file_slug_map\";a:1:{s:31:\"event-tickets/event-tickets.php\";s:13:\"event-tickets\";}s:7:\"plugins\";a:1:{s:13:\"event-tickets\";a:24:{s:10:\"tec_fs_key\";s:9:\"FS_Plugin\";s:16:\"parent_plugin_id\";N;s:5:\"title\";s:13:\"Event Tickets\";s:4:\"slug\";s:13:\"event-tickets\";s:12:\"premium_slug\";s:21:\"event-tickets-premium\";s:4:\"type\";s:6:\"plugin\";s:20:\"affiliate_moderation\";b:0;s:19:\"is_wp_org_compliant\";b:1;s:22:\"premium_releases_count\";N;s:4:\"file\";s:31:\"event-tickets/event-tickets.php\";s:7:\"version\";s:6:\"4.12.0\";s:11:\"auto_update\";N;s:4:\"info\";N;s:10:\"is_premium\";b:0;s:14:\"premium_suffix\";s:9:\"(Premium)\";s:7:\"is_live\";b:1;s:9:\"bundle_id\";N;s:17:\"bundle_public_key\";N;s:10:\"public_key\";s:32:\"pk_6dd9310b57c62871c59e58b8e739e\";s:10:\"secret_key\";N;s:2:\"id\";s:4:\"3841\";s:7:\"updated\";N;s:7:\"created\";N;s:22:\"\0FS_Entity\0_is_updated\";b:1;}}s:9:\"unique_id\";s:32:\"78b69501cc592dd8ec16180286bc2604\";}','yes'),(152,'stellarwp_telemetry','a:1:{s:7:\"plugins\";a:1:{s:13:\"event-tickets\";a:2:{s:7:\"wp_slug\";s:31:\"event-tickets/event-tickets.php\";s:5:\"optin\";b:0;}}}','yes'),(153,'stellarwp_telemetry_event-tickets_show_optin','0','yes'),(154,'tec_freemius_plugins_archive','O:8:\"stdClass\":3:{s:7:\"plugins\";a:1:{s:36:\"event-tickets/common/vendor/freemius\";O:8:\"stdClass\":4:{s:7:\"version\";s:5:\"2.3.2\";s:4:\"type\";s:6:\"plugin\";s:9:\"timestamp\";i:1586395381;s:11:\"plugin_path\";s:31:\"event-tickets/event-tickets.php\";}}s:7:\"abspath\";s:26:\"/shared/httpd/main/htdocs/\";s:6:\"newest\";O:8:\"stdClass\":5:{s:11:\"plugin_path\";s:31:\"event-tickets/event-tickets.php\";s:8:\"sdk_path\";s:36:\"event-tickets/common/vendor/freemius\";s:7:\"version\";s:5:\"2.3.2\";s:13:\"in_activation\";b:0;s:9:\"timestamp\";i:1586395381;}}','yes'),(155,'widget_block','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(156,'stellarwp_telemetry_last_send','','yes'),(157,'_site_transient_timeout_theme_roots','1734034127','no'),(158,'_site_transient_theme_roots','a:5:{s:12:\"twentytwenty\";s:7:\"/themes\";s:16:\"twentytwentyfour\";s:7:\"/themes\";s:15:\"twentytwentyone\";s:7:\"/themes\";s:17:\"twentytwentythree\";s:7:\"/themes\";s:15:\"twentytwentytwo\";s:7:\"/themes\";}','no'),(159,'wp_force_deactivated_plugins','a:0:{}','yes'),(160,'tribe_last_save_post','1734032327.4614','yes'),(161,'tec_timed_tribe_supports_async_process','a:3:{s:3:\"key\";s:28:\"tribe_supports_async_process\";s:5:\"value\";i:1;s:10:\"expiration\";i:1734637336;}','yes'),(163,'_transient_timeout_dash_v2_88ae138922fe95674369b1cb3d215a2b','1734075527','no'),(164,'_transient_dash_v2_88ae138922fe95674369b1cb3d215a2b','<div class=\"rss-widget\"><p><strong>RSS Error:</strong> WP HTTP Error: Airplane Mode is enabled</p></div><div class=\"rss-widget\"><p><strong>RSS Error:</strong> WP HTTP Error: Airplane Mode is enabled</p></div>','no'),(168,'admin_email_lifespan','1749584536','yes'),(169,'disallowed_keys','','no'),(170,'comment_previously_approved','1','yes'),(171,'auto_plugin_theme_update_emails','a:0:{}','no'),(172,'auto_update_core_dev','enabled','yes'),(173,'auto_update_core_minor','enabled','yes'),(174,'auto_update_core_major','enabled','yes'),(175,'wp_attachment_pages_enabled','1','yes'),(176,'user_count','1','no');
/*!40000 ALTER TABLE `wp_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_postmeta`
--

DROP TABLE IF EXISTS `wp_postmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_postmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `post_id` (`post_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_postmeta`
--

LOCK TABLES `wp_postmeta` WRITE;
/*!40000 ALTER TABLE `wp_postmeta` DISABLE KEYS */;
INSERT INTO `wp_postmeta` VALUES (1,1,'_tribe_hide_attendees_list',''),(2,1,'_tribe_modified_fields','a:53:{s:26:\"_tribe_hide_attendees_list\";i:1590007458;s:12:\"_EventOrigin\";i:1590007458;s:22:\"_tribe_ticket_capacity\";i:1590007569;s:21:\"_tribe_ticket_version\";i:1596220548;s:10:\"post_title\";i:1590007568;s:12:\"post_content\";i:1596053375;s:11:\"post_status\";i:1590007568;s:6:\"_price\";i:1590007569;s:6:\"_stock\";i:1590007569;s:20:\"_tribe_ticket_header\";i:1590504899;s:30:\"_tribe_default_ticket_provider\";i:1590007569;s:18:\"_ticket_start_date\";i:1590007569;s:16:\"_ticket_end_date\";i:1590007569;s:30:\"_tribe_ticket_show_description\";i:1590007569;s:28:\"_tribe_ticket_show_not_going\";i:1590007569;s:21:\"_tribe_rsvp_for_event\";i:1590007569;s:12:\"_EventAllDay\";i:1590504899;s:14:\"_EventTimezone\";i:1590007569;s:15:\"_EventStartDate\";i:1596207198;s:13:\"_EventEndDate\";i:1596207198;s:18:\"_EventStartDateUTC\";i:1596207198;s:16:\"_EventEndDateUTC\";i:1596207198;s:13:\"_EventShowMap\";i:1590007569;s:17:\"_EventShowMapLink\";i:1590007569;s:9:\"_EventURL\";i:1590007569;s:10:\"_EventCost\";i:1596823897;s:21:\"_EventCostDescription\";i:1590007569;s:20:\"_EventCurrencySymbol\";i:1590007569;s:22:\"_EventCurrencyPosition\";i:1590007569;s:23:\"_EventDateTimeSeparator\";i:1590007569;s:24:\"_EventTimeRangeSeparator\";i:1590007569;s:13:\"_EventVenueID\";i:1590007569;s:15:\"_OrganizerEmail\";i:1590007569;s:15:\"_OrganizerPhone\";i:1590007569;s:17:\"_OrganizerWebsite\";i:1590007569;s:13:\"_VenueAddress\";i:1590007569;s:10:\"_VenueCity\";i:1590007569;s:13:\"_VenueCountry\";i:1590007569;s:14:\"_VenueProvince\";i:1590007569;s:9:\"_VenueZip\";i:1590007569;s:11:\"_VenuePhone\";i:1590007569;s:9:\"_VenueURL\";i:1590007569;s:19:\"_VenueStateProvince\";i:1590007569;s:9:\"_VenueLat\";i:1590007569;s:9:\"_VenueLng\";i:1590007569;s:10:\"_edit_last\";i:1590007570;s:27:\"_tribe_tickets_flush_blocks\";i:1596053313;s:14:\"_EventDuration\";i:1590504899;s:18:\"_EventTimezoneAbbr\";i:1590504899;s:16:\"_tribe_post_root\";i:1590505303;s:40:\"_tribe_progressive_ticket_current_number\";i:1597179090;s:15:\"_tribe_featured\";i:1594825160;s:16:\"_EventRecurrence\";i:1593528498;}'),(3,1,'_EventOrigin','events-calendar'),(4,1,'_tribe_ticket_capacity',''),(5,1,'_tribe_ticket_version','4.12.3'),(6,1,'_edit_lock','1597849899:1'),(7,1,'_price',''),(8,1,'_stock',''),(9,1,'_tribe_default_ticket_provider','Tribe__Tickets_Plus__Commerce__WooCommerce__Main'),(10,1,'_ticket_start_date',''),(11,1,'_ticket_end_date',''),(12,1,'_tribe_ticket_show_description',''),(13,1,'_tribe_ticket_show_not_going',''),(14,1,'_tribe_rsvp_for_event',''),(15,1,'_EventTimezone','UTC'),(16,1,'_EventStartDate','2024-12-19 08:00:00'),(17,1,'_EventEndDate','2024-12-19 17:00:00'),(18,1,'_EventStartDateUTC','2024-12-19 08:00:00'),(19,1,'_EventEndDateUTC','2024-12-19 17:00:00'),(20,1,'_EventShowMap','1'),(21,1,'_EventShowMapLink','1'),(22,1,'_EventURL',''),(23,1,'_EventCostDescription',''),(24,1,'_EventCurrencySymbol','$'),(25,1,'_EventCurrencyPosition','prefix'),(26,1,'_EventDateTimeSeparator','@'),(27,1,'_EventTimeRangeSeparator','-'),(28,1,'_EventVenueID','0'),(29,1,'_OrganizerEmail',''),(30,1,'_OrganizerPhone',''),(31,1,'_OrganizerWebsite',''),(32,1,'_VenueAddress',''),(33,1,'_VenueCity',''),(34,1,'_VenueCountry',''),(35,1,'_VenueProvince',''),(36,1,'_VenueZip',''),(37,1,'_VenuePhone',''),(38,1,'_VenueURL',''),(39,1,'_VenueStateProvince',''),(40,1,'_VenueLat',''),(41,1,'_VenueLng',''),(42,1,'_edit_last','1'),(43,1,'_EventDuration','32400'),(44,1,'_EventTimezoneAbbr','UTC'),(45,1,'_tribe_post_root','RSVPTEST-'),(46,1,'_tribe_progressive_ticket_current_number','16'),(47,1,'_EventRecurrence','a:3:{s:5:\"rules\";a:0:{}s:10:\"exclusions\";a:0:{}s:11:\"description\";N;}'),(48,1,'_tribe_tickets_flush_blocks','1'),(49,1,'_EventCost','0'),(50,4,'_tribe_rsvp_for_event','1'),(51,4,'_tribe_ticket_show_description','yes'),(52,4,'_price','0'),(53,4,'_manage_stock','yes'),(54,4,'_stock','100'),(55,4,'_tribe_ticket_capacity','100'),(56,4,'_tribe_ticket_version','5.0.0'),(57,4,'_tribe_ticket_show_not_going','1'),(58,4,'_tribe_tickets_meta','a:0:{}'),(59,4,'_ticket_start_date','2024-12-05 00:00:00'),(60,4,'_ticket_end_date','2024-12-26 00:00:00'),(61,1,'_tribe_hide_attendees_list',''),(62,1,'_tribe_modified_fields','a:53:{s:26:\"_tribe_hide_attendees_list\";i:1590007458;s:12:\"_EventOrigin\";i:1590007458;s:22:\"_tribe_ticket_capacity\";i:1590007569;s:21:\"_tribe_ticket_version\";i:1596220548;s:10:\"post_title\";i:1590007568;s:12:\"post_content\";i:1596053375;s:11:\"post_status\";i:1590007568;s:6:\"_price\";i:1590007569;s:6:\"_stock\";i:1590007569;s:20:\"_tribe_ticket_header\";i:1590504899;s:30:\"_tribe_default_ticket_provider\";i:1590007569;s:18:\"_ticket_start_date\";i:1590007569;s:16:\"_ticket_end_date\";i:1590007569;s:30:\"_tribe_ticket_show_description\";i:1590007569;s:28:\"_tribe_ticket_show_not_going\";i:1590007569;s:21:\"_tribe_rsvp_for_event\";i:1590007569;s:12:\"_EventAllDay\";i:1590504899;s:14:\"_EventTimezone\";i:1590007569;s:15:\"_EventStartDate\";i:1596207198;s:13:\"_EventEndDate\";i:1596207198;s:18:\"_EventStartDateUTC\";i:1596207198;s:16:\"_EventEndDateUTC\";i:1596207198;s:13:\"_EventShowMap\";i:1590007569;s:17:\"_EventShowMapLink\";i:1590007569;s:9:\"_EventURL\";i:1590007569;s:10:\"_EventCost\";i:1596823897;s:21:\"_EventCostDescription\";i:1590007569;s:20:\"_EventCurrencySymbol\";i:1590007569;s:22:\"_EventCurrencyPosition\";i:1590007569;s:23:\"_EventDateTimeSeparator\";i:1590007569;s:24:\"_EventTimeRangeSeparator\";i:1590007569;s:13:\"_EventVenueID\";i:1590007569;s:15:\"_OrganizerEmail\";i:1590007569;s:15:\"_OrganizerPhone\";i:1590007569;s:17:\"_OrganizerWebsite\";i:1590007569;s:13:\"_VenueAddress\";i:1590007569;s:10:\"_VenueCity\";i:1590007569;s:13:\"_VenueCountry\";i:1590007569;s:14:\"_VenueProvince\";i:1590007569;s:9:\"_VenueZip\";i:1590007569;s:11:\"_VenuePhone\";i:1590007569;s:9:\"_VenueURL\";i:1590007569;s:19:\"_VenueStateProvince\";i:1590007569;s:9:\"_VenueLat\";i:1590007569;s:9:\"_VenueLng\";i:1590007569;s:10:\"_edit_last\";i:1590007570;s:27:\"_tribe_tickets_flush_blocks\";i:1596053313;s:14:\"_EventDuration\";i:1590504899;s:18:\"_EventTimezoneAbbr\";i:1590504899;s:16:\"_tribe_post_root\";i:1590505303;s:40:\"_tribe_progressive_ticket_current_number\";i:1597179090;s:15:\"_tribe_featured\";i:1594825160;s:16:\"_EventRecurrence\";i:1593528498;}'),(63,1,'_EventOrigin','events-calendar'),(64,1,'_tribe_ticket_capacity',''),(65,1,'_tribe_ticket_version','4.12.3'),(66,1,'_edit_lock','1597849899:1'),(67,1,'_price',''),(68,1,'_stock',''),(69,1,'_tribe_default_ticket_provider','Tribe__Tickets_Plus__Commerce__WooCommerce__Main'),(70,1,'_ticket_start_date',''),(71,1,'_ticket_end_date',''),(72,1,'_tribe_ticket_show_description',''),(73,1,'_tribe_ticket_show_not_going',''),(74,1,'_tribe_rsvp_for_event',''),(75,1,'_EventTimezone','UTC'),(76,1,'_EventStartDate','2024-12-19 08:00:00'),(77,1,'_EventEndDate','2024-12-19 17:00:00'),(78,1,'_EventStartDateUTC','2024-12-19 08:00:00'),(79,1,'_EventEndDateUTC','2024-12-19 17:00:00'),(80,1,'_EventShowMap','1'),(81,1,'_EventShowMapLink','1'),(82,1,'_EventURL',''),(83,1,'_EventCostDescription',''),(84,1,'_EventCurrencySymbol','$'),(85,1,'_EventCurrencyPosition','prefix'),(86,1,'_EventDateTimeSeparator','@'),(87,1,'_EventTimeRangeSeparator','-'),(88,1,'_EventVenueID','0'),(89,1,'_OrganizerEmail',''),(90,1,'_OrganizerPhone',''),(91,1,'_OrganizerWebsite',''),(92,1,'_VenueAddress',''),(93,1,'_VenueCity',''),(94,1,'_VenueCountry',''),(95,1,'_VenueProvince',''),(96,1,'_VenueZip',''),(97,1,'_VenuePhone',''),(98,1,'_VenueURL',''),(99,1,'_VenueStateProvince',''),(100,1,'_VenueLat',''),(101,1,'_VenueLng',''),(102,1,'_edit_last','1'),(103,1,'_EventDuration','32400'),(104,1,'_EventTimezoneAbbr','UTC'),(105,1,'_tribe_post_root','RSVPTEST-'),(106,1,'_tribe_progressive_ticket_current_number','16'),(107,1,'_EventRecurrence','a:3:{s:5:\"rules\";a:0:{}s:10:\"exclusions\";a:0:{}s:11:\"description\";N;}'),(108,1,'_tribe_tickets_flush_blocks','1'),(109,1,'_EventCost','0'),(110,4,'_tribe_rsvp_for_event','1'),(111,4,'_tribe_ticket_show_description','yes'),(112,4,'_price','0'),(113,4,'_manage_stock','yes'),(114,4,'_stock','100'),(115,4,'_tribe_ticket_capacity','100'),(116,4,'_tribe_ticket_version','5.0.0'),(117,4,'_tribe_ticket_show_not_going','1'),(118,4,'_tribe_tickets_meta','a:0:{}'),(119,4,'_ticket_start_date','2024-12-05 00:00:00'),(120,4,'_ticket_end_date','2024-12-26 00:00:00');
/*!40000 ALTER TABLE `wp_postmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_posts`
--

DROP TABLE IF EXISTS `wp_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_posts` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_author` bigint(20) unsigned NOT NULL DEFAULT 0,
  `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content` longtext NOT NULL,
  `post_title` text NOT NULL,
  `post_excerpt` text NOT NULL,
  `post_status` varchar(20) NOT NULL DEFAULT 'publish',
  `comment_status` varchar(20) NOT NULL DEFAULT 'open',
  `ping_status` varchar(20) NOT NULL DEFAULT 'open',
  `post_password` varchar(255) NOT NULL DEFAULT '',
  `post_name` varchar(200) NOT NULL DEFAULT '',
  `to_ping` text NOT NULL,
  `pinged` text NOT NULL,
  `post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content_filtered` longtext NOT NULL,
  `post_parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `guid` varchar(255) NOT NULL DEFAULT '',
  `menu_order` int(11) NOT NULL DEFAULT 0,
  `post_type` varchar(20) NOT NULL DEFAULT 'post',
  `post_mime_type` varchar(100) NOT NULL DEFAULT '',
  `comment_count` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ID`),
  KEY `post_name` (`post_name`(191)),
  KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`),
  KEY `post_parent` (`post_parent`),
  KEY `post_author` (`post_author`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_posts`
--

LOCK TABLES `wp_posts` WRITE;
/*!40000 ALTER TABLE `wp_posts` DISABLE KEYS */;
INSERT INTO `wp_posts` VALUES (1,1,'2020-05-20 20:46:08','2020-05-20 20:46:08','<!-- wp:tribe/event-datetime /-->\r\n\r\n<!-- wp:paragraph {\"placeholder\":\"Add Description...\"} -->\r\n<p>Lorem ipsum dolor sit amet.</p>\r\n<!-- /wp:paragraph -->\r\n\r\n<!-- wp:tribe/rsvp /-->\r\n\r\n<!-- wp:tribe/event-price /-->\r\n\r\n<!-- wp:tribe/event-organizer /-->\r\n\r\n<!-- wp:tribe/event-venue /-->\r\n\r\n<!-- wp:tribe/event-website /-->\r\n\r\n<!-- wp:tribe/event-links /-->\r\n\r\n<!-- wp:tribe/tickets -->\r\n<div class=\"wp-block-tribe-tickets\"> </div>\r\n<!-- /wp:tribe/tickets -->','RSVP Test','','publish','open','closed','','rsvp-test','','','2025-08-07 18:11:45','2025-08-07 18:11:45','',0,'http://wordpress.test/?post_type=tribe_events&#038;p=1',0,'tribe_events','',0),(2,1,'2018-05-08 07:57:13','2018-05-08 07:57:13','Welcome to <a href=\"http://wordpress.test/\">Test Sites</a>. This is your first post. Edit or delete it, then start blogging!','Hello world!','','publish','open','open','','hello-world','','','2018-05-08 07:57:13','2018-05-08 07:57:13','',0,'http://wordpress.test/test1/?p=1',0,'post','',1),(3,1,'2018-05-08 07:57:13','2018-05-08 07:57:13','This is an example page. It\'s different from a blog post because it will stay in one place and will show up in your site navigation (in most themes). Most people start with an About page that introduces them to potential site visitors. It might say something like this:\n\n<blockquote>Hi there! I\'m a bike messenger by day, aspiring actor by night, and this is my website. I live in Los Angeles, have a great dog named Jack, and I like pi&#241;a coladas. (And gettin\' caught in the rain.)</blockquote>\n\n...or something like this:\n\n<blockquote>The XYZ Doohickey Company was founded in 1971, and has been providing quality doohickeys to the public ever since. Located in Gotham City, XYZ employs over 2,000 people and does all kinds of awesome things for the Gotham community.</blockquote>\n\nAs a new WordPress user, you should go to <a href=\"http://wp.local/test1/wp-admin/\">your dashboard</a> to delete this page and create new pages for your content. Have fun!','Sample Page','','publish','closed','open','','sample-page','','','2018-05-08 07:57:13','2018-05-08 07:57:13','',0,'http://wp.local/test1/?page_id=2',0,'page','',0),(4,1,'2020-08-23 13:41:18','2020-08-23 13:41:18','','Job &amp; Career Fair','Lorem ipsum dolor sit amet, consectetur adipiscing elit','publish','closed','closed','','job-career-fair','','','2020-08-23 13:41:32','2020-08-23 13:41:32','',0,'http://wordpress.test/?post_type=tribe_rsvp_tickets&#038;p=4',5,'tribe_rsvp_tickets','',0),(5,1,'2024-12-12 19:38:47','0000-00-00 00:00:00','','Auto Draft','','auto-draft','open','open','','','','','2024-12-12 19:38:47','0000-00-00 00:00:00','',0,'http://wordpress.test/?p=5',0,'post','',0);
/*!40000 ALTER TABLE `wp_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_tec_slr_layouts`
--

DROP TABLE IF EXISTS `wp_tec_slr_layouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_tec_slr_layouts` (
  `id` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_date` datetime NOT NULL,
  `map` varchar(36) NOT NULL,
  `seats` int(11) NOT NULL DEFAULT 0,
  `screenshot_url` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_tec_slr_layouts`
--

LOCK TABLES `wp_tec_slr_layouts` WRITE;
/*!40000 ALTER TABLE `wp_tec_slr_layouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_tec_slr_layouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_tec_slr_maps`
--

DROP TABLE IF EXISTS `wp_tec_slr_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_tec_slr_maps` (
  `id` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `seats` int(11) NOT NULL DEFAULT 0,
  `screenshot_url` varchar(255) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_tec_slr_maps`
--

LOCK TABLES `wp_tec_slr_maps` WRITE;
/*!40000 ALTER TABLE `wp_tec_slr_maps` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_tec_slr_maps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_tec_slr_seat_types`
--

DROP TABLE IF EXISTS `wp_tec_slr_seat_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_tec_slr_seat_types` (
  `id` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `map` varchar(36) NOT NULL,
  `layout` varchar(36) NOT NULL,
  `seats` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_tec_slr_seat_types`
--

LOCK TABLES `wp_tec_slr_seat_types` WRITE;
/*!40000 ALTER TABLE `wp_tec_slr_seat_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_tec_slr_seat_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_tec_slr_sessions`
--

DROP TABLE IF EXISTS `wp_tec_slr_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_tec_slr_sessions` (
  `token` varchar(150) NOT NULL,
  `object_id` bigint(20) NOT NULL,
  `expiration` int(11) NOT NULL,
  `reservations` longblob DEFAULT NULL,
  `expiration_lock` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_tec_slr_sessions`
--

LOCK TABLES `wp_tec_slr_sessions` WRITE;
/*!40000 ALTER TABLE `wp_tec_slr_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_tec_slr_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_term_relationships`
--

DROP TABLE IF EXISTS `wp_term_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_term_relationships` (
  `object_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `term_taxonomy_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `term_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`object_id`,`term_taxonomy_id`),
  KEY `term_taxonomy_id` (`term_taxonomy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_term_relationships`
--

LOCK TABLES `wp_term_relationships` WRITE;
/*!40000 ALTER TABLE `wp_term_relationships` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_term_relationships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_term_taxonomy`
--

DROP TABLE IF EXISTS `wp_term_taxonomy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_term_taxonomy` (
  `term_taxonomy_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `taxonomy` varchar(32) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `count` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`term_taxonomy_id`),
  UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
  KEY `taxonomy` (`taxonomy`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_term_taxonomy`
--

LOCK TABLES `wp_term_taxonomy` WRITE;
/*!40000 ALTER TABLE `wp_term_taxonomy` DISABLE KEYS */;
INSERT INTO `wp_term_taxonomy` VALUES (1,1,'category','',0,1);
/*!40000 ALTER TABLE `wp_term_taxonomy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_termmeta`
--

DROP TABLE IF EXISTS `wp_termmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_termmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `term_id` (`term_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_termmeta`
--

LOCK TABLES `wp_termmeta` WRITE;
/*!40000 ALTER TABLE `wp_termmeta` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_termmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_terms`
--

DROP TABLE IF EXISTS `wp_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_terms` (
  `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT '',
  `term_group` bigint(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`term_id`),
  KEY `slug` (`slug`(191)),
  KEY `name` (`name`(191))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_terms`
--

LOCK TABLES `wp_terms` WRITE;
/*!40000 ALTER TABLE `wp_terms` DISABLE KEYS */;
INSERT INTO `wp_terms` VALUES (1,'Uncategorized','uncategorized',0);
/*!40000 ALTER TABLE `wp_terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_usermeta`
--

DROP TABLE IF EXISTS `wp_usermeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_usermeta` (
  `umeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`umeta_id`),
  KEY `user_id` (`user_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_usermeta`
--

LOCK TABLES `wp_usermeta` WRITE;
/*!40000 ALTER TABLE `wp_usermeta` DISABLE KEYS */;
INSERT INTO `wp_usermeta` VALUES (1,1,'nickname','admin'),(2,1,'first_name',''),(3,1,'last_name',''),(4,1,'description',''),(5,1,'rich_editing','true'),(6,1,'syntax_highlighting','true'),(7,1,'comment_shortcuts','false'),(8,1,'admin_color','fresh'),(9,1,'use_ssl','0'),(10,1,'show_admin_bar_front','true'),(11,1,'locale',''),(12,1,'wp_capabilities','a:1:{s:13:\"administrator\";b:1;}'),(13,1,'wp_user_level','10'),(14,1,'dismissed_wp_pointers',''),(15,1,'show_welcome_panel','1'),(16,1,'session_tokens','a:2:{s:64:\"905eafaa6da0bf79337d806fc9b906ff8cf4327ae9f80116c40ff52822d35432\";a:4:{s:10:\"expiration\";i:1734205127;s:2:\"ip\";s:10:\"172.22.0.5\";s:2:\"ua\";s:12:\"tribe-tester\";s:5:\"login\";i:1734032327;}s:64:\"4cadfec309257ac98f0067d176c555e84820ccbb124d5cfc0a0f891eec68dc25\";a:4:{s:10:\"expiration\";i:1734205306;s:2:\"ip\";s:10:\"172.22.0.5\";s:2:\"ua\";s:12:\"tribe-tester\";s:5:\"login\";i:1734032506;}}'),(17,1,'wp_dashboard_quick_press_last_post_id','5');
/*!40000 ALTER TABLE `wp_usermeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_users`
--

DROP TABLE IF EXISTS `wp_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_users` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_login` varchar(60) NOT NULL DEFAULT '',
  `user_pass` varchar(255) NOT NULL DEFAULT '',
  `user_nicename` varchar(50) NOT NULL DEFAULT '',
  `user_email` varchar(100) NOT NULL DEFAULT '',
  `user_url` varchar(100) NOT NULL DEFAULT '',
  `user_registered` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_activation_key` varchar(255) NOT NULL DEFAULT '',
  `user_status` int(11) NOT NULL DEFAULT 0,
  `display_name` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `user_login_key` (`user_login`),
  KEY `user_nicename` (`user_nicename`),
  KEY `user_email` (`user_email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_users`
--

LOCK TABLES `wp_users` WRITE;
/*!40000 ALTER TABLE `wp_users` DISABLE KEYS */;
INSERT INTO `wp_users` VALUES (1,'admin','$P$B5bvjdcJ9LPqh23fLj9ZyTMUERYwoH.','admin','admin@tribe.local','','2018-04-24 11:10:01','',0,'admin');
/*!40000 ALTER TABLE `wp_users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-12-12 19:42:22
