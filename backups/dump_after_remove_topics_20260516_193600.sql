mysqldump: [Warning] Using a password on the command line interface can be insecure.
-- MySQL dump 10.13  Distrib 5.7.24, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: inseptum
-- ------------------------------------------------------
-- Server version	5.7.24

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
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL,
  `password` varchar(150) NOT NULL,
  `role` enum('admin','moderator','spectator') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','2b\n10\n10N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy','admin');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `articles`
--

DROP TABLE IF EXISTS `articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(30) NOT NULL,
  `description` text NOT NULL,
  `file_path` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `test_id` int(11) DEFAULT NULL,
  `task_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_test_id` (`test_id`),
  KEY `idx_task_id` (`task_id`),
  CONSTRAINT `articles_ibfk_2` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `articles_ibfk_3` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `articles`
--

LOCK TABLES `articles` WRITE;
/*!40000 ALTER TABLE `articles` DISABLE KEYS */;
INSERT INTO `articles` VALUES (1,'Как работать с bootstrap','Статья с основами подключения bootstrap в ваш проект','bootstrap_connect.docx','2026-02-13 17:41:15',1,1),(2,'атрибуты bootstrapз','атрибуты bootstrap','bootstrap_connect — копия.docx','2026-02-28 21:00:00',NULL,NULL),(3,'dasdsd','dasdasd','bootstrap_connect — копия.docx','2026-04-02 09:56:33',NULL,NULL);
/*!40000 ALTER TABLE `articles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `module_types`
--

DROP TABLE IF EXISTS `module_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `module_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(64) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(80) NOT NULL,
  `highlight_language` varchar(40) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_module_types_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `module_types`
--

