/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.5.26-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: db    Database: test
-- ------------------------------------------------------
-- Server version	10.7.8-MariaDB-1:10.7.8+maria~ubu2004

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `test_commentmeta`
--

DROP TABLE IF EXISTS `test_commentmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_commentmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `comment_id` (`comment_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_commentmeta`
--

LOCK TABLES `test_commentmeta` WRITE;
/*!40000 ALTER TABLE `test_commentmeta` DISABLE KEYS */;
/*!40000 ALTER TABLE `test_commentmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_comments`
--

DROP TABLE IF EXISTS `test_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_comments` (
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_comments`
--

LOCK TABLES `test_comments` WRITE;
/*!40000 ALTER TABLE `test_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `test_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_links`
--

DROP TABLE IF EXISTS `test_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_links` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_links`
--

LOCK TABLES `test_links` WRITE;
/*!40000 ALTER TABLE `test_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `test_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_options`
--

DROP TABLE IF EXISTS `test_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_options` (
  `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `option_name` varchar(191) NOT NULL DEFAULT '',
  `option_value` longtext NOT NULL,
  `autoload` varchar(20) NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`option_id`),
  UNIQUE KEY `option_name` (`option_name`),
  KEY `autoload` (`autoload`)
) ENGINE=InnoDB AUTO_INCREMENT=188 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_options`
--

LOCK TABLES `test_options` WRITE;
/*!40000 ALTER TABLE `test_options` DISABLE KEYS */;
INSERT INTO `test_options` VALUES (2,'siteurl','http://wordpress.test','yes'),(3,'home','http://wordpress.test','yes'),(4,'blogname','Event Tickets Tests','yes'),(5,'blogdescription','','yes'),(6,'users_can_register','0','yes'),(7,'admin_email','admin@wordpress.test','yes'),(8,'start_of_week','1','yes'),(9,'use_balanceTags','0','yes'),(10,'use_smilies','1','yes'),(11,'require_name_email','1','yes'),(12,'comments_notify','1','yes'),(13,'posts_per_rss','10','yes'),(14,'rss_use_excerpt','0','yes'),(15,'mailserver_url','mail.example.com','yes'),(16,'mailserver_login','login@example.com','yes'),(17,'mailserver_pass','password','yes'),(18,'mailserver_port','110','yes'),(19,'default_category','1','yes'),(20,'default_comment_status','open','yes'),(21,'default_ping_status','open','yes'),(22,'default_pingback_flag','1','yes'),(23,'posts_per_page','10','yes'),(24,'date_format','F j, Y','yes'),(25,'time_format','g:i a','yes'),(26,'links_updated_date_format','F j, Y g:i a','yes'),(27,'comment_moderation','0','yes'),(28,'moderation_notify','1','yes'),(29,'rewrite_rules','','yes'),(30,'hack_file','0','yes'),(31,'blog_charset','UTF-8','yes'),(32,'moderation_keys','','no'),(33,'active_plugins','a:2:{i:0;s:31:\"event-tickets/event-tickets.php\";i:1;s:43:\"the-events-calendar/the-events-calendar.php\";}','yes'),(34,'category_base','','yes'),(35,'ping_sites','http://rpc.pingomatic.com/','yes'),(36,'comment_max_links','2','yes'),(37,'gmt_offset','0','yes'),(38,'default_email_category','1','yes'),(39,'recently_edited','','no'),(40,'template','twentytwentyfour','yes'),(41,'stylesheet','twentytwentyfour','yes'),(42,'comment_registration','0','yes'),(43,'html_type','text/html','yes'),(44,'use_trackback','0','yes'),(45,'default_role','subscriber','yes'),(46,'db_version','56657','yes'),(47,'uploads_use_yearmonth_folders','1','yes'),(48,'upload_path','','yes'),(49,'blog_public','1','yes'),(50,'default_link_category','2','yes'),(51,'show_on_front','posts','yes'),(52,'tag_base','','yes'),(53,'show_avatars','1','yes'),(54,'avatar_rating','G','yes'),(55,'upload_url_path','','yes'),(56,'thumbnail_size_w','150','yes'),(57,'thumbnail_size_h','150','yes'),(58,'thumbnail_crop','1','yes'),(59,'medium_size_w','300','yes'),(60,'medium_size_h','300','yes'),(61,'avatar_default','mystery','yes'),(62,'large_size_w','1024','yes'),(63,'large_size_h','1024','yes'),(64,'image_default_link_type','none','yes'),(65,'image_default_size','','yes'),(66,'image_default_align','','yes'),(67,'close_comments_for_old_posts','0','yes'),(68,'close_comments_days_old','14','yes'),(69,'thread_comments','1','yes'),(70,'thread_comments_depth','5','yes'),(71,'page_comments','0','yes'),(72,'comments_per_page','50','yes'),(73,'default_comments_page','newest','yes'),(74,'comment_order','asc','yes'),(75,'sticky_posts','a:0:{}','yes'),(76,'widget_categories','a:0:{}','yes'),(77,'widget_text','a:0:{}','yes'),(78,'widget_rss','a:0:{}','yes'),(79,'uninstall_plugins','a:0:{}','no'),(80,'timezone_string','','yes'),(81,'page_for_posts','0','yes'),(82,'page_on_front','0','yes'),(83,'default_post_format','0','yes'),(84,'link_manager_enabled','0','yes'),(85,'finished_splitting_shared_terms','1','yes'),(86,'site_icon','0','yes'),(87,'medium_large_size_w','768','yes'),(88,'medium_large_size_h','0','yes'),(89,'wp_page_for_privacy_policy','3','yes'),(90,'show_comments_cookies_opt_in','1','yes'),(91,'admin_email_lifespan','1736260011','yes'),(92,'disallowed_keys','','no'),(93,'comment_previously_approved','1','yes'),(94,'auto_plugin_theme_update_emails','a:0:{}','no'),(95,'auto_update_core_dev','enabled','yes'),(96,'auto_update_core_minor','enabled','yes'),(97,'auto_update_core_major','enabled','yes'),(98,'wp_force_deactivated_plugins','a:0:{}','yes'),(99,'wp_attachment_pages_enabled','0','yes'),(100,'initial_db_version','56657','yes'),(101,'test_user_roles','a:5:{s:13:\"administrator\";a:2:{s:4:\"name\";s:13:\"Administrator\";s:12:\"capabilities\";a:101:{s:13:\"switch_themes\";b:1;s:11:\"edit_themes\";b:1;s:16:\"activate_plugins\";b:1;s:12:\"edit_plugins\";b:1;s:10:\"edit_users\";b:1;s:10:\"edit_files\";b:1;s:14:\"manage_options\";b:1;s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:6:\"import\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:8:\"level_10\";b:1;s:7:\"level_9\";b:1;s:7:\"level_8\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;s:12:\"delete_users\";b:1;s:12:\"create_users\";b:1;s:17:\"unfiltered_upload\";b:1;s:14:\"edit_dashboard\";b:1;s:14:\"update_plugins\";b:1;s:14:\"delete_plugins\";b:1;s:15:\"install_plugins\";b:1;s:13:\"update_themes\";b:1;s:14:\"install_themes\";b:1;s:11:\"update_core\";b:1;s:10:\"list_users\";b:1;s:12:\"remove_users\";b:1;s:13:\"promote_users\";b:1;s:18:\"edit_theme_options\";b:1;s:13:\"delete_themes\";b:1;s:6:\"export\";b:1;s:25:\"read_private_tribe_events\";b:1;s:17:\"edit_tribe_events\";b:1;s:24:\"edit_others_tribe_events\";b:1;s:25:\"edit_private_tribe_events\";b:1;s:27:\"edit_published_tribe_events\";b:1;s:19:\"delete_tribe_events\";b:1;s:26:\"delete_others_tribe_events\";b:1;s:27:\"delete_private_tribe_events\";b:1;s:29:\"delete_published_tribe_events\";b:1;s:20:\"publish_tribe_events\";b:1;s:25:\"read_private_tribe_venues\";b:1;s:17:\"edit_tribe_venues\";b:1;s:24:\"edit_others_tribe_venues\";b:1;s:25:\"edit_private_tribe_venues\";b:1;s:27:\"edit_published_tribe_venues\";b:1;s:19:\"delete_tribe_venues\";b:1;s:26:\"delete_others_tribe_venues\";b:1;s:27:\"delete_private_tribe_venues\";b:1;s:29:\"delete_published_tribe_venues\";b:1;s:20:\"publish_tribe_venues\";b:1;s:29:\"read_private_tribe_organizers\";b:1;s:21:\"edit_tribe_organizers\";b:1;s:28:\"edit_others_tribe_organizers\";b:1;s:29:\"edit_private_tribe_organizers\";b:1;s:31:\"edit_published_tribe_organizers\";b:1;s:23:\"delete_tribe_organizers\";b:1;s:30:\"delete_others_tribe_organizers\";b:1;s:31:\"delete_private_tribe_organizers\";b:1;s:33:\"delete_published_tribe_organizers\";b:1;s:24:\"publish_tribe_organizers\";b:1;s:31:\"read_private_aggregator-records\";b:1;s:23:\"edit_aggregator-records\";b:1;s:30:\"edit_others_aggregator-records\";b:1;s:31:\"edit_private_aggregator-records\";b:1;s:33:\"edit_published_aggregator-records\";b:1;s:25:\"delete_aggregator-records\";b:1;s:32:\"delete_others_aggregator-records\";b:1;s:33:\"delete_private_aggregator-records\";b:1;s:35:\"delete_published_aggregator-records\";b:1;s:26:\"publish_aggregator-records\";b:1;}}s:6:\"editor\";a:2:{s:4:\"name\";s:6:\"Editor\";s:12:\"capabilities\";a:74:{s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;s:25:\"read_private_tribe_events\";b:1;s:17:\"edit_tribe_events\";b:1;s:24:\"edit_others_tribe_events\";b:1;s:25:\"edit_private_tribe_events\";b:1;s:27:\"edit_published_tribe_events\";b:1;s:19:\"delete_tribe_events\";b:1;s:26:\"delete_others_tribe_events\";b:1;s:27:\"delete_private_tribe_events\";b:1;s:29:\"delete_published_tribe_events\";b:1;s:20:\"publish_tribe_events\";b:1;s:25:\"read_private_tribe_venues\";b:1;s:17:\"edit_tribe_venues\";b:1;s:24:\"edit_others_tribe_venues\";b:1;s:25:\"edit_private_tribe_venues\";b:1;s:27:\"edit_published_tribe_venues\";b:1;s:19:\"delete_tribe_venues\";b:1;s:26:\"delete_others_tribe_venues\";b:1;s:27:\"delete_private_tribe_venues\";b:1;s:29:\"delete_published_tribe_venues\";b:1;s:20:\"publish_tribe_venues\";b:1;s:29:\"read_private_tribe_organizers\";b:1;s:21:\"edit_tribe_organizers\";b:1;s:28:\"edit_others_tribe_organizers\";b:1;s:29:\"edit_private_tribe_organizers\";b:1;s:31:\"edit_published_tribe_organizers\";b:1;s:23:\"delete_tribe_organizers\";b:1;s:30:\"delete_others_tribe_organizers\";b:1;s:31:\"delete_private_tribe_organizers\";b:1;s:33:\"delete_published_tribe_organizers\";b:1;s:24:\"publish_tribe_organizers\";b:1;s:31:\"read_private_aggregator-records\";b:1;s:23:\"edit_aggregator-records\";b:1;s:30:\"edit_others_aggregator-records\";b:1;s:31:\"edit_private_aggregator-records\";b:1;s:33:\"edit_published_aggregator-records\";b:1;s:25:\"delete_aggregator-records\";b:1;s:32:\"delete_others_aggregator-records\";b:1;s:33:\"delete_private_aggregator-records\";b:1;s:35:\"delete_published_aggregator-records\";b:1;s:26:\"publish_aggregator-records\";b:1;}}s:6:\"author\";a:2:{s:4:\"name\";s:6:\"Author\";s:12:\"capabilities\";a:30:{s:12:\"upload_files\";b:1;s:10:\"edit_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:17:\"edit_tribe_events\";b:1;s:27:\"edit_published_tribe_events\";b:1;s:19:\"delete_tribe_events\";b:1;s:29:\"delete_published_tribe_events\";b:1;s:20:\"publish_tribe_events\";b:1;s:17:\"edit_tribe_venues\";b:1;s:27:\"edit_published_tribe_venues\";b:1;s:19:\"delete_tribe_venues\";b:1;s:29:\"delete_published_tribe_venues\";b:1;s:20:\"publish_tribe_venues\";b:1;s:21:\"edit_tribe_organizers\";b:1;s:31:\"edit_published_tribe_organizers\";b:1;s:23:\"delete_tribe_organizers\";b:1;s:33:\"delete_published_tribe_organizers\";b:1;s:24:\"publish_tribe_organizers\";b:1;s:23:\"edit_aggregator-records\";b:1;s:33:\"edit_published_aggregator-records\";b:1;s:25:\"delete_aggregator-records\";b:1;s:35:\"delete_published_aggregator-records\";b:1;s:26:\"publish_aggregator-records\";b:1;}}s:11:\"contributor\";a:2:{s:4:\"name\";s:11:\"Contributor\";s:12:\"capabilities\";a:13:{s:10:\"edit_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;s:17:\"edit_tribe_events\";b:1;s:19:\"delete_tribe_events\";b:1;s:17:\"edit_tribe_venues\";b:1;s:19:\"delete_tribe_venues\";b:1;s:21:\"edit_tribe_organizers\";b:1;s:23:\"delete_tribe_organizers\";b:1;s:23:\"edit_aggregator-records\";b:1;s:25:\"delete_aggregator-records\";b:1;}}s:10:\"subscriber\";a:2:{s:4:\"name\";s:10:\"Subscriber\";s:12:\"capabilities\";a:2:{s:4:\"read\";b:1;s:7:\"level_0\";b:1;}}}','yes'),(102,'fresh_site','1','yes'),(103,'user_count','27','no'),(104,'widget_block','a:6:{i:2;a:1:{s:7:\"content\";s:19:\"<!-- wp:search /-->\";}i:3;a:1:{s:7:\"content\";s:154:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Recent Posts</h2><!-- /wp:heading --><!-- wp:latest-posts /--></div><!-- /wp:group -->\";}i:4;a:1:{s:7:\"content\";s:227:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Recent Comments</h2><!-- /wp:heading --><!-- wp:latest-comments {\"displayAvatar\":false,\"displayDate\":false,\"displayExcerpt\":false} /--></div><!-- /wp:group -->\";}i:5;a:1:{s:7:\"content\";s:146:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Archives</h2><!-- /wp:heading --><!-- wp:archives /--></div><!-- /wp:group -->\";}i:6;a:1:{s:7:\"content\";s:150:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Categories</h2><!-- /wp:heading --><!-- wp:categories /--></div><!-- /wp:group -->\";}s:12:\"_multiwidget\";i:1;}','yes'),(105,'sidebars_widgets','a:4:{s:19:\"wp_inactive_widgets\";a:0:{}s:9:\"sidebar-1\";a:3:{i:0;s:7:\"block-2\";i:1;s:7:\"block-3\";i:2;s:7:\"block-4\";}s:9:\"sidebar-2\";a:2:{i:0;s:7:\"block-5\";i:1;s:7:\"block-6\";}s:13:\"array_version\";i:3;}','yes'),(106,'cron','a:7:{i:1720708011;a:2:{s:30:\"tribe_schedule_transient_purge\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:16:\"tribe_daily_cron\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1720708012;a:1:{s:32:\"recovery_mode_clean_expired_keys\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1720708013;a:1:{s:26:\"tribe_tickets_migrate_4_12\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}}i:1720708014;a:4:{s:34:\"wp_privacy_delete_old_export_files\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}s:16:\"wp_version_check\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:17:\"wp_update_plugins\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:16:\"wp_update_themes\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1720794411;a:1:{s:24:\"tribe_common_log_cleanup\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1720794413;a:1:{s:30:\"wp_site_health_scheduled_check\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"weekly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:604800;}}}s:7:\"version\";i:2;}','yes'),(107,'tribe_last_updated_option','1720708055.6387','yes'),(108,'nonce_key',' ~n9]Y& {N7w<Cv~N8 koJ~_`Gx8K;F5z.A1o?G:j wm+`Y^-IRwZ$G{Bl`$=/CK','no'),(109,'nonce_salt','u-(|%|<Hx+_ah9J^rTU&`f94z5yEs!(Ajy)@@/=aA4qFOixw{#)+W[F>QkzUV2m!','no'),(110,'tribe_events_calendar_options','a:16:{s:8:\"did_init\";b:1;s:19:\"tribeEventsTemplate\";s:0:\"\";s:16:\"tribeEnableViews\";a:3:{i:0;s:4:\"list\";i:1;s:5:\"month\";i:2;s:3:\"day\";}s:10:\"viewOption\";s:4:\"list\";s:25:\"ticket-enabled-post-types\";a:2:{i:0;s:12:\"tribe_events\";i:1;s:4:\"post\";}s:14:\"schema-version\";s:7:\"6.5.1.4\";s:28:\"event-tickets-schema-version\";s:8:\"5.11.0.3\";s:21:\"previous_ecp_versions\";a:1:{i:0;s:1:\"0\";}s:18:\"latest_ecp_version\";s:7:\"6.5.1.4\";s:31:\"previous_event_tickets_versions\";a:0:{}s:28:\"latest_event_tickets_version\";s:8:\"5.11.0.3\";s:18:\"dateWithYearFormat\";s:6:\"F j, Y\";s:24:\"recurrenceMaxMonthsAfter\";i:24;s:22:\"google_maps_js_api_key\";s:39:\"AIzaSyDNsicAsP6-VuGtAb1O9riI3oc_NOb7IOU\";s:21:\"ticket-paypal-sandbox\";i:0;s:28:\"ticket-paypal-identity-token\";s:0:\"\";}','yes'),(113,'tribe_last_save_post','1720708055.6392','yes'),(114,'widget_pages','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(115,'widget_calendar','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(116,'widget_archives','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(117,'widget_media_audio','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(118,'widget_media_image','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(119,'widget_media_gallery','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(120,'widget_media_video','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(121,'widget_meta','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(122,'widget_search','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(123,'widget_recent-posts','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(124,'widget_recent-comments','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(125,'widget_tag_cloud','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(126,'widget_nav_menu','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(127,'widget_custom_html','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(128,'widget_tribe-widget-events-list','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(129,'_transient_wp_core_block_css_files','a:2:{s:7:\"version\";s:5:\"6.4.1\";s:5:\"files\";a:500:{i:0;s:23:\"archives/editor-rtl.css\";i:1;s:27:\"archives/editor-rtl.min.css\";i:2;s:19:\"archives/editor.css\";i:3;s:23:\"archives/editor.min.css\";i:4;s:22:\"archives/style-rtl.css\";i:5;s:26:\"archives/style-rtl.min.css\";i:6;s:18:\"archives/style.css\";i:7;s:22:\"archives/style.min.css\";i:8;s:20:\"audio/editor-rtl.css\";i:9;s:24:\"audio/editor-rtl.min.css\";i:10;s:16:\"audio/editor.css\";i:11;s:20:\"audio/editor.min.css\";i:12;s:19:\"audio/style-rtl.css\";i:13;s:23:\"audio/style-rtl.min.css\";i:14;s:15:\"audio/style.css\";i:15;s:19:\"audio/style.min.css\";i:16;s:19:\"audio/theme-rtl.css\";i:17;s:23:\"audio/theme-rtl.min.css\";i:18;s:15:\"audio/theme.css\";i:19;s:19:\"audio/theme.min.css\";i:20;s:21:\"avatar/editor-rtl.css\";i:21;s:25:\"avatar/editor-rtl.min.css\";i:22;s:17:\"avatar/editor.css\";i:23;s:21:\"avatar/editor.min.css\";i:24;s:20:\"avatar/style-rtl.css\";i:25;s:24:\"avatar/style-rtl.min.css\";i:26;s:16:\"avatar/style.css\";i:27;s:20:\"avatar/style.min.css\";i:28;s:20:\"block/editor-rtl.css\";i:29;s:24:\"block/editor-rtl.min.css\";i:30;s:16:\"block/editor.css\";i:31;s:20:\"block/editor.min.css\";i:32;s:21:\"button/editor-rtl.css\";i:33;s:25:\"button/editor-rtl.min.css\";i:34;s:17:\"button/editor.css\";i:35;s:21:\"button/editor.min.css\";i:36;s:20:\"button/style-rtl.css\";i:37;s:24:\"button/style-rtl.min.css\";i:38;s:16:\"button/style.css\";i:39;s:20:\"button/style.min.css\";i:40;s:22:\"buttons/editor-rtl.css\";i:41;s:26:\"buttons/editor-rtl.min.css\";i:42;s:18:\"buttons/editor.css\";i:43;s:22:\"buttons/editor.min.css\";i:44;s:21:\"buttons/style-rtl.css\";i:45;s:25:\"buttons/style-rtl.min.css\";i:46;s:17:\"buttons/style.css\";i:47;s:21:\"buttons/style.min.css\";i:48;s:22:\"calendar/style-rtl.css\";i:49;s:26:\"calendar/style-rtl.min.css\";i:50;s:18:\"calendar/style.css\";i:51;s:22:\"calendar/style.min.css\";i:52;s:25:\"categories/editor-rtl.css\";i:53;s:29:\"categories/editor-rtl.min.css\";i:54;s:21:\"categories/editor.css\";i:55;s:25:\"categories/editor.min.css\";i:56;s:24:\"categories/style-rtl.css\";i:57;s:28:\"categories/style-rtl.min.css\";i:58;s:20:\"categories/style.css\";i:59;s:24:\"categories/style.min.css\";i:60;s:19:\"code/editor-rtl.css\";i:61;s:23:\"code/editor-rtl.min.css\";i:62;s:15:\"code/editor.css\";i:63;s:19:\"code/editor.min.css\";i:64;s:18:\"code/style-rtl.css\";i:65;s:22:\"code/style-rtl.min.css\";i:66;s:14:\"code/style.css\";i:67;s:18:\"code/style.min.css\";i:68;s:18:\"code/theme-rtl.css\";i:69;s:22:\"code/theme-rtl.min.css\";i:70;s:14:\"code/theme.css\";i:71;s:18:\"code/theme.min.css\";i:72;s:22:\"columns/editor-rtl.css\";i:73;s:26:\"columns/editor-rtl.min.css\";i:74;s:18:\"columns/editor.css\";i:75;s:22:\"columns/editor.min.css\";i:76;s:21:\"columns/style-rtl.css\";i:77;s:25:\"columns/style-rtl.min.css\";i:78;s:17:\"columns/style.css\";i:79;s:21:\"columns/style.min.css\";i:80;s:29:\"comment-content/style-rtl.css\";i:81;s:33:\"comment-content/style-rtl.min.css\";i:82;s:25:\"comment-content/style.css\";i:83;s:29:\"comment-content/style.min.css\";i:84;s:30:\"comment-template/style-rtl.css\";i:85;s:34:\"comment-template/style-rtl.min.css\";i:86;s:26:\"comment-template/style.css\";i:87;s:30:\"comment-template/style.min.css\";i:88;s:42:\"comments-pagination-numbers/editor-rtl.css\";i:89;s:46:\"comments-pagination-numbers/editor-rtl.min.css\";i:90;s:38:\"comments-pagination-numbers/editor.css\";i:91;s:42:\"comments-pagination-numbers/editor.min.css\";i:92;s:34:\"comments-pagination/editor-rtl.css\";i:93;s:38:\"comments-pagination/editor-rtl.min.css\";i:94;s:30:\"comments-pagination/editor.css\";i:95;s:34:\"comments-pagination/editor.min.css\";i:96;s:33:\"comments-pagination/style-rtl.css\";i:97;s:37:\"comments-pagination/style-rtl.min.css\";i:98;s:29:\"comments-pagination/style.css\";i:99;s:33:\"comments-pagination/style.min.css\";i:100;s:29:\"comments-title/editor-rtl.css\";i:101;s:33:\"comments-title/editor-rtl.min.css\";i:102;s:25:\"comments-title/editor.css\";i:103;s:29:\"comments-title/editor.min.css\";i:104;s:23:\"comments/editor-rtl.css\";i:105;s:27:\"comments/editor-rtl.min.css\";i:106;s:19:\"comments/editor.css\";i:107;s:23:\"comments/editor.min.css\";i:108;s:22:\"comments/style-rtl.css\";i:109;s:26:\"comments/style-rtl.min.css\";i:110;s:18:\"comments/style.css\";i:111;s:22:\"comments/style.min.css\";i:112;s:20:\"cover/editor-rtl.css\";i:113;s:24:\"cover/editor-rtl.min.css\";i:114;s:16:\"cover/editor.css\";i:115;s:20:\"cover/editor.min.css\";i:116;s:19:\"cover/style-rtl.css\";i:117;s:23:\"cover/style-rtl.min.css\";i:118;s:15:\"cover/style.css\";i:119;s:19:\"cover/style.min.css\";i:120;s:22:\"details/editor-rtl.css\";i:121;s:26:\"details/editor-rtl.min.css\";i:122;s:18:\"details/editor.css\";i:123;s:22:\"details/editor.min.css\";i:124;s:21:\"details/style-rtl.css\";i:125;s:25:\"details/style-rtl.min.css\";i:126;s:17:\"details/style.css\";i:127;s:21:\"details/style.min.css\";i:128;s:20:\"embed/editor-rtl.css\";i:129;s:24:\"embed/editor-rtl.min.css\";i:130;s:16:\"embed/editor.css\";i:131;s:20:\"embed/editor.min.css\";i:132;s:19:\"embed/style-rtl.css\";i:133;s:23:\"embed/style-rtl.min.css\";i:134;s:15:\"embed/style.css\";i:135;s:19:\"embed/style.min.css\";i:136;s:19:\"embed/theme-rtl.css\";i:137;s:23:\"embed/theme-rtl.min.css\";i:138;s:15:\"embed/theme.css\";i:139;s:19:\"embed/theme.min.css\";i:140;s:19:\"file/editor-rtl.css\";i:141;s:23:\"file/editor-rtl.min.css\";i:142;s:15:\"file/editor.css\";i:143;s:19:\"file/editor.min.css\";i:144;s:18:\"file/style-rtl.css\";i:145;s:22:\"file/style-rtl.min.css\";i:146;s:14:\"file/style.css\";i:147;s:18:\"file/style.min.css\";i:148;s:23:\"footnotes/style-rtl.css\";i:149;s:27:\"footnotes/style-rtl.min.css\";i:150;s:19:\"footnotes/style.css\";i:151;s:23:\"footnotes/style.min.css\";i:152;s:23:\"freeform/editor-rtl.css\";i:153;s:27:\"freeform/editor-rtl.min.css\";i:154;s:19:\"freeform/editor.css\";i:155;s:23:\"freeform/editor.min.css\";i:156;s:22:\"gallery/editor-rtl.css\";i:157;s:26:\"gallery/editor-rtl.min.css\";i:158;s:18:\"gallery/editor.css\";i:159;s:22:\"gallery/editor.min.css\";i:160;s:21:\"gallery/style-rtl.css\";i:161;s:25:\"gallery/style-rtl.min.css\";i:162;s:17:\"gallery/style.css\";i:163;s:21:\"gallery/style.min.css\";i:164;s:21:\"gallery/theme-rtl.css\";i:165;s:25:\"gallery/theme-rtl.min.css\";i:166;s:17:\"gallery/theme.css\";i:167;s:21:\"gallery/theme.min.css\";i:168;s:20:\"group/editor-rtl.css\";i:169;s:24:\"group/editor-rtl.min.css\";i:170;s:16:\"group/editor.css\";i:171;s:20:\"group/editor.min.css\";i:172;s:19:\"group/style-rtl.css\";i:173;s:23:\"group/style-rtl.min.css\";i:174;s:15:\"group/style.css\";i:175;s:19:\"group/style.min.css\";i:176;s:19:\"group/theme-rtl.css\";i:177;s:23:\"group/theme-rtl.min.css\";i:178;s:15:\"group/theme.css\";i:179;s:19:\"group/theme.min.css\";i:180;s:21:\"heading/style-rtl.css\";i:181;s:25:\"heading/style-rtl.min.css\";i:182;s:17:\"heading/style.css\";i:183;s:21:\"heading/style.min.css\";i:184;s:19:\"html/editor-rtl.css\";i:185;s:23:\"html/editor-rtl.min.css\";i:186;s:15:\"html/editor.css\";i:187;s:19:\"html/editor.min.css\";i:188;s:20:\"image/editor-rtl.css\";i:189;s:24:\"image/editor-rtl.min.css\";i:190;s:16:\"image/editor.css\";i:191;s:20:\"image/editor.min.css\";i:192;s:19:\"image/style-rtl.css\";i:193;s:23:\"image/style-rtl.min.css\";i:194;s:15:\"image/style.css\";i:195;s:19:\"image/style.min.css\";i:196;s:19:\"image/theme-rtl.css\";i:197;s:23:\"image/theme-rtl.min.css\";i:198;s:15:\"image/theme.css\";i:199;s:19:\"image/theme.min.css\";i:200;s:29:\"latest-comments/style-rtl.css\";i:201;s:33:\"latest-comments/style-rtl.min.css\";i:202;s:25:\"latest-comments/style.css\";i:203;s:29:\"latest-comments/style.min.css\";i:204;s:27:\"latest-posts/editor-rtl.css\";i:205;s:31:\"latest-posts/editor-rtl.min.css\";i:206;s:23:\"latest-posts/editor.css\";i:207;s:27:\"latest-posts/editor.min.css\";i:208;s:26:\"latest-posts/style-rtl.css\";i:209;s:30:\"latest-posts/style-rtl.min.css\";i:210;s:22:\"latest-posts/style.css\";i:211;s:26:\"latest-posts/style.min.css\";i:212;s:18:\"list/style-rtl.css\";i:213;s:22:\"list/style-rtl.min.css\";i:214;s:14:\"list/style.css\";i:215;s:18:\"list/style.min.css\";i:216;s:25:\"media-text/editor-rtl.css\";i:217;s:29:\"media-text/editor-rtl.min.css\";i:218;s:21:\"media-text/editor.css\";i:219;s:25:\"media-text/editor.min.css\";i:220;s:24:\"media-text/style-rtl.css\";i:221;s:28:\"media-text/style-rtl.min.css\";i:222;s:20:\"media-text/style.css\";i:223;s:24:\"media-text/style.min.css\";i:224;s:19:\"more/editor-rtl.css\";i:225;s:23:\"more/editor-rtl.min.css\";i:226;s:15:\"more/editor.css\";i:227;s:19:\"more/editor.min.css\";i:228;s:30:\"navigation-link/editor-rtl.css\";i:229;s:34:\"navigation-link/editor-rtl.min.css\";i:230;s:26:\"navigation-link/editor.css\";i:231;s:30:\"navigation-link/editor.min.css\";i:232;s:29:\"navigation-link/style-rtl.css\";i:233;s:33:\"navigation-link/style-rtl.min.css\";i:234;s:25:\"navigation-link/style.css\";i:235;s:29:\"navigation-link/style.min.css\";i:236;s:33:\"navigation-submenu/editor-rtl.css\";i:237;s:37:\"navigation-submenu/editor-rtl.min.css\";i:238;s:29:\"navigation-submenu/editor.css\";i:239;s:33:\"navigation-submenu/editor.min.css\";i:240;s:25:\"navigation/editor-rtl.css\";i:241;s:29:\"navigation/editor-rtl.min.css\";i:242;s:21:\"navigation/editor.css\";i:243;s:25:\"navigation/editor.min.css\";i:244;s:24:\"navigation/style-rtl.css\";i:245;s:28:\"navigation/style-rtl.min.css\";i:246;s:20:\"navigation/style.css\";i:247;s:24:\"navigation/style.min.css\";i:248;s:23:\"nextpage/editor-rtl.css\";i:249;s:27:\"nextpage/editor-rtl.min.css\";i:250;s:19:\"nextpage/editor.css\";i:251;s:23:\"nextpage/editor.min.css\";i:252;s:24:\"page-list/editor-rtl.css\";i:253;s:28:\"page-list/editor-rtl.min.css\";i:254;s:20:\"page-list/editor.css\";i:255;s:24:\"page-list/editor.min.css\";i:256;s:23:\"page-list/style-rtl.css\";i:257;s:27:\"page-list/style-rtl.min.css\";i:258;s:19:\"page-list/style.css\";i:259;s:23:\"page-list/style.min.css\";i:260;s:24:\"paragraph/editor-rtl.css\";i:261;s:28:\"paragraph/editor-rtl.min.css\";i:262;s:20:\"paragraph/editor.css\";i:263;s:24:\"paragraph/editor.min.css\";i:264;s:23:\"paragraph/style-rtl.css\";i:265;s:27:\"paragraph/style-rtl.min.css\";i:266;s:19:\"paragraph/style.css\";i:267;s:23:\"paragraph/style.min.css\";i:268;s:25:\"post-author/style-rtl.css\";i:269;s:29:\"post-author/style-rtl.min.css\";i:270;s:21:\"post-author/style.css\";i:271;s:25:\"post-author/style.min.css\";i:272;s:33:\"post-comments-form/editor-rtl.css\";i:273;s:37:\"post-comments-form/editor-rtl.min.css\";i:274;s:29:\"post-comments-form/editor.css\";i:275;s:33:\"post-comments-form/editor.min.css\";i:276;s:32:\"post-comments-form/style-rtl.css\";i:277;s:36:\"post-comments-form/style-rtl.min.css\";i:278;s:28:\"post-comments-form/style.css\";i:279;s:32:\"post-comments-form/style.min.css\";i:280;s:23:\"post-date/style-rtl.css\";i:281;s:27:\"post-date/style-rtl.min.css\";i:282;s:19:\"post-date/style.css\";i:283;s:23:\"post-date/style.min.css\";i:284;s:27:\"post-excerpt/editor-rtl.css\";i:285;s:31:\"post-excerpt/editor-rtl.min.css\";i:286;s:23:\"post-excerpt/editor.css\";i:287;s:27:\"post-excerpt/editor.min.css\";i:288;s:26:\"post-excerpt/style-rtl.css\";i:289;s:30:\"post-excerpt/style-rtl.min.css\";i:290;s:22:\"post-excerpt/style.css\";i:291;s:26:\"post-excerpt/style.min.css\";i:292;s:34:\"post-featured-image/editor-rtl.css\";i:293;s:38:\"post-featured-image/editor-rtl.min.css\";i:294;s:30:\"post-featured-image/editor.css\";i:295;s:34:\"post-featured-image/editor.min.css\";i:296;s:33:\"post-featured-image/style-rtl.css\";i:297;s:37:\"post-featured-image/style-rtl.min.css\";i:298;s:29:\"post-featured-image/style.css\";i:299;s:33:\"post-featured-image/style.min.css\";i:300;s:34:\"post-navigation-link/style-rtl.css\";i:301;s:38:\"post-navigation-link/style-rtl.min.css\";i:302;s:30:\"post-navigation-link/style.css\";i:303;s:34:\"post-navigation-link/style.min.css\";i:304;s:28:\"post-template/editor-rtl.css\";i:305;s:32:\"post-template/editor-rtl.min.css\";i:306;s:24:\"post-template/editor.css\";i:307;s:28:\"post-template/editor.min.css\";i:308;s:27:\"post-template/style-rtl.css\";i:309;s:31:\"post-template/style-rtl.min.css\";i:310;s:23:\"post-template/style.css\";i:311;s:27:\"post-template/style.min.css\";i:312;s:24:\"post-terms/style-rtl.css\";i:313;s:28:\"post-terms/style-rtl.min.css\";i:314;s:20:\"post-terms/style.css\";i:315;s:24:\"post-terms/style.min.css\";i:316;s:24:\"post-title/style-rtl.css\";i:317;s:28:\"post-title/style-rtl.min.css\";i:318;s:20:\"post-title/style.css\";i:319;s:24:\"post-title/style.min.css\";i:320;s:26:\"preformatted/style-rtl.css\";i:321;s:30:\"preformatted/style-rtl.min.css\";i:322;s:22:\"preformatted/style.css\";i:323;s:26:\"preformatted/style.min.css\";i:324;s:24:\"pullquote/editor-rtl.css\";i:325;s:28:\"pullquote/editor-rtl.min.css\";i:326;s:20:\"pullquote/editor.css\";i:327;s:24:\"pullquote/editor.min.css\";i:328;s:23:\"pullquote/style-rtl.css\";i:329;s:27:\"pullquote/style-rtl.min.css\";i:330;s:19:\"pullquote/style.css\";i:331;s:23:\"pullquote/style.min.css\";i:332;s:23:\"pullquote/theme-rtl.css\";i:333;s:27:\"pullquote/theme-rtl.min.css\";i:334;s:19:\"pullquote/theme.css\";i:335;s:23:\"pullquote/theme.min.css\";i:336;s:39:\"query-pagination-numbers/editor-rtl.css\";i:337;s:43:\"query-pagination-numbers/editor-rtl.min.css\";i:338;s:35:\"query-pagination-numbers/editor.css\";i:339;s:39:\"query-pagination-numbers/editor.min.css\";i:340;s:31:\"query-pagination/editor-rtl.css\";i:341;s:35:\"query-pagination/editor-rtl.min.css\";i:342;s:27:\"query-pagination/editor.css\";i:343;s:31:\"query-pagination/editor.min.css\";i:344;s:30:\"query-pagination/style-rtl.css\";i:345;s:34:\"query-pagination/style-rtl.min.css\";i:346;s:26:\"query-pagination/style.css\";i:347;s:30:\"query-pagination/style.min.css\";i:348;s:25:\"query-title/style-rtl.css\";i:349;s:29:\"query-title/style-rtl.min.css\";i:350;s:21:\"query-title/style.css\";i:351;s:25:\"query-title/style.min.css\";i:352;s:20:\"query/editor-rtl.css\";i:353;s:24:\"query/editor-rtl.min.css\";i:354;s:16:\"query/editor.css\";i:355;s:20:\"query/editor.min.css\";i:356;s:19:\"query/style-rtl.css\";i:357;s:23:\"query/style-rtl.min.css\";i:358;s:15:\"query/style.css\";i:359;s:19:\"query/style.min.css\";i:360;s:19:\"quote/style-rtl.css\";i:361;s:23:\"quote/style-rtl.min.css\";i:362;s:15:\"quote/style.css\";i:363;s:19:\"quote/style.min.css\";i:364;s:19:\"quote/theme-rtl.css\";i:365;s:23:\"quote/theme-rtl.min.css\";i:366;s:15:\"quote/theme.css\";i:367;s:19:\"quote/theme.min.css\";i:368;s:23:\"read-more/style-rtl.css\";i:369;s:27:\"read-more/style-rtl.min.css\";i:370;s:19:\"read-more/style.css\";i:371;s:23:\"read-more/style.min.css\";i:372;s:18:\"rss/editor-rtl.css\";i:373;s:22:\"rss/editor-rtl.min.css\";i:374;s:14:\"rss/editor.css\";i:375;s:18:\"rss/editor.min.css\";i:376;s:17:\"rss/style-rtl.css\";i:377;s:21:\"rss/style-rtl.min.css\";i:378;s:13:\"rss/style.css\";i:379;s:17:\"rss/style.min.css\";i:380;s:21:\"search/editor-rtl.css\";i:381;s:25:\"search/editor-rtl.min.css\";i:382;s:17:\"search/editor.css\";i:383;s:21:\"search/editor.min.css\";i:384;s:20:\"search/style-rtl.css\";i:385;s:24:\"search/style-rtl.min.css\";i:386;s:16:\"search/style.css\";i:387;s:20:\"search/style.min.css\";i:388;s:20:\"search/theme-rtl.css\";i:389;s:24:\"search/theme-rtl.min.css\";i:390;s:16:\"search/theme.css\";i:391;s:20:\"search/theme.min.css\";i:392;s:24:\"separator/editor-rtl.css\";i:393;s:28:\"separator/editor-rtl.min.css\";i:394;s:20:\"separator/editor.css\";i:395;s:24:\"separator/editor.min.css\";i:396;s:23:\"separator/style-rtl.css\";i:397;s:27:\"separator/style-rtl.min.css\";i:398;s:19:\"separator/style.css\";i:399;s:23:\"separator/style.min.css\";i:400;s:23:\"separator/theme-rtl.css\";i:401;s:27:\"separator/theme-rtl.min.css\";i:402;s:19:\"separator/theme.css\";i:403;s:23:\"separator/theme.min.css\";i:404;s:24:\"shortcode/editor-rtl.css\";i:405;s:28:\"shortcode/editor-rtl.min.css\";i:406;s:20:\"shortcode/editor.css\";i:407;s:24:\"shortcode/editor.min.css\";i:408;s:24:\"site-logo/editor-rtl.css\";i:409;s:28:\"site-logo/editor-rtl.min.css\";i:410;s:20:\"site-logo/editor.css\";i:411;s:24:\"site-logo/editor.min.css\";i:412;s:23:\"site-logo/style-rtl.css\";i:413;s:27:\"site-logo/style-rtl.min.css\";i:414;s:19:\"site-logo/style.css\";i:415;s:23:\"site-logo/style.min.css\";i:416;s:27:\"site-tagline/editor-rtl.css\";i:417;s:31:\"site-tagline/editor-rtl.min.css\";i:418;s:23:\"site-tagline/editor.css\";i:419;s:27:\"site-tagline/editor.min.css\";i:420;s:25:\"site-title/editor-rtl.css\";i:421;s:29:\"site-title/editor-rtl.min.css\";i:422;s:21:\"site-title/editor.css\";i:423;s:25:\"site-title/editor.min.css\";i:424;s:24:\"site-title/style-rtl.css\";i:425;s:28:\"site-title/style-rtl.min.css\";i:426;s:20:\"site-title/style.css\";i:427;s:24:\"site-title/style.min.css\";i:428;s:26:\"social-link/editor-rtl.css\";i:429;s:30:\"social-link/editor-rtl.min.css\";i:430;s:22:\"social-link/editor.css\";i:431;s:26:\"social-link/editor.min.css\";i:432;s:27:\"social-links/editor-rtl.css\";i:433;s:31:\"social-links/editor-rtl.min.css\";i:434;s:23:\"social-links/editor.css\";i:435;s:27:\"social-links/editor.min.css\";i:436;s:26:\"social-links/style-rtl.css\";i:437;s:30:\"social-links/style-rtl.min.css\";i:438;s:22:\"social-links/style.css\";i:439;s:26:\"social-links/style.min.css\";i:440;s:21:\"spacer/editor-rtl.css\";i:441;s:25:\"spacer/editor-rtl.min.css\";i:442;s:17:\"spacer/editor.css\";i:443;s:21:\"spacer/editor.min.css\";i:444;s:20:\"spacer/style-rtl.css\";i:445;s:24:\"spacer/style-rtl.min.css\";i:446;s:16:\"spacer/style.css\";i:447;s:20:\"spacer/style.min.css\";i:448;s:20:\"table/editor-rtl.css\";i:449;s:24:\"table/editor-rtl.min.css\";i:450;s:16:\"table/editor.css\";i:451;s:20:\"table/editor.min.css\";i:452;s:19:\"table/style-rtl.css\";i:453;s:23:\"table/style-rtl.min.css\";i:454;s:15:\"table/style.css\";i:455;s:19:\"table/style.min.css\";i:456;s:19:\"table/theme-rtl.css\";i:457;s:23:\"table/theme-rtl.min.css\";i:458;s:15:\"table/theme.css\";i:459;s:19:\"table/theme.min.css\";i:460;s:23:\"tag-cloud/style-rtl.css\";i:461;s:27:\"tag-cloud/style-rtl.min.css\";i:462;s:19:\"tag-cloud/style.css\";i:463;s:23:\"tag-cloud/style.min.css\";i:464;s:28:\"template-part/editor-rtl.css\";i:465;s:32:\"template-part/editor-rtl.min.css\";i:466;s:24:\"template-part/editor.css\";i:467;s:28:\"template-part/editor.min.css\";i:468;s:27:\"template-part/theme-rtl.css\";i:469;s:31:\"template-part/theme-rtl.min.css\";i:470;s:23:\"template-part/theme.css\";i:471;s:27:\"template-part/theme.min.css\";i:472;s:30:\"term-description/style-rtl.css\";i:473;s:34:\"term-description/style-rtl.min.css\";i:474;s:26:\"term-description/style.css\";i:475;s:30:\"term-description/style.min.css\";i:476;s:27:\"text-columns/editor-rtl.css\";i:477;s:31:\"text-columns/editor-rtl.min.css\";i:478;s:23:\"text-columns/editor.css\";i:479;s:27:\"text-columns/editor.min.css\";i:480;s:26:\"text-columns/style-rtl.css\";i:481;s:30:\"text-columns/style-rtl.min.css\";i:482;s:22:\"text-columns/style.css\";i:483;s:26:\"text-columns/style.min.css\";i:484;s:19:\"verse/style-rtl.css\";i:485;s:23:\"verse/style-rtl.min.css\";i:486;s:15:\"verse/style.css\";i:487;s:19:\"verse/style.min.css\";i:488;s:20:\"video/editor-rtl.css\";i:489;s:24:\"video/editor-rtl.min.css\";i:490;s:16:\"video/editor.css\";i:491;s:20:\"video/editor.min.css\";i:492;s:19:\"video/style-rtl.css\";i:493;s:23:\"video/style-rtl.min.css\";i:494;s:15:\"video/style.css\";i:495;s:19:\"video/style.min.css\";i:496;s:19:\"video/theme-rtl.css\";i:497;s:23:\"video/theme-rtl.min.css\";i:498;s:15:\"video/theme.css\";i:499;s:19:\"video/theme.min.css\";}}','yes'),(130,'stellarwp_telemetry_last_send','','yes'),(131,'stellarwp_telemetry','a:1:{s:7:\"plugins\";a:1:{s:19:\"the-events-calendar\";a:2:{s:7:\"wp_slug\";s:43:\"the-events-calendar/the-events-calendar.php\";s:5:\"optin\";b:0;}}}','yes'),(132,'stellarwp_telemetry_the-events-calendar_show_optin','1','yes');
/*!40000 ALTER TABLE `test_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_postmeta`
--

DROP TABLE IF EXISTS `test_postmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_postmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `post_id` (`post_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=6054 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_postmeta`
--

LOCK TABLES `test_postmeta` WRITE;
/*!40000 ALTER TABLE `test_postmeta` DISABLE KEYS */;
/*!40000 ALTER TABLE `test_postmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_posts`
--

DROP TABLE IF EXISTS `test_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_posts` (
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
) ENGINE=InnoDB AUTO_INCREMENT=5544 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_posts`
--

LOCK TABLES `test_posts` WRITE;
/*!40000 ALTER TABLE `test_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `test_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_term_relationships`
--

DROP TABLE IF EXISTS `test_term_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_term_relationships` (
  `object_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `term_taxonomy_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `term_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`object_id`,`term_taxonomy_id`),
  KEY `term_taxonomy_id` (`term_taxonomy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_term_relationships`
--

LOCK TABLES `test_term_relationships` WRITE;
/*!40000 ALTER TABLE `test_term_relationships` DISABLE KEYS */;
/*!40000 ALTER TABLE `test_term_relationships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_term_taxonomy`
--

DROP TABLE IF EXISTS `test_term_taxonomy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_term_taxonomy` (
  `term_taxonomy_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `taxonomy` varchar(32) NOT NULL DEFAULT '',
  `description` longtext NOT NULL,
  `parent` bigint(20) unsigned NOT NULL DEFAULT 0,
  `count` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`term_taxonomy_id`),
  UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
  KEY `taxonomy` (`taxonomy`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_term_taxonomy`
--

LOCK TABLES `test_term_taxonomy` WRITE;
/*!40000 ALTER TABLE `test_term_taxonomy` DISABLE KEYS */;
INSERT INTO `test_term_taxonomy` VALUES (1,1,'category','',0,0);
/*!40000 ALTER TABLE `test_term_taxonomy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_termmeta`
--

DROP TABLE IF EXISTS `test_termmeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_termmeta` (
  `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`meta_id`),
  KEY `term_id` (`term_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_termmeta`
--

LOCK TABLES `test_termmeta` WRITE;
/*!40000 ALTER TABLE `test_termmeta` DISABLE KEYS */;
/*!40000 ALTER TABLE `test_termmeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_terms`
--

DROP TABLE IF EXISTS `test_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_terms` (
  `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL DEFAULT '',
  `slug` varchar(200) NOT NULL DEFAULT '',
  `term_group` bigint(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`term_id`),
  KEY `slug` (`slug`(191)),
  KEY `name` (`name`(191))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_terms`
--

LOCK TABLES `test_terms` WRITE;
/*!40000 ALTER TABLE `test_terms` DISABLE KEYS */;
INSERT INTO `test_terms` VALUES (1,'Uncategorized','uncategorized',0);
/*!40000 ALTER TABLE `test_terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_usermeta`
--

DROP TABLE IF EXISTS `test_usermeta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_usermeta` (
  `umeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `meta_key` varchar(255) DEFAULT NULL,
  `meta_value` longtext DEFAULT NULL,
  PRIMARY KEY (`umeta_id`),
  KEY `user_id` (`user_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=566 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_usermeta`
--

LOCK TABLES `test_usermeta` WRITE;
/*!40000 ALTER TABLE `test_usermeta` DISABLE KEYS */;
INSERT INTO `test_usermeta` VALUES (1,1,'nickname','admin'),(2,1,'first_name',''),(3,1,'last_name',''),(4,1,'description',''),(5,1,'rich_editing','true'),(6,1,'syntax_highlighting','true'),(7,1,'comment_shortcuts','false'),(8,1,'admin_color','fresh'),(9,1,'use_ssl','0'),(10,1,'show_admin_bar_front','true'),(11,1,'locale',''),(12,1,'test_capabilities','a:1:{s:13:\"administrator\";b:1;}'),(13,1,'test_user_level','10'),(14,1,'dismissed_wp_pointers',''),(15,1,'show_welcome_panel','1');
/*!40000 ALTER TABLE `test_usermeta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_users`
--

DROP TABLE IF EXISTS `test_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_users` (
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
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_users`
--

LOCK TABLES `test_users` WRITE;
/*!40000 ALTER TABLE `test_users` DISABLE KEYS */;
INSERT INTO `test_users` VALUES (1,'admin','$P$B.ka8Y.Uerv2YtP.NbdWxh3LHxzzD8/','admin','admin@wordpress.test','http://wordpress.test','2024-07-11 14:26:51','',0,'admin');
/*!40000 ALTER TABLE `test_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_actionscheduler_actions`
--

DROP TABLE IF EXISTS `wp_actionscheduler_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_actionscheduler_actions` (
  `action_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hook` varchar(191) NOT NULL,
  `status` varchar(20) NOT NULL,
  `scheduled_date_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `scheduled_date_local` datetime DEFAULT '0000-00-00 00:00:00',
  `args` varchar(191) DEFAULT NULL,
  `schedule` longtext DEFAULT NULL,
  `group_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_attempt_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `last_attempt_local` datetime DEFAULT '0000-00-00 00:00:00',
  `claim_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `extended_args` varchar(8000) DEFAULT NULL,
  `priority` tinyint(3) unsigned NOT NULL DEFAULT 10,
  PRIMARY KEY (`action_id`),
  KEY `hook` (`hook`),
  KEY `status` (`status`),
  KEY `scheduled_date_gmt` (`scheduled_date_gmt`),
  KEY `args` (`args`),
  KEY `group_id` (`group_id`),
  KEY `last_attempt_gmt` (`last_attempt_gmt`),
  KEY `claim_id_status_scheduled_date_gmt` (`claim_id`,`status`,`scheduled_date_gmt`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_actionscheduler_actions`
--

LOCK TABLES `wp_actionscheduler_actions` WRITE;
/*!40000 ALTER TABLE `wp_actionscheduler_actions` DISABLE KEYS */;
INSERT INTO `wp_actionscheduler_actions` VALUES (11,'action_scheduler/migration_hook','pending','2023-03-29 14:02:00','2023-03-29 10:02:00','[]','O:30:\"ActionScheduler_SimpleSchedule\":2:{s:22:\"\0*\0scheduled_timestamp\";i:1680098520;s:41:\"\0ActionScheduler_SimpleSchedule\0timestamp\";i:1680098520;}',1,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0,NULL,10);
/*!40000 ALTER TABLE `wp_actionscheduler_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_actionscheduler_claims`
--

DROP TABLE IF EXISTS `wp_actionscheduler_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_actionscheduler_claims` (
  `claim_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date_created_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`claim_id`),
  KEY `date_created_gmt` (`date_created_gmt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_actionscheduler_claims`
--

LOCK TABLES `wp_actionscheduler_claims` WRITE;
/*!40000 ALTER TABLE `wp_actionscheduler_claims` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_actionscheduler_claims` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_actionscheduler_groups`
--

DROP TABLE IF EXISTS `wp_actionscheduler_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_actionscheduler_groups` (
  `group_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  PRIMARY KEY (`group_id`),
  KEY `slug` (`slug`(191))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_actionscheduler_groups`
--

LOCK TABLES `wp_actionscheduler_groups` WRITE;
/*!40000 ALTER TABLE `wp_actionscheduler_groups` DISABLE KEYS */;
INSERT INTO `wp_actionscheduler_groups` VALUES (1,'action-scheduler-migration');
/*!40000 ALTER TABLE `wp_actionscheduler_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_actionscheduler_logs`
--

DROP TABLE IF EXISTS `wp_actionscheduler_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_actionscheduler_logs` (
  `log_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `action_id` bigint(20) unsigned NOT NULL,
  `message` text NOT NULL,
  `log_date_gmt` datetime DEFAULT '0000-00-00 00:00:00',
  `log_date_local` datetime DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`log_id`),
  KEY `action_id` (`action_id`),
  KEY `log_date_gmt` (`log_date_gmt`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_actionscheduler_logs`
--

LOCK TABLES `wp_actionscheduler_logs` WRITE;
/*!40000 ALTER TABLE `wp_actionscheduler_logs` DISABLE KEYS */;
INSERT INTO `wp_actionscheduler_logs` VALUES (1,11,'action created','2023-03-29 14:01:00','2023-03-29 10:01:00');
/*!40000 ALTER TABLE `wp_actionscheduler_logs` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_comments`
--

LOCK TABLES `wp_comments` WRITE;
/*!40000 ALTER TABLE `wp_comments` DISABLE KEYS */;
INSERT INTO `wp_comments` VALUES (1,1,'A WordPress Commenter','wapuu@wordpress.example','https://wordpress.org/','','2023-03-28 13:54:49','2023-03-28 13:54:49','Hi, this is a comment.\nTo get started with moderating, editing, and deleting comments, please visit the Comments screen in the dashboard.\nCommenter avatars come from <a href=\"https://en.gravatar.com/\">Gravatar</a>.',0,'1','','comment',0,0);
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
) ENGINE=InnoDB AUTO_INCREMENT=182 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_options`
--

LOCK TABLES `wp_options` WRITE;
/*!40000 ALTER TABLE `wp_options` DISABLE KEYS */;
INSERT INTO `wp_options` VALUES (1,'siteurl','https://wordpress.test','yes'),(2,'home','https://wordpress.test','yes'),(3,'blogname','RT Smoketest','yes'),(4,'blogdescription','Just another WordPress site','yes'),(5,'users_can_register','0','yes'),(6,'admin_email','admin@wordpress.test','yes'),(7,'start_of_week','1','yes'),(8,'use_balanceTags','0','yes'),(9,'use_smilies','1','yes'),(10,'require_name_email','1','yes'),(11,'comments_notify','1','yes'),(12,'posts_per_rss','10','yes'),(13,'rss_use_excerpt','0','yes'),(14,'mailserver_url','mail.example.com','yes'),(15,'mailserver_login','login@example.com','yes'),(16,'mailserver_pass','password','yes'),(17,'mailserver_port','110','yes'),(18,'default_category','1','yes'),(19,'default_comment_status','open','yes'),(20,'default_ping_status','open','yes'),(21,'default_pingback_flag','1','yes'),(22,'posts_per_page','10','yes'),(23,'date_format','F j, Y','yes'),(24,'time_format','g:i a','yes'),(25,'links_updated_date_format','F j, Y g:i a','yes'),(26,'comment_moderation','0','yes'),(27,'moderation_notify','1','yes'),(28,'permalink_structure','','yes'),(29,'rewrite_rules','','yes'),(30,'hack_file','0','yes'),(31,'blog_charset','UTF-8','yes'),(32,'moderation_keys','','no'),(33,'active_plugins','a:3:{i:0;s:31:\"event-tickets/event-tickets.php\";i:1;s:34:\"events-pro/events-calendar-pro.php\";i:2;s:43:\"the-events-calendar/the-events-calendar.php\";}','yes'),(34,'category_base','','yes'),(35,'ping_sites','http://rpc.pingomatic.com/','yes'),(36,'comment_max_links','2','yes'),(37,'gmt_offset','0','yes'),(38,'default_email_category','1','yes'),(39,'recently_edited','','no'),(40,'template','twentytwenty','yes'),(41,'stylesheet','twentytwenty','yes'),(42,'comment_registration','0','yes'),(43,'html_type','text/html','yes'),(44,'use_trackback','0','yes'),(45,'default_role','subscriber','yes'),(46,'db_version','57155','yes'),(47,'uploads_use_yearmonth_folders','1','yes'),(48,'upload_path','','yes'),(49,'blog_public','1','yes'),(50,'default_link_category','2','yes'),(51,'show_on_front','posts','yes'),(52,'tag_base','','yes'),(53,'show_avatars','1','yes'),(54,'avatar_rating','G','yes'),(55,'upload_url_path','','yes'),(56,'thumbnail_size_w','150','yes'),(57,'thumbnail_size_h','150','yes'),(58,'thumbnail_crop','1','yes'),(59,'medium_size_w','300','yes'),(60,'medium_size_h','300','yes'),(61,'avatar_default','mystery','yes'),(62,'large_size_w','1024','yes'),(63,'large_size_h','1024','yes'),(64,'image_default_link_type','none','yes'),(65,'image_default_size','','yes'),(66,'image_default_align','','yes'),(67,'close_comments_for_old_posts','0','yes'),(68,'close_comments_days_old','14','yes'),(69,'thread_comments','1','yes'),(70,'thread_comments_depth','5','yes'),(71,'page_comments','0','yes'),(72,'comments_per_page','50','yes'),(73,'default_comments_page','newest','yes'),(74,'comment_order','asc','yes'),(75,'sticky_posts','a:0:{}','yes'),(76,'widget_categories','a:0:{}','yes'),(77,'widget_text','a:0:{}','yes'),(78,'widget_rss','a:0:{}','yes'),(79,'uninstall_plugins','a:0:{}','no'),(80,'timezone_string','America/New_York','yes'),(81,'page_for_posts','0','yes'),(82,'page_on_front','0','yes'),(83,'default_post_format','0','yes'),(84,'link_manager_enabled','0','yes'),(85,'finished_splitting_shared_terms','1','yes'),(86,'site_icon','0','yes'),(87,'medium_large_size_w','768','yes'),(88,'medium_large_size_h','0','yes'),(89,'wp_page_for_privacy_policy','3','yes'),(90,'show_comments_cookies_opt_in','1','yes'),(91,'admin_email_lifespan','1695563689','yes'),(92,'disallowed_keys','','no'),(93,'comment_previously_approved','1','yes'),(94,'auto_plugin_theme_update_emails','a:0:{}','no'),(95,'auto_update_core_dev','enabled','yes'),(96,'auto_update_core_minor','enabled','yes'),(97,'auto_update_core_major','enabled','yes'),(98,'wp_force_deactivated_plugins','a:0:{}','yes'),(99,'initial_db_version','53496','yes'),(100,'wp_user_roles','a:5:{s:13:\"administrator\";a:2:{s:4:\"name\";s:13:\"Administrator\";s:12:\"capabilities\";a:101:{s:13:\"switch_themes\";b:1;s:11:\"edit_themes\";b:1;s:16:\"activate_plugins\";b:1;s:12:\"edit_plugins\";b:1;s:10:\"edit_users\";b:1;s:10:\"edit_files\";b:1;s:14:\"manage_options\";b:1;s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:6:\"import\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:8:\"level_10\";b:1;s:7:\"level_9\";b:1;s:7:\"level_8\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;s:12:\"delete_users\";b:1;s:12:\"create_users\";b:1;s:17:\"unfiltered_upload\";b:1;s:14:\"edit_dashboard\";b:1;s:14:\"update_plugins\";b:1;s:14:\"delete_plugins\";b:1;s:15:\"install_plugins\";b:1;s:13:\"update_themes\";b:1;s:14:\"install_themes\";b:1;s:11:\"update_core\";b:1;s:10:\"list_users\";b:1;s:12:\"remove_users\";b:1;s:13:\"promote_users\";b:1;s:18:\"edit_theme_options\";b:1;s:13:\"delete_themes\";b:1;s:6:\"export\";b:1;s:25:\"read_private_tribe_events\";b:1;s:17:\"edit_tribe_events\";b:1;s:24:\"edit_others_tribe_events\";b:1;s:25:\"edit_private_tribe_events\";b:1;s:27:\"edit_published_tribe_events\";b:1;s:19:\"delete_tribe_events\";b:1;s:26:\"delete_others_tribe_events\";b:1;s:27:\"delete_private_tribe_events\";b:1;s:29:\"delete_published_tribe_events\";b:1;s:20:\"publish_tribe_events\";b:1;s:25:\"read_private_tribe_venues\";b:1;s:17:\"edit_tribe_venues\";b:1;s:24:\"edit_others_tribe_venues\";b:1;s:25:\"edit_private_tribe_venues\";b:1;s:27:\"edit_published_tribe_venues\";b:1;s:19:\"delete_tribe_venues\";b:1;s:26:\"delete_others_tribe_venues\";b:1;s:27:\"delete_private_tribe_venues\";b:1;s:29:\"delete_published_tribe_venues\";b:1;s:20:\"publish_tribe_venues\";b:1;s:29:\"read_private_tribe_organizers\";b:1;s:21:\"edit_tribe_organizers\";b:1;s:28:\"edit_others_tribe_organizers\";b:1;s:29:\"edit_private_tribe_organizers\";b:1;s:31:\"edit_published_tribe_organizers\";b:1;s:23:\"delete_tribe_organizers\";b:1;s:30:\"delete_others_tribe_organizers\";b:1;s:31:\"delete_private_tribe_organizers\";b:1;s:33:\"delete_published_tribe_organizers\";b:1;s:24:\"publish_tribe_organizers\";b:1;s:31:\"read_private_aggregator-records\";b:1;s:23:\"edit_aggregator-records\";b:1;s:30:\"edit_others_aggregator-records\";b:1;s:31:\"edit_private_aggregator-records\";b:1;s:33:\"edit_published_aggregator-records\";b:1;s:25:\"delete_aggregator-records\";b:1;s:32:\"delete_others_aggregator-records\";b:1;s:33:\"delete_private_aggregator-records\";b:1;s:35:\"delete_published_aggregator-records\";b:1;s:26:\"publish_aggregator-records\";b:1;}}s:6:\"editor\";a:2:{s:4:\"name\";s:6:\"Editor\";s:12:\"capabilities\";a:74:{s:17:\"moderate_comments\";b:1;s:17:\"manage_categories\";b:1;s:12:\"manage_links\";b:1;s:12:\"upload_files\";b:1;s:15:\"unfiltered_html\";b:1;s:10:\"edit_posts\";b:1;s:17:\"edit_others_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:10:\"edit_pages\";b:1;s:4:\"read\";b:1;s:7:\"level_7\";b:1;s:7:\"level_6\";b:1;s:7:\"level_5\";b:1;s:7:\"level_4\";b:1;s:7:\"level_3\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:17:\"edit_others_pages\";b:1;s:20:\"edit_published_pages\";b:1;s:13:\"publish_pages\";b:1;s:12:\"delete_pages\";b:1;s:19:\"delete_others_pages\";b:1;s:22:\"delete_published_pages\";b:1;s:12:\"delete_posts\";b:1;s:19:\"delete_others_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:20:\"delete_private_posts\";b:1;s:18:\"edit_private_posts\";b:1;s:18:\"read_private_posts\";b:1;s:20:\"delete_private_pages\";b:1;s:18:\"edit_private_pages\";b:1;s:18:\"read_private_pages\";b:1;s:25:\"read_private_tribe_events\";b:1;s:17:\"edit_tribe_events\";b:1;s:24:\"edit_others_tribe_events\";b:1;s:25:\"edit_private_tribe_events\";b:1;s:27:\"edit_published_tribe_events\";b:1;s:19:\"delete_tribe_events\";b:1;s:26:\"delete_others_tribe_events\";b:1;s:27:\"delete_private_tribe_events\";b:1;s:29:\"delete_published_tribe_events\";b:1;s:20:\"publish_tribe_events\";b:1;s:25:\"read_private_tribe_venues\";b:1;s:17:\"edit_tribe_venues\";b:1;s:24:\"edit_others_tribe_venues\";b:1;s:25:\"edit_private_tribe_venues\";b:1;s:27:\"edit_published_tribe_venues\";b:1;s:19:\"delete_tribe_venues\";b:1;s:26:\"delete_others_tribe_venues\";b:1;s:27:\"delete_private_tribe_venues\";b:1;s:29:\"delete_published_tribe_venues\";b:1;s:20:\"publish_tribe_venues\";b:1;s:29:\"read_private_tribe_organizers\";b:1;s:21:\"edit_tribe_organizers\";b:1;s:28:\"edit_others_tribe_organizers\";b:1;s:29:\"edit_private_tribe_organizers\";b:1;s:31:\"edit_published_tribe_organizers\";b:1;s:23:\"delete_tribe_organizers\";b:1;s:30:\"delete_others_tribe_organizers\";b:1;s:31:\"delete_private_tribe_organizers\";b:1;s:33:\"delete_published_tribe_organizers\";b:1;s:24:\"publish_tribe_organizers\";b:1;s:31:\"read_private_aggregator-records\";b:1;s:23:\"edit_aggregator-records\";b:1;s:30:\"edit_others_aggregator-records\";b:1;s:31:\"edit_private_aggregator-records\";b:1;s:33:\"edit_published_aggregator-records\";b:1;s:25:\"delete_aggregator-records\";b:1;s:32:\"delete_others_aggregator-records\";b:1;s:33:\"delete_private_aggregator-records\";b:1;s:35:\"delete_published_aggregator-records\";b:1;s:26:\"publish_aggregator-records\";b:1;}}s:6:\"author\";a:2:{s:4:\"name\";s:6:\"Author\";s:12:\"capabilities\";a:30:{s:12:\"upload_files\";b:1;s:10:\"edit_posts\";b:1;s:20:\"edit_published_posts\";b:1;s:13:\"publish_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_2\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;s:22:\"delete_published_posts\";b:1;s:17:\"edit_tribe_events\";b:1;s:27:\"edit_published_tribe_events\";b:1;s:19:\"delete_tribe_events\";b:1;s:29:\"delete_published_tribe_events\";b:1;s:20:\"publish_tribe_events\";b:1;s:17:\"edit_tribe_venues\";b:1;s:27:\"edit_published_tribe_venues\";b:1;s:19:\"delete_tribe_venues\";b:1;s:29:\"delete_published_tribe_venues\";b:1;s:20:\"publish_tribe_venues\";b:1;s:21:\"edit_tribe_organizers\";b:1;s:31:\"edit_published_tribe_organizers\";b:1;s:23:\"delete_tribe_organizers\";b:1;s:33:\"delete_published_tribe_organizers\";b:1;s:24:\"publish_tribe_organizers\";b:1;s:23:\"edit_aggregator-records\";b:1;s:33:\"edit_published_aggregator-records\";b:1;s:25:\"delete_aggregator-records\";b:1;s:35:\"delete_published_aggregator-records\";b:1;s:26:\"publish_aggregator-records\";b:1;}}s:11:\"contributor\";a:2:{s:4:\"name\";s:11:\"Contributor\";s:12:\"capabilities\";a:13:{s:10:\"edit_posts\";b:1;s:4:\"read\";b:1;s:7:\"level_1\";b:1;s:7:\"level_0\";b:1;s:12:\"delete_posts\";b:1;s:17:\"edit_tribe_events\";b:1;s:19:\"delete_tribe_events\";b:1;s:17:\"edit_tribe_venues\";b:1;s:19:\"delete_tribe_venues\";b:1;s:21:\"edit_tribe_organizers\";b:1;s:23:\"delete_tribe_organizers\";b:1;s:23:\"edit_aggregator-records\";b:1;s:25:\"delete_aggregator-records\";b:1;}}s:10:\"subscriber\";a:2:{s:4:\"name\";s:10:\"Subscriber\";s:12:\"capabilities\";a:2:{s:4:\"read\";b:1;s:7:\"level_0\";b:1;}}}','yes'),(101,'fresh_site','1','yes'),(102,'user_count','1','no'),(103,'widget_block','a:6:{i:2;a:1:{s:7:\"content\";s:19:\"<!-- wp:search /-->\";}i:3;a:1:{s:7:\"content\";s:154:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Recent Posts</h2><!-- /wp:heading --><!-- wp:latest-posts /--></div><!-- /wp:group -->\";}i:4;a:1:{s:7:\"content\";s:227:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Recent Comments</h2><!-- /wp:heading --><!-- wp:latest-comments {\"displayAvatar\":false,\"displayDate\":false,\"displayExcerpt\":false} /--></div><!-- /wp:group -->\";}i:5;a:1:{s:7:\"content\";s:146:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Archives</h2><!-- /wp:heading --><!-- wp:archives /--></div><!-- /wp:group -->\";}i:6;a:1:{s:7:\"content\";s:150:\"<!-- wp:group --><div class=\"wp-block-group\"><!-- wp:heading --><h2>Categories</h2><!-- /wp:heading --><!-- wp:categories /--></div><!-- /wp:group -->\";}s:12:\"_multiwidget\";i:1;}','yes'),(104,'sidebars_widgets','a:2:{s:19:\"wp_inactive_widgets\";a:5:{i:0;s:7:\"block-2\";i:1;s:7:\"block-3\";i:2;s:7:\"block-4\";i:3;s:7:\"block-5\";i:4;s:7:\"block-6\";}s:13:\"array_version\";i:3;}','yes'),(105,'cron','a:7:{i:1680011715;a:5:{s:32:\"recovery_mode_clean_expired_keys\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:34:\"wp_privacy_delete_old_export_files\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}s:16:\"wp_version_check\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:17:\"wp_update_plugins\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:16:\"wp_update_themes\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}}i:1680011801;a:3:{s:30:\"tribe_schedule_transient_purge\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:10:\"twicedaily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:43200;}}s:16:\"tribe_daily_cron\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}s:26:\"tribe_tickets_migrate_4_12\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"hourly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:3600;}}}i:1680098115;a:1:{s:30:\"wp_site_health_scheduled_check\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:6:\"weekly\";s:4:\"args\";a:0:{}s:8:\"interval\";i:604800;}}}i:1680098201;a:1:{s:24:\"tribe_common_log_cleanup\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1687425476;a:1:{s:21:\"tribe-recurrence-cron\";a:1:{s:32:\"40cd750bba9870f18aada2478b24840a\";a:3:{s:8:\"schedule\";s:5:\"daily\";s:4:\"args\";a:0:{}s:8:\"interval\";i:86400;}}}i:1692276901;a:1:{s:26:\"action_scheduler_run_queue\";a:1:{s:32:\"0d04ed39571b55704c122d726248bbac\";a:3:{s:8:\"schedule\";s:12:\"every_minute\";s:4:\"args\";a:1:{i:0;s:7:\"WP Cron\";}s:8:\"interval\";i:60;}}}s:7:\"version\";i:2;}','yes'),(106,'widget_pages','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(107,'widget_calendar','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(108,'widget_archives','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(109,'widget_media_audio','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(110,'widget_media_image','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(111,'widget_media_gallery','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(112,'widget_media_video','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(113,'widget_meta','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(114,'widget_search','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(115,'widget_tag_cloud','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(116,'widget_nav_menu','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(117,'widget_custom_html','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(118,'_transient_doing_cron','1700233725.4340300559997558593750','yes'),(121,'_site_transient_update_themes','O:8:\"stdClass\":5:{s:12:\"last_checked\";i:1680011726;s:7:\"checked\";a:3:{s:12:\"twentytwenty\";s:3:\"2.0\";s:15:\"twentytwentyone\";s:3:\"1.6\";s:15:\"twentytwentytwo\";s:3:\"1.2\";}s:8:\"response\";a:3:{s:12:\"twentytwenty\";a:6:{s:5:\"theme\";s:12:\"twentytwenty\";s:11:\"new_version\";s:3:\"2.1\";s:3:\"url\";s:42:\"https://wordpress.org/themes/twentytwenty/\";s:7:\"package\";s:58:\"https://downloads.wordpress.org/theme/twentytwenty.2.1.zip\";s:8:\"requires\";s:3:\"4.7\";s:12:\"requires_php\";s:5:\"5.2.4\";}s:15:\"twentytwentyone\";a:6:{s:5:\"theme\";s:15:\"twentytwentyone\";s:11:\"new_version\";s:3:\"1.7\";s:3:\"url\";s:45:\"https://wordpress.org/themes/twentytwentyone/\";s:7:\"package\";s:61:\"https://downloads.wordpress.org/theme/twentytwentyone.1.7.zip\";s:8:\"requires\";s:3:\"5.3\";s:12:\"requires_php\";s:3:\"5.6\";}s:15:\"twentytwentytwo\";a:6:{s:5:\"theme\";s:15:\"twentytwentytwo\";s:11:\"new_version\";s:3:\"1.3\";s:3:\"url\";s:45:\"https://wordpress.org/themes/twentytwentytwo/\";s:7:\"package\";s:61:\"https://downloads.wordpress.org/theme/twentytwentytwo.1.3.zip\";s:8:\"requires\";s:3:\"5.9\";s:12:\"requires_php\";s:3:\"5.6\";}}s:9:\"no_update\";a:0:{}s:12:\"translations\";a:0:{}}','no'),(124,'tribe_last_updated_option','1700233729.9088','yes'),(125,'tribe_events_calendar_options','a:14:{s:25:\"ticket-enabled-post-types\";a:2:{i:0;s:12:\"tribe_events\";i:1;s:4:\"page\";}s:28:\"event-tickets-schema-version\";s:9:\"6.0.0-dev\";s:31:\"previous_event_tickets_versions\";a:1:{i:0;s:1:\"0\";}s:28:\"latest_event_tickets_version\";s:9:\"6.0.0-dev\";s:8:\"did_init\";b:1;s:19:\"tribeEventsTemplate\";s:0:\"\";s:16:\"tribeEnableViews\";a:3:{i:0;s:4:\"list\";i:1;s:5:\"month\";i:2;s:3:\"day\";}s:10:\"viewOption\";s:4:\"list\";s:14:\"schema-version\";s:9:\"6.3.0-dev\";s:21:\"previous_ecp_versions\";a:2:{i:0;s:1:\"0\";i:1;s:5:\"6.1.1\";}s:18:\"latest_ecp_version\";s:9:\"6.3.0-dev\";s:18:\"dateWithYearFormat\";s:6:\"F j, Y\";s:24:\"recurrenceMaxMonthsAfter\";i:60;s:22:\"google_maps_js_api_key\";s:39:\"AIzaSyDNsicAsP6-VuGtAb1O9riI3oc_NOb7IOU\";}','yes'),(126,'tec_recurring_tickets_active','1','yes'),(127,'widget_recent-posts','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(128,'widget_recent-comments','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(132,'tec_ct1_migration_state','a:3:{s:18:\"complete_timestamp\";N;s:5:\"phase\";s:22:\"migration-not-required\";s:19:\"preview_unsupported\";b:0;}','yes'),(133,'tec_ct1_series_relationship_table_schema_version','1.0.0','yes'),(134,'tec_ct1_events_table_schema_version','1.0.1','yes'),(135,'tec_ct1_occurrences_table_schema_version','1.0.2','yes'),(136,'tec_ct1_events_field_schema_version','1.0.1','yes'),(137,'tec_ct1_occurrences_field_schema_version','1.0.1','yes'),(140,'_transient_tec_custom_tables_v1_ecp_initialized','1680098849','yes'),(141,'tec_custom_tables_v1_provisional_post_base_provisional_id','10000000','yes'),(142,'stellar_schema_version_tec-ft-ticket-groups','1.0.0','yes'),(143,'stellar_schema_version_tec-ft-posts-and-ticket-groups','1.0.0','yes'),(146,'tribe_last_save_post','1700233729.9089','yes'),(147,'widget_tribe-widget-events-list','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(148,'widget_tribe-widget-event-countdown','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(149,'widget_tribe-widget-featured-venue','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(150,'widget_tribe-widget-events-month','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(151,'widget_tribe-widget-events-week','a:1:{s:12:\"_multiwidget\";i:1;}','yes'),(152,'stellarwp_telemetry_last_send','','yes'),(153,'stellarwp_telemetry','a:1:{s:7:\"plugins\";a:1:{s:13:\"event-tickets\";a:2:{s:7:\"wp_slug\";s:31:\"event-tickets/event-tickets.php\";s:5:\"optin\";b:0;}}}','yes'),(154,'stellarwp_telemetry_event-tickets_show_optin','1','yes'),(155,'tec_timed_tec_custom_tables_v1_ecp_initialized','a:3:{s:3:\"key\";s:36:\"tec_custom_tables_v1_ecp_initialized\";s:5:\"value\";i:1;s:10:\"expiration\";i:1700320125;}','yes'),(156,'tec_timed_tec_custom_tables_v1_initialized','a:3:{s:3:\"key\";s:32:\"tec_custom_tables_v1_initialized\";s:5:\"value\";i:1;s:10:\"expiration\";i:1700320125;}','yes'),(159,'external_updates-events-calendar-pro','O:8:\"stdClass\":3:{s:9:\"lastCheck\";i:1687425483;s:14:\"checkedVersion\";s:5:\"6.1.0\";s:6:\"update\";O:19:\"Tribe__PUE__Utility\":11:{s:2:\"id\";i:0;s:6:\"plugin\";s:34:\"events-pro/events-calendar-pro.php\";s:4:\"slug\";s:19:\"events-calendar-pro\";s:7:\"version\";s:5:\"6.1.0\";s:8:\"homepage\";s:18:\"https://evnt.is/4d\";s:12:\"download_url\";s:301:\"https://pue.theeventscalendar.com/api/plugins/v2/download?plugin=events-calendar-pro&version=6.1.0&installed_version=6.1.0&domain=wordpress.test&multisite=0&network_activated=0&active_sites=1&wp_version=6.0.2&key=97f261bb1246e1c8ad0b7ace9ec6cb7764a09730&dk=97f261bb1246e1c8ad0b7ace9ec6cb7764a09730&o=e\";s:8:\"sections\";O:8:\"stdClass\":3:{s:11:\"description\";s:204:\"Events Calendar Pro is an extension to The Events Calendar, and includes recurring events, additional frontend views and more. To see a full feature list please visit the product page (http://evnt.is/4d).\";s:12:\"installation\";s:353:\"Installing Events Calendar Pro is easy: just back up your site, download/install The Events Calendar from the WordPress.org repo, and download/install Events Calendar Pro from theeventscalendar.com. Activate them both and you\'ll be good to go! If you\'re still confused or encounter problems, check out part 1 of our new user primer (http://m.tri.be/4i).\";s:9:\"changelog\";s:971:\"<p>= [6.1.0] 2023-06-14 =</p>\r\n<ul>\r\n<li> Version - Events Calendar PRO 6.1.0 is only compatible with The Events Calendar 6.1.0 and higher</li>\r\n<li>Feature - Replace Freemius with Telemetry - an in-house info system.</li>\r\n<li>Feature - Add plugin info to Site Health admin page.</li>\r\n<li>Fix - Ensure all the strings in the add event page are translatable. [ECP-1453]</li>\r\n<li>Fix - Ensure the `Use in Block Editor` option under `Events → Settings → Additional Fields` works as expected.</li>\r\n<li>Fix - Removing our eager schema updates. This was causing a number of `ALTER` statements being run redundantly. No longer utilizes cache/transient for the last run check as it is not dependable.</li>\r\n<li>Tweak - Updates title tags on events pages, to be more unique and search specific, taking on formats like \"Summary of Events from February 2, 2017 - February 3, 2019\".</li>\r\n<li>Language - 4 new strings added, 96 updated, 0 fuzzied, and 1 obsoleted</li>\r\n</ul>\";}s:14:\"upgrade_notice\";s:0:\"\";s:13:\"custom_update\";O:8:\"stdClass\":1:{s:5:\"icons\";O:8:\"stdClass\":1:{s:3:\"svg\";s:84:\"https://theeventscalendar.com/content/themes/tribe-ecp/img/svg/product-icons/ECP.svg\";}}s:11:\"api_expired\";b:0;s:11:\"api_upgrade\";b:0;}}','no'),(160,'tribe_pue_key_notices','a:0:{}','yes'),(161,'_site_transient_update_plugins','O:8:\"stdClass\":4:{s:12:\"last_checked\";i:1687425481;s:8:\"response\";a:1:{s:19:\"akismet/akismet.php\";O:8:\"stdClass\":12:{s:2:\"id\";s:21:\"w.org/plugins/akismet\";s:4:\"slug\";s:7:\"akismet\";s:6:\"plugin\";s:19:\"akismet/akismet.php\";s:11:\"new_version\";s:3:\"5.2\";s:3:\"url\";s:38:\"https://wordpress.org/plugins/akismet/\";s:7:\"package\";s:54:\"https://downloads.wordpress.org/plugin/akismet.5.2.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:60:\"https://ps.w.org/akismet/assets/icon-256x256.png?rev=2818463\";s:2:\"1x\";s:60:\"https://ps.w.org/akismet/assets/icon-128x128.png?rev=2818463\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:63:\"https://ps.w.org/akismet/assets/banner-1544x500.png?rev=2900731\";s:2:\"1x\";s:62:\"https://ps.w.org/akismet/assets/banner-772x250.png?rev=2900731\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"5.8\";s:6:\"tested\";s:5:\"6.2.2\";s:12:\"requires_php\";s:6:\"5.6.20\";}}s:12:\"translations\";a:0:{}s:9:\"no_update\";a:10:{s:35:\"advanced-post-manager/tribe-apm.php\";O:8:\"stdClass\":10:{s:2:\"id\";s:35:\"w.org/plugins/advanced-post-manager\";s:4:\"slug\";s:21:\"advanced-post-manager\";s:6:\"plugin\";s:35:\"advanced-post-manager/tribe-apm.php\";s:11:\"new_version\";s:5:\"4.5.4\";s:3:\"url\";s:52:\"https://wordpress.org/plugins/advanced-post-manager/\";s:7:\"package\";s:70:\"https://downloads.wordpress.org/plugin/advanced-post-manager.4.5.4.zip\";s:5:\"icons\";a:1:{s:7:\"default\";s:72:\"https://s.w.org/plugins/geopattern-icon/advanced-post-manager_66b8d2.svg\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:76:\"https://ps.w.org/advanced-post-manager/assets/banner-1544x500.jpg?rev=593014\";s:2:\"1x\";s:75:\"https://ps.w.org/advanced-post-manager/assets/banner-772x250.png?rev=517740\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"5.7\";}s:23:\"debug-bar/debug-bar.php\";O:8:\"stdClass\":10:{s:2:\"id\";s:23:\"w.org/plugins/debug-bar\";s:4:\"slug\";s:9:\"debug-bar\";s:6:\"plugin\";s:23:\"debug-bar/debug-bar.php\";s:11:\"new_version\";s:5:\"1.1.4\";s:3:\"url\";s:40:\"https://wordpress.org/plugins/debug-bar/\";s:7:\"package\";s:58:\"https://downloads.wordpress.org/plugin/debug-bar.1.1.4.zip\";s:5:\"icons\";a:2:{s:2:\"1x\";s:54:\"https://ps.w.org/debug-bar/assets/icon.svg?rev=1908362\";s:3:\"svg\";s:54:\"https://ps.w.org/debug-bar/assets/icon.svg?rev=1908362\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:65:\"https://ps.w.org/debug-bar/assets/banner-1544x500.png?rev=1365496\";s:2:\"1x\";s:64:\"https://ps.w.org/debug-bar/assets/banner-772x250.png?rev=1365496\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"3.4\";}s:49:\"easy-digital-downloads/easy-digital-downloads.php\";O:8:\"stdClass\":10:{s:2:\"id\";s:36:\"w.org/plugins/easy-digital-downloads\";s:4:\"slug\";s:22:\"easy-digital-downloads\";s:6:\"plugin\";s:49:\"easy-digital-downloads/easy-digital-downloads.php\";s:11:\"new_version\";s:5:\"3.1.2\";s:3:\"url\";s:53:\"https://wordpress.org/plugins/easy-digital-downloads/\";s:7:\"package\";s:71:\"https://downloads.wordpress.org/plugin/easy-digital-downloads.3.1.2.zip\";s:5:\"icons\";a:2:{s:2:\"1x\";s:66:\"https://ps.w.org/easy-digital-downloads/assets/icon.svg?rev=971968\";s:3:\"svg\";s:66:\"https://ps.w.org/easy-digital-downloads/assets/icon.svg?rev=971968\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:78:\"https://ps.w.org/easy-digital-downloads/assets/banner-1544x500.png?rev=2636140\";s:2:\"1x\";s:77:\"https://ps.w.org/easy-digital-downloads/assets/banner-772x250.png?rev=2636140\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"5.4\";}s:31:\"event-tickets/event-tickets.php\";O:8:\"stdClass\":10:{s:2:\"id\";s:27:\"w.org/plugins/event-tickets\";s:4:\"slug\";s:13:\"event-tickets\";s:6:\"plugin\";s:31:\"event-tickets/event-tickets.php\";s:11:\"new_version\";s:7:\"5.6.0.2\";s:3:\"url\";s:44:\"https://wordpress.org/plugins/event-tickets/\";s:7:\"package\";s:64:\"https://downloads.wordpress.org/plugin/event-tickets.5.6.0.2.zip\";s:5:\"icons\";a:2:{s:2:\"1x\";s:58:\"https://ps.w.org/event-tickets/assets/icon.svg?rev=2259340\";s:3:\"svg\";s:58:\"https://ps.w.org/event-tickets/assets/icon.svg?rev=2259340\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:69:\"https://ps.w.org/event-tickets/assets/banner-1544x500.png?rev=2257626\";s:2:\"1x\";s:68:\"https://ps.w.org/event-tickets/assets/banner-772x250.png?rev=2257626\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:5:\"5.8.5\";}s:9:\"hello.php\";O:8:\"stdClass\":10:{s:2:\"id\";s:25:\"w.org/plugins/hello-dolly\";s:4:\"slug\";s:11:\"hello-dolly\";s:6:\"plugin\";s:9:\"hello.php\";s:11:\"new_version\";s:5:\"1.7.2\";s:3:\"url\";s:42:\"https://wordpress.org/plugins/hello-dolly/\";s:7:\"package\";s:60:\"https://downloads.wordpress.org/plugin/hello-dolly.1.7.2.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:64:\"https://ps.w.org/hello-dolly/assets/icon-256x256.jpg?rev=2052855\";s:2:\"1x\";s:64:\"https://ps.w.org/hello-dolly/assets/icon-128x128.jpg?rev=2052855\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:67:\"https://ps.w.org/hello-dolly/assets/banner-1544x500.jpg?rev=2645582\";s:2:\"1x\";s:66:\"https://ps.w.org/hello-dolly/assets/banner-772x250.jpg?rev=2052855\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"4.6\";}s:31:\"query-monitor/query-monitor.php\";O:8:\"stdClass\":10:{s:2:\"id\";s:27:\"w.org/plugins/query-monitor\";s:4:\"slug\";s:13:\"query-monitor\";s:6:\"plugin\";s:31:\"query-monitor/query-monitor.php\";s:11:\"new_version\";s:6:\"3.12.3\";s:3:\"url\";s:44:\"https://wordpress.org/plugins/query-monitor/\";s:7:\"package\";s:63:\"https://downloads.wordpress.org/plugin/query-monitor.3.12.3.zip\";s:5:\"icons\";a:2:{s:2:\"1x\";s:58:\"https://ps.w.org/query-monitor/assets/icon.svg?rev=2870124\";s:3:\"svg\";s:58:\"https://ps.w.org/query-monitor/assets/icon.svg?rev=2870124\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:69:\"https://ps.w.org/query-monitor/assets/banner-1544x500.png?rev=2870124\";s:2:\"1x\";s:68:\"https://ps.w.org/query-monitor/assets/banner-772x250.png?rev=2457098\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"5.2\";}s:27:\"redis-cache/redis-cache.php\";O:8:\"stdClass\":10:{s:2:\"id\";s:25:\"w.org/plugins/redis-cache\";s:4:\"slug\";s:11:\"redis-cache\";s:6:\"plugin\";s:27:\"redis-cache/redis-cache.php\";s:11:\"new_version\";s:5:\"2.4.1\";s:3:\"url\";s:42:\"https://wordpress.org/plugins/redis-cache/\";s:7:\"package\";s:60:\"https://downloads.wordpress.org/plugin/redis-cache.2.4.1.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:64:\"https://ps.w.org/redis-cache/assets/icon-256x256.gif?rev=2568513\";s:2:\"1x\";s:64:\"https://ps.w.org/redis-cache/assets/icon-128x128.gif?rev=2568513\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:67:\"https://ps.w.org/redis-cache/assets/banner-1544x500.png?rev=2315420\";s:2:\"1x\";s:66:\"https://ps.w.org/redis-cache/assets/banner-772x250.png?rev=2315420\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"3.3\";}s:51:\"rewrite-rules-inspector/rewrite-rules-inspector.php\";O:8:\"stdClass\":10:{s:2:\"id\";s:37:\"w.org/plugins/rewrite-rules-inspector\";s:4:\"slug\";s:23:\"rewrite-rules-inspector\";s:6:\"plugin\";s:51:\"rewrite-rules-inspector/rewrite-rules-inspector.php\";s:11:\"new_version\";s:5:\"1.3.1\";s:3:\"url\";s:54:\"https://wordpress.org/plugins/rewrite-rules-inspector/\";s:7:\"package\";s:72:\"https://downloads.wordpress.org/plugin/rewrite-rules-inspector.1.3.1.zip\";s:5:\"icons\";a:1:{s:7:\"default\";s:74:\"https://s.w.org/plugins/geopattern-icon/rewrite-rules-inspector_e2e3e4.svg\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:79:\"https://ps.w.org/rewrite-rules-inspector/assets/banner-1544x500.png?rev=2533834\";s:2:\"1x\";s:78:\"https://ps.w.org/rewrite-rules-inspector/assets/banner-772x250.png?rev=2533843\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"3.1\";}s:43:\"the-events-calendar/the-events-calendar.php\";O:8:\"stdClass\":10:{s:2:\"id\";s:33:\"w.org/plugins/the-events-calendar\";s:4:\"slug\";s:19:\"the-events-calendar\";s:6:\"plugin\";s:43:\"the-events-calendar/the-events-calendar.php\";s:11:\"new_version\";s:5:\"6.1.1\";s:3:\"url\";s:50:\"https://wordpress.org/plugins/the-events-calendar/\";s:7:\"package\";s:68:\"https://downloads.wordpress.org/plugin/the-events-calendar.6.1.1.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:72:\"https://ps.w.org/the-events-calendar/assets/icon-256x256.gif?rev=2516440\";s:2:\"1x\";s:72:\"https://ps.w.org/the-events-calendar/assets/icon-128x128.gif?rev=2516440\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:75:\"https://ps.w.org/the-events-calendar/assets/banner-1544x500.png?rev=2257622\";s:2:\"1x\";s:74:\"https://ps.w.org/the-events-calendar/assets/banner-772x250.png?rev=2257622\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:5:\"5.8.6\";}s:27:\"woocommerce/woocommerce.php\";O:8:\"stdClass\":12:{s:2:\"id\";s:25:\"w.org/plugins/woocommerce\";s:4:\"slug\";s:11:\"woocommerce\";s:6:\"plugin\";s:27:\"woocommerce/woocommerce.php\";s:11:\"new_version\";s:5:\"7.8.0\";s:3:\"url\";s:42:\"https://wordpress.org/plugins/woocommerce/\";s:7:\"package\";s:60:\"https://downloads.wordpress.org/plugin/woocommerce.7.8.0.zip\";s:5:\"icons\";a:2:{s:2:\"2x\";s:64:\"https://ps.w.org/woocommerce/assets/icon-256x256.gif?rev=2869506\";s:2:\"1x\";s:64:\"https://ps.w.org/woocommerce/assets/icon-128x128.gif?rev=2869506\";}s:7:\"banners\";a:2:{s:2:\"2x\";s:67:\"https://ps.w.org/woocommerce/assets/banner-1544x500.png?rev=2366418\";s:2:\"1x\";s:66:\"https://ps.w.org/woocommerce/assets/banner-772x250.png?rev=2366418\";}s:11:\"banners_rtl\";a:0:{}s:8:\"requires\";s:3:\"6.1\";s:6:\"tested\";s:5:\"6.2.2\";s:12:\"requires_php\";s:3:\"7.3\";}}}','no'),(162,'schema-ActionScheduler_StoreSchema','7.0.1692276901','yes'),(163,'schema-ActionScheduler_LoggerSchema','3.0.1692276901','yes'),(172,'theme_mods_twentytwentytwo','a:1:{s:16:\"sidebars_widgets\";a:2:{s:4:\"time\";i:1692276901;s:4:\"data\";a:3:{s:19:\"wp_inactive_widgets\";a:0:{}s:9:\"sidebar-1\";a:3:{i:0;s:7:\"block-2\";i:1;s:7:\"block-3\";i:2;s:7:\"block-4\";}s:9:\"sidebar-2\";a:2:{i:0;s:7:\"block-5\";i:1;s:7:\"block-6\";}}}}','no'),(173,'current_theme','Twenty Twenty','yes'),(174,'theme_switched','','yes'),(177,'theme_mods_twentytwenty','a:1:{s:18:\"nav_menu_locations\";a:0:{}}','yes'),(180,'wp_attachment_pages_enabled','1','yes'),(181,'db_upgraded','1','yes');
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
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_postmeta`
--

LOCK TABLES `wp_postmeta` WRITE;
/*!40000 ALTER TABLE `wp_postmeta` DISABLE KEYS */;
INSERT INTO `wp_postmeta` VALUES (1,2,'_wp_page_template','default'),(2,3,'_wp_page_template','default'),(3,4,'email_recipient','customer'),(4,4,'email_template','ticket'),(5,4,'email_version','6.0.0-dev'),(6,5,'email_recipient','customer'),(7,5,'email_template','rsvp'),(8,5,'email_version','6.0.0-dev'),(9,6,'email_recipient','customer'),(10,6,'email_template','rsvp-not-going'),(11,6,'email_version','6.0.0-dev'),(12,7,'email_recipient','purchaser'),(13,7,'email_template','customer-purchase-receipt'),(14,7,'email_version','6.0.0-dev'),(15,8,'email_recipient','admin'),(16,8,'email_template','admin-failed-order'),(17,8,'email_version','6.0.0-dev'),(18,9,'email_recipient','admin'),(19,9,'email_template','admin-new-order'),(20,9,'email_version','6.0.0-dev');
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_posts`
--

LOCK TABLES `wp_posts` WRITE;
/*!40000 ALTER TABLE `wp_posts` DISABLE KEYS */;
INSERT INTO `wp_posts` VALUES (1,1,'2023-03-28 13:54:49','2023-03-28 13:54:49','<!-- wp:paragraph -->\n<p>Welcome to WordPress. This is your first post. Edit or delete it, then start writing!</p>\n<!-- /wp:paragraph -->','Hello world!','','publish','open','open','','hello-world','','','2023-03-28 13:54:49','2023-03-28 13:54:49','',0,'https://wordpress.test/?p=1',0,'post','',1),(2,1,'2023-03-28 13:54:49','2023-03-28 13:54:49','<!-- wp:paragraph -->\n<p>This is an example page. It\'s different from a blog post because it will stay in one place and will show up in your site navigation (in most themes). Most people start with an About page that introduces them to potential site visitors. It might say something like this:</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>Hi there! I\'m a bike messenger by day, aspiring actor by night, and this is my website. I live in Los Angeles, have a great dog named Jack, and I like pi&#241;a coladas. (And gettin\' caught in the rain.)</p></blockquote>\n<!-- /wp:quote -->\n\n<!-- wp:paragraph -->\n<p>...or something like this:</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>The XYZ Doohickey Company was founded in 1971, and has been providing quality doohickeys to the public ever since. Located in Gotham City, XYZ employs over 2,000 people and does all kinds of awesome things for the Gotham community.</p></blockquote>\n<!-- /wp:quote -->\n\n<!-- wp:paragraph -->\n<p>As a new WordPress user, you should go to <a href=\"https://wordpress.test/wp-admin/\">your dashboard</a> to delete this page and create new pages for your content. Have fun!</p>\n<!-- /wp:paragraph -->','Sample Page','','publish','closed','open','','sample-page','','','2023-03-28 13:54:49','2023-03-28 13:54:49','',0,'https://wordpress.test/?page_id=2',0,'page','',0),(3,1,'2023-03-28 13:54:49','2023-03-28 13:54:49','<!-- wp:heading --><h2>Who we are</h2><!-- /wp:heading --><!-- wp:paragraph --><p><strong class=\"privacy-policy-tutorial\">Suggested text: </strong>Our website address is: https://wordpress.test.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Comments</h2><!-- /wp:heading --><!-- wp:paragraph --><p><strong class=\"privacy-policy-tutorial\">Suggested text: </strong>When visitors leave comments on the site we collect the data shown in the comments form, and also the visitor&#8217;s IP address and browser user agent string to help spam detection.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>An anonymized string created from your email address (also called a hash) may be provided to the Gravatar service to see if you are using it. The Gravatar service privacy policy is available here: https://automattic.com/privacy/. After approval of your comment, your profile picture is visible to the public in the context of your comment.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Media</h2><!-- /wp:heading --><!-- wp:paragraph --><p><strong class=\"privacy-policy-tutorial\">Suggested text: </strong>If you upload images to the website, you should avoid uploading images with embedded location data (EXIF GPS) included. Visitors to the website can download and extract any location data from images on the website.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Cookies</h2><!-- /wp:heading --><!-- wp:paragraph --><p><strong class=\"privacy-policy-tutorial\">Suggested text: </strong>If you leave a comment on our site you may opt-in to saving your name, email address and website in cookies. These are for your convenience so that you do not have to fill in your details again when you leave another comment. These cookies will last for one year.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>If you visit our login page, we will set a temporary cookie to determine if your browser accepts cookies. This cookie contains no personal data and is discarded when you close your browser.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>When you log in, we will also set up several cookies to save your login information and your screen display choices. Login cookies last for two days, and screen options cookies last for a year. If you select &quot;Remember Me&quot;, your login will persist for two weeks. If you log out of your account, the login cookies will be removed.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>If you edit or publish an article, an additional cookie will be saved in your browser. This cookie includes no personal data and simply indicates the post ID of the article you just edited. It expires after 1 day.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Embedded content from other websites</h2><!-- /wp:heading --><!-- wp:paragraph --><p><strong class=\"privacy-policy-tutorial\">Suggested text: </strong>Articles on this site may include embedded content (e.g. videos, images, articles, etc.). Embedded content from other websites behaves in the exact same way as if the visitor has visited the other website.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>These websites may collect data about you, use cookies, embed additional third-party tracking, and monitor your interaction with that embedded content, including tracking your interaction with the embedded content if you have an account and are logged in to that website.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Who we share your data with</h2><!-- /wp:heading --><!-- wp:paragraph --><p><strong class=\"privacy-policy-tutorial\">Suggested text: </strong>If you request a password reset, your IP address will be included in the reset email.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>How long we retain your data</h2><!-- /wp:heading --><!-- wp:paragraph --><p><strong class=\"privacy-policy-tutorial\">Suggested text: </strong>If you leave a comment, the comment and its metadata are retained indefinitely. This is so we can recognize and approve any follow-up comments automatically instead of holding them in a moderation queue.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>For users that register on our website (if any), we also store the personal information they provide in their user profile. All users can see, edit, or delete their personal information at any time (except they cannot change their username). Website administrators can also see and edit that information.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>What rights you have over your data</h2><!-- /wp:heading --><!-- wp:paragraph --><p><strong class=\"privacy-policy-tutorial\">Suggested text: </strong>If you have an account on this site, or have left comments, you can request to receive an exported file of the personal data we hold about you, including any data you have provided to us. You can also request that we erase any personal data we hold about you. This does not include any data we are obliged to keep for administrative, legal, or security purposes.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Where your data is sent</h2><!-- /wp:heading --><!-- wp:paragraph --><p><strong class=\"privacy-policy-tutorial\">Suggested text: </strong>Visitor comments may be checked through an automated spam detection service.</p><!-- /wp:paragraph -->','Privacy Policy','','draft','closed','open','','privacy-policy','','','2023-03-28 13:54:49','2023-03-28 13:54:49','',0,'https://wordpress.test/?page_id=3',0,'page','',0),(4,0,'2023-03-28 09:56:41','2023-03-28 13:56:41','','Ticket Email','','publish','closed','closed','','tec_tickets_emails_ticket','','','2023-03-28 09:56:41','2023-03-28 13:56:41','',0,'https://wordpress.test/?post_type=tec_tickets_emails&p=4',0,'tec_tickets_emails','',0),(5,0,'2023-03-28 09:56:41','2023-03-28 13:56:41','','RSVP Email','','publish','closed','closed','','tec_tickets_emails_rsvp','','','2023-03-28 09:56:41','2023-03-28 13:56:41','',0,'https://wordpress.test/?post_type=tec_tickets_emails&p=5',0,'tec_tickets_emails','',0),(6,0,'2023-03-28 09:56:41','2023-03-28 13:56:41','','RSVP &quot;Not Going&quot; Email','','publish','closed','closed','','tec_tickets_emails_rsvp_not_going','','','2023-03-28 09:56:41','2023-03-28 13:56:41','',0,'https://wordpress.test/?post_type=tec_tickets_emails&p=6',0,'tec_tickets_emails','',0),(7,0,'2023-03-28 09:56:41','2023-03-28 13:56:41','','Purchase Receipt','','publish','closed','closed','','tec_tickets_emails_purchase_receipt','','','2023-03-28 09:56:41','2023-03-28 13:56:41','',0,'https://wordpress.test/?post_type=tec_tickets_emails&p=7',0,'tec_tickets_emails','',0),(8,0,'2023-03-28 09:56:41','2023-03-28 13:56:41','','Failed Order','','publish','closed','closed','','tec_tickets_emails_failed_order','','','2023-03-28 09:56:41','2023-03-28 13:56:41','',0,'https://wordpress.test/?post_type=tec_tickets_emails&p=8',0,'tec_tickets_emails','',0),(9,0,'2023-03-28 09:56:41','2023-03-28 13:56:41','','Completed Order','','publish','closed','closed','','tec_tickets_emails_completed_order','','','2023-03-28 09:56:41','2023-03-28 13:56:41','',0,'https://wordpress.test/?post_type=tec_tickets_emails&p=9',0,'tec_tickets_emails','',0);
/*!40000 ALTER TABLE `wp_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_tec_events`
--

DROP TABLE IF EXISTS `wp_tec_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_tec_events` (
  `event_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `start_date` varchar(19) NOT NULL,
  `end_date` varchar(19) DEFAULT NULL,
  `timezone` varchar(30) NOT NULL DEFAULT 'UTC',
  `start_date_utc` varchar(19) NOT NULL,
  `end_date_utc` varchar(19) DEFAULT NULL,
  `duration` mediumint(30) DEFAULT 7200,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `hash` varchar(40) NOT NULL,
  `rset` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`event_id`),
  UNIQUE KEY `post_id` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_tec_events`
--

LOCK TABLES `wp_tec_events` WRITE;
/*!40000 ALTER TABLE `wp_tec_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_tec_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_tec_occurrences`
--

DROP TABLE IF EXISTS `wp_tec_occurrences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_tec_occurrences` (
  `occurrence_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `post_id` bigint(20) unsigned NOT NULL,
  `start_date` varchar(19) NOT NULL,
  `start_date_utc` varchar(19) NOT NULL,
  `end_date` varchar(19) NOT NULL,
  `end_date_utc` varchar(19) NOT NULL,
  `duration` mediumint(30) DEFAULT 7200,
  `hash` varchar(40) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `has_recurrence` tinyint(1) DEFAULT 0,
  `sequence` bigint(20) unsigned DEFAULT 0,
  `is_rdate` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`occurrence_id`),
  UNIQUE KEY `hash` (`hash`),
  KEY `event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_tec_occurrences`
--

LOCK TABLES `wp_tec_occurrences` WRITE;
/*!40000 ALTER TABLE `wp_tec_occurrences` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_tec_occurrences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_tec_posts_and_ticket_groups`
--

DROP TABLE IF EXISTS `wp_tec_posts_and_ticket_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_tec_posts_and_ticket_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `group_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_tec_posts_and_ticket_groups`
--

LOCK TABLES `wp_tec_posts_and_ticket_groups` WRITE;
/*!40000 ALTER TABLE `wp_tec_posts_and_ticket_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_tec_posts_and_ticket_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_tec_series_relationships`
--

DROP TABLE IF EXISTS `wp_tec_series_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_tec_series_relationships` (
  `relationship_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `series_post_id` bigint(20) unsigned NOT NULL,
  `event_id` bigint(20) unsigned NOT NULL,
  `event_post_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`relationship_id`),
  KEY `series_post_id` (`series_post_id`),
  KEY `event_post_id` (`event_post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_tec_series_relationships`
--

LOCK TABLES `wp_tec_series_relationships` WRITE;
/*!40000 ALTER TABLE `wp_tec_series_relationships` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_tec_series_relationships` ENABLE KEYS */;
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
-- Table structure for table `wp_tec_ticket_groups`
--

DROP TABLE IF EXISTS `wp_tec_ticket_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_tec_ticket_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL DEFAULT '',
  `data` text NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_tec_ticket_groups`
--

LOCK TABLES `wp_tec_ticket_groups` WRITE;
/*!40000 ALTER TABLE `wp_tec_ticket_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_tec_ticket_groups` ENABLE KEYS */;
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
INSERT INTO `wp_term_relationships` VALUES (1,1,0);
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_usermeta`
--

LOCK TABLES `wp_usermeta` WRITE;
/*!40000 ALTER TABLE `wp_usermeta` DISABLE KEYS */;
INSERT INTO `wp_usermeta` VALUES (1,1,'nickname','admin'),(2,1,'first_name',''),(3,1,'last_name',''),(4,1,'description',''),(5,1,'rich_editing','true'),(6,1,'syntax_highlighting','true'),(7,1,'comment_shortcuts','false'),(8,1,'admin_color','fresh'),(9,1,'use_ssl','0'),(10,1,'show_admin_bar_front','true'),(11,1,'locale',''),(12,1,'wp_capabilities','a:1:{s:13:\"administrator\";b:1;}'),(13,1,'wp_user_level','10'),(14,1,'dismissed_wp_pointers',''),(15,1,'show_welcome_panel','1');
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
INSERT INTO `wp_users` VALUES (1,'admin','$P$Bo4.85BShf4qgOmSZkAy/VpkjnnkUR1','admin','admin@wordpress.test','https://wordpress.test','2023-03-28 13:54:49','',0,'admin');
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

-- Dump completed on 2024-12-20 15:44:21