LOCK TABLES `module_types` WRITE;
/*!40000 ALTER TABLE `module_types` DISABLE KEYS */;
INSERT INTO `module_types` VALUES (1,'bootstrap','Bootstrap','FaBootstrap','css','#7952B3','2026-05-12 23:04:23','2026-05-12 23:04:23'),(2,'html','HTML','FaHtml5','html','#E34F26','2026-05-12 23:04:23','2026-05-12 23:04:23'),(3,'php','PHP','FaPhp','php','#777BB4','2026-05-12 23:04:23','2026-05-12 23:04:23'),(4,'javascript','JavaScript','FaJs','javascript','#F7DF1E','2026-05-12 23:04:23','2026-05-12 23:04:23'),(5,'database','╨С╨░╨╖╤Л ╨┤╨░╨╜╨╜╤Л╤Е','FaDatabase','sql','#00758F','2026-05-12 23:04:23','2026-05-12 23:04:23'),(6,'structure','╨б╤В╤А╤Г╨║╤В╤Г╤А╤Л ╨┤╨░╨╜╨╜╤Л╤Е','TbBinaryTree','javascript','#4CAF50','2026-05-12 23:04:23','2026-05-12 23:04:23');
/*!40000 ALTER TABLE `module_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modules`
--

DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(20) NOT NULL,
  `description` text NOT NULL,
  `module_type_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_modules_module_type_id` (`module_type_id`),
  CONSTRAINT `fk_modules_module_type` FOREIGN KEY (`module_type_id`) REFERENCES `module_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modules`
--

LOCK TABLES `modules` WRITE;
/*!40000 ALTER TABLE `modules` DISABLE KEYS */;
INSERT INTO `modules` VALUES (1,'Bootstrap','Материалы по Bootstrap: применение, компоненты и адаптивная верстка',1),(2,'Javascript','Материалы по JavaScript: основы, фреймворки и современные практики',4),(3,'HTML','Материалы по HTML: семантическая верстка и современные стандарты',2),(4,'PHP','Материалы по PHP: серверное программирование и фреймворки',3),(5,'Database','Материалы по базам данных: SQL, проектирование и оптимизация',5),(6,'Structure','Материалы по структурам данных и алгоритмам',6),(7,'Git','Основа управления технологией контроля версий',NULL);
/*!40000 ALTER TABLE `modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(5) NOT NULL,
  `u_id` int(5) NOT NULL,
  `p_id` int(3) NOT NULL,
  `date` date NOT NULL,
  `payment` enum('cash','card') NOT NULL,
  `status` enum('Новая','Банкет назначен','Банкет завершен') NOT NULL,
  `created` date NOT NULL,
  `review` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,2,1,'2026-05-09','cash','Банкет завершен','2026-05-07','sdfdsfsdf656546'),(2,2,3,'2026-05-13','card','Банкет назначен','2026-05-07',''),(3,2,4,'2026-05-22','card','Новая','2026-05-07','');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (1,'Задача на создание Bootstrap классов','Создайте bootstrap класс и используйте его','easy','2026-04-17 11:42:50');
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tests`
--

DROP TABLE IF EXISTS `tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(40) NOT NULL,
  `description` text NOT NULL,
  `file_path` varchar(140) NOT NULL,
  `time_limit` int(4) NOT NULL DEFAULT '20',
  `question_count` int(5) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tests`
--

LOCK TABLES `tests` WRITE;
/*!40000 ALTER TABLE `tests` DISABLE KEYS */;
INSERT INTO `tests` VALUES (1,'Основы Bootstrap','Тест по теме Основы Bootstrap раскрывающий и дополняющий данную тему','bootstrap_connect',20,4,'2026-03-01 20:58:26'),(5,'da','dsa','1775152339_bootstrap_connect — копия',12,4,'2026-04-02 17:52:19');
/*!40000 ALTER TABLE `tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_article_favorite`
--

DROP TABLE IF EXISTS `user_article_favorite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_article_favorite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_article_favorite`
--

LOCK TABLES `user_article_favorite` WRITE;
/*!40000 ALTER TABLE `user_article_favorite` DISABLE KEYS */;
INSERT INTO `user_article_favorite` VALUES (1,1,1,'2026-03-19 10:36:00');
/*!40000 ALTER TABLE `user_article_favorite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_article_read`
--

DROP TABLE IF EXISTS `user_article_read`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_article_read` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `progress_percent` int(3) DEFAULT '0' COMMENT 'Прогресс чтения 0-100',
  `last_position` int(11) DEFAULT NULL COMMENT 'Позиция скролла',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_article` (`user_id`,`article_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_article_id` (`article_id`),
  KEY `idx_is_read` (`is_read`),
  CONSTRAINT `fk_user_article_read_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_article_read_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_article_read`
--

LOCK TABLES `user_article_read` WRITE;
/*!40000 ALTER TABLE `user_article_read` DISABLE KEYS */;
INSERT INTO `user_article_read` VALUES (1,33,1,0,'2026-02-28 16:46:28',0,NULL,'2026-02-28 15:31:54','2026-02-28 16:57:58'),(2,26,1,1,'2026-03-02 17:15:05',0,NULL,'2026-02-28 15:42:18','2026-03-02 17:15:05'),(3,26,2,0,'2026-03-03 12:34:53',0,NULL,'2026-03-03 12:34:53',NULL),(4,1,1,1,'2026-03-18 13:43:56',0,NULL,'2026-03-10 14:24:27','2026-03-18 13:43:56'),(5,1,2,0,'2026-03-25 12:30:13',0,NULL,'2026-03-25 12:30:13',NULL);
/*!40000 ALTER TABLE `user_article_read` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_test_favorite`
--

DROP TABLE IF EXISTS `user_test_favorite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_test_favorite` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_test_favorite`
--

LOCK TABLES `user_test_favorite` WRITE;
/*!40000 ALTER TABLE `user_test_favorite` DISABLE KEYS */;
INSERT INTO `user_test_favorite` VALUES (37,26,1,'2026-03-10 10:11:56'),(38,1,1,'2026-03-15 15:13:51');
/*!40000 ALTER TABLE `user_test_favorite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_test_passed`
--

DROP TABLE IF EXISTS `user_test_passed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_test_passed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `is_passed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_test_passed`
--

LOCK TABLES `user_test_passed` WRITE;
/*!40000 ALTER TABLE `user_test_passed` DISABLE KEYS */;
INSERT INTO `user_test_passed` VALUES (1,26,1,1,'2026-03-03 18:29:17',NULL),(2,1,1,1,'2026-03-18 17:53:08',NULL);
/*!40000 ALTER TABLE `user_test_passed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL,
  `password` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'test_user','123','2024-01-15 07:30:00'),(26,'bob','$2y$10$oRf4ymjpxtCTCU30iJNdHOf7le.lNykde54kS1RBk.GmKxDKcE4xy','2026-02-09 07:37:20'),(27,'bob1','$2y$10$IRy7Nf5Dsh96sstCPZooEe/8.hafmtc46959T/jD9.kBtRNs7wuma','2026-02-09 07:38:25'),(28,'фв','$2y$10$2vGZTQG77pSynCmwIs5kpOqg1pioJetqEnb3k2V1NWmcNYktRphS.','2026-02-09 07:40:33'),(29,'ек','$2y$10$DvaGIiCsHA77XWKO6uCFieUIPc4LkfaUTcuw6dgPR7OqbyRX3Op5C','2026-02-09 07:41:46'),(30,'Иван','$2y$10$xfGnQkKrViN0.Q/pEFzafO2MYj9Zm9/XWqCOmGPzacTP7pgv71Jvu','2026-02-09 07:44:14'),(31,'Иван--','$2y$10$thP5vu88t/x4abMapFuzmePp6KCqX3O/WCOLGrM0QGE7AQ8/COZbK','2026-02-09 07:45:24'),(32,'Иван\\u0000Петров','$2y$10$XL0G87u4PoXokHDQWEe9w.PSHX6MkRelZF267CeYwdqnnRAVYazwC','2026-02-09 07:45:47'),(33,'Иван\\u202E','$2y$10$wq8.Kwu7EM4mb78G2MgfV.JP1sB2THcm58/9rC1Si6lO0yjdomsAe','2026-02-09 07:45:59'),(34,'rwqw','$2y$10$FT9oPVEYsipotocNZePZ0ufVap0V90Sw1HrWFcnrIWxDayuWDecdS','2026-02-09 08:04:57'),(35,'Роман','$2y$10$doeNCSjYUczWerO5kQIMhO0JNJxzT1hri/8jNzZb5cG/5lZdwHeeS','2026-02-21 13:51:08'),(36,'tea','$2y$10$djc2tgi/dj/2U/CobuacWOoXQB4i14ezH2qd85.kHrHaDeSw2MbLa','2026-02-22 10:23:37'),(37,'feas','$2y$10$1drJkJ63VGaQlZwoXowUluqvyFPwumn4KPHpa/rBMZno/SOiWL7T.','2026-02-22 10:24:16'),(38,'dawdawd','$2y$10$ZeSq3N6qUEqAXDStE7.AUevapqbzTCUC0Hv27/pDW41TeHFijqsBm','2026-02-22 10:25:07'),(39,'dadasd','$2y$10$RsbjVIWpl7YhYg5tNfCIV..whFCHDdEqbaJ6xlT7shIJ6AYkx8dxq','2026-02-22 10:26:59'),(40,'hndgd','$2y$10$ihDOYPxZwKsuW8FwXNlg0.PrNxTmT80Ar0.QLvtQ4XyB4S9CrHXy2','2026-02-22 10:30:24'),(41,'пыам','$2y$10$8bMce2ZyQZTqRp6X/xqRueCvGWvdMgiNLvGQ40xzGwNyJZaQsssxO','2026-02-22 10:36:55'),(42,'reere','$2y$10$4.KR1OEPg1nq523q1g.bXuTln/ubkJ.vHNgkivVC7odXMKcbTUCEG','2026-02-23 11:18:18'),(43,'dasgegd','$2y$10$gOcrY.S84ljukZOvJcgPJOw/SpFusJRjoCMr1fwhigoujX9wEATr2','2026-02-23 11:46:14'),(44,'qwergdbnjyuhgfrjkuyt','$2y$10$92Sg.IWMIn1b/14CQjW6qOxV6.bJlzFXic3MsAu8ck20itHEfOXsu','2026-02-23 11:56:11'),(45,'рыфаыафы','$2y$10$.R7.xgWJfX3E/.cjFVpZ5O7nPNj4YITZHUGWgpx7ic3ZnFn4pEUmy','2026-02-23 20:17:04'),(46,'qa_tst_%ts%','$2y$10$/9cgDCo3oUf66dN.volGie..ZIeg7uDpJzsJMJlxWXF/.NI81q18.','2026-05-10 20:58:34'),(47,'qa_tst_8888','$2y$10$OJB.kXx.ve/6KiONF7mZyek4RCcStNIPZuDDVPHSW/PO2D2jZq5Vm','2026-05-10 20:58:46');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-16 19:37:16
