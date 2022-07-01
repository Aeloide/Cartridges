/*
SQLyog Ultimate v9.01 
MySQL - 5.5.5-10.5.16-MariaDB : Database - cartriges_new
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`cartriges_new` /*!40100 DEFAULT CHARACTER SET latin1 */;

/*Table structure for table `recycling_admins` */

DROP TABLE IF EXISTS `recycling_admins`;

CREATE TABLE `recycling_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adminName` varchar(100) DEFAULT NULL,
  `pass` text DEFAULT NULL,
  `salt` text DEFAULT NULL,
  `datelogin` datetime DEFAULT NULL,
  `datereg` datetime DEFAULT NULL,
  `lastip` bigint(20) DEFAULT NULL,
  `superAdm` tinyint(4) DEFAULT 2,
  `adminStatus` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1;

/*Data for the table `recycling_admins` */

/*Table structure for table `recycling_admins_offices` */

DROP TABLE IF EXISTS `recycling_admins_offices`;

CREATE TABLE `recycling_admins_offices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `office_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `recycling_admins_offices` */

/*Table structure for table `recycling_brands` */

DROP TABLE IF EXISTS `recycling_brands`;

CREATE TABLE `recycling_brands` (
  `id` int(10) unsigned NOT NULL,
  `brandName` text CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `recycling_brands` */

insert  into `recycling_brands`(`id`,`brandName`) values (1,'Samsung (original)'),(2,'HP (Original)'),(3,'Kyocera (Original)'),(4,'Brother (Original)'),(5,'Xerox (Original)'),(6,'Canon (Original)'),(7,'NV-Print'),(8,'Sakura');

/*Table structure for table `recycling_breaks` */

DROP TABLE IF EXISTS `recycling_breaks`;

CREATE TABLE `recycling_breaks` (
  `id` int(10) unsigned NOT NULL,
  `breakName` tinytext CHARACTER SET utf8 DEFAULT NULL,
  `breakDate` int(10) unsigned DEFAULT NULL,
  `officeId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `recycling_breaks` */

/*Table structure for table `recycling_breaks_content` */

DROP TABLE IF EXISTS `recycling_breaks_content`;

CREATE TABLE `recycling_breaks_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `breakId` int(11) DEFAULT NULL,
  `eventId` int(11) DEFAULT NULL,
  `checkId` int(11) DEFAULT NULL,
  `worksCount` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `recycling_breaks_content` */

/*Table structure for table `recycling_cartridges` */

DROP TABLE IF EXISTS `recycling_cartridges`;

CREATE TABLE `recycling_cartridges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cartridge_model_id` int(11) DEFAULT NULL,
  `brandId` int(11) DEFAULT NULL,
  `office_id` int(11) NOT NULL DEFAULT 1,
  `inv_num` int(11) DEFAULT NULL,
  `sn` varchar(20) DEFAULT NULL,
  `status_id` tinyint(3) NOT NULL DEFAULT 1 COMMENT '0 - пустой; 2 - в резерве; 4 - в принтере',
  PRIMARY KEY (`id`),
  KEY `cartridge_model_id` (`cartridge_model_id`)
) ENGINE=Aria DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1;

/*Data for the table `recycling_cartridges` */

/*Table structure for table `recycling_cartridges_models` */

DROP TABLE IF EXISTS `recycling_cartridges_models`;

CREATE TABLE `recycling_cartridges_models` (
  `id` int(11) NOT NULL,
  `cartridgeName_OLD` tinytext CHARACTER SET utf8 DEFAULT NULL,
  `cartridgeName` tinytext CHARACTER SET utf8 DEFAULT NULL,
  `cartridgeCapacity` int(11) DEFAULT NULL,
  `cartridgeColor` int(11) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `recycling_cartridges_models` */

insert  into `recycling_cartridges_models`(`id`,`cartridgeName_OLD`,`cartridgeName`,`cartridgeCapacity`,`cartridgeColor`) values (1,'53X','Q7553X',7000,1),(2,'53A','Q7553A',3000,1),(3,'80X','CF280X',6900,1),(4,'80A','CF280A',2700,1),(5,'26X','CF226X',9000,1),(6,'81A','CF281A',10500,1),(7,'12A','Q2612A',2000,1),(8,'36A','CB436A',2000,1),(9,'92A','C4092A ',2500,1),(10,'15A','C7115A',2500,1),(11,'15X','C7115X',3500,1),(12,'42X','Q5942X',20000,1),(13,'90X','CE390X',24000,1),(14,'64X','CC364X',24000,1),(15,'E30','1491A003',4000,1),(16,'90A','CE390A',10000,1),(17,'11X','Q6511X',12000,1),(18,'64A','CC364A',10000,1),(19,'42A','Q5942A',10000,1);

/*Table structure for table `recycling_cartridges_stasuses` */

DROP TABLE IF EXISTS `recycling_cartridges_stasuses`;

CREATE TABLE `recycling_cartridges_stasuses` (
  `id` tinyint(3) NOT NULL,
  `stasus` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1;

/*Data for the table `recycling_cartridges_stasuses` */

insert  into `recycling_cartridges_stasuses`(`id`,`stasus`) values (3,'выведен из оборота'),(2,'заправлен и находится в резерве'),(0,'ожидает заправщика'),(1,'заправляется'),(4,'установлен в принтере');

/*Table structure for table `recycling_cartridges_worknames` */

DROP TABLE IF EXISTS `recycling_cartridges_worknames`;

CREATE TABLE `recycling_cartridges_worknames` (
  `id` int(11) NOT NULL,
  `workName` text CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `recycling_cartridges_worknames` */

insert  into `recycling_cartridges_worknames`(`id`,`workName`) values (1,'заправка'),(2,'замена барабана'),(3,'замена магнитного вала'),(4,'замена ракеля'),(5,'мелкий ремонт');

/*Table structure for table `recycling_cartridges_works` */

DROP TABLE IF EXISTS `recycling_cartridges_works`;

CREATE TABLE `recycling_cartridges_works` (
  `eventId` int(11) DEFAULT NULL,
  `workId` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `recycling_cartridges_works` */

/*Table structure for table `recycling_checks` */

DROP TABLE IF EXISTS `recycling_checks`;

CREATE TABLE `recycling_checks` (
  `id` int(11) NOT NULL,
  `checkName` text CHARACTER SET utf8 DEFAULT NULL,
  `checkDate` date DEFAULT NULL,
  `checkDateAdded` date DEFAULT NULL,
  `checkSumm` float DEFAULT NULL,
  `companyRefId` int(11) DEFAULT NULL,
  `isPay` int(11) DEFAULT 0,
  `companyId` int(11) DEFAULT NULL,
  `breakId` int(11) DEFAULT NULL,
  `fileName` text CHARACTER SET utf8 DEFAULT NULL,
  `fileSize` int(11) DEFAULT NULL,
  `mimeType` text CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `recycling_checks` */

/*Table structure for table `recycling_companies` */

DROP TABLE IF EXISTS `recycling_companies`;

CREATE TABLE `recycling_companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company` varchar(100) DEFAULT NULL,
  `officeId` int(11) DEFAULT NULL,
  `inn` bigint(20) DEFAULT 0,
  `companyType` int(11) DEFAULT 2,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1;

/*Data for the table `recycling_companies` */

/*Table structure for table `recycling_events` */

DROP TABLE IF EXISTS `recycling_events`;

CREATE TABLE `recycling_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `office_id` int(11) NOT NULL DEFAULT 1,
  `dt` int(11) unsigned DEFAULT NULL,
  `printer_id` int(11) NOT NULL DEFAULT 0,
  `cartridge_out_id` int(11) NOT NULL DEFAULT 0,
  `cartridge_in_id` int(11) NOT NULL DEFAULT 0,
  `cartridge_new_id` int(11) NOT NULL DEFAULT 0,
  `reason_id` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `status_id` tinyint(3) unsigned DEFAULT NULL,
  `pages` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `printer_id` (`printer_id`)
) ENGINE=Aria DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1;

/*Data for the table `recycling_events` */

/*Table structure for table `recycling_events_admins` */

DROP TABLE IF EXISTS `recycling_events_admins`;

CREATE TABLE `recycling_events_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `dt` int(11) unsigned DEFAULT NULL,
  `admin_id` int(11) NOT NULL,
  `event_typ_id` int(11) NOT NULL,
  `remark` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1;

/*Data for the table `recycling_events_admins` */

/*Table structure for table `recycling_events_reasons` */

DROP TABLE IF EXISTS `recycling_events_reasons`;

CREATE TABLE `recycling_events_reasons` (
  `id` tinyint(3) unsigned NOT NULL,
  `reason` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1;

/*Data for the table `recycling_events_reasons` */

insert  into `recycling_events_reasons`(`id`,`reason`) values (1,'закончился тонер'),(2,'рекламация'),(3,'покупка нового'),(4,'покупка б/у');

/*Table structure for table `recycling_events_stasuses` */

DROP TABLE IF EXISTS `recycling_events_stasuses`;

CREATE TABLE `recycling_events_stasuses` (
  `id` tinyint(3) unsigned NOT NULL,
  `stasus` varchar(30) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1;

/*Data for the table `recycling_events_stasuses` */

insert  into `recycling_events_stasuses`(`id`,`stasus`) values (0,'ожидает заправщика'),(1,'передан заправщику'),(2,'резерв'),(3,'выведен из оборота'),(4,'оплата'),(5,'введён в оборот');

/*Table structure for table `recycling_offices` */

DROP TABLE IF EXISTS `recycling_offices`;

CREATE TABLE `recycling_offices` (
  `id` int(11) NOT NULL,
  `officeName` tinytext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1;

/*Data for the table `recycling_offices` */

/*Table structure for table `recycling_printers` */

DROP TABLE IF EXISTS `recycling_printers`;

CREATE TABLE `recycling_printers` (
  `id` int(11) unsigned NOT NULL,
  `printerName` varchar(100) DEFAULT NULL,
  `modelId` int(11) unsigned DEFAULT NULL,
  `officeId` int(11) unsigned DEFAULT 1,
  `companyId` int(11) unsigned DEFAULT NULL,
  `sn` varchar(50) DEFAULT NULL,
  `ip` text DEFAULT NULL,
  `cartridge_inside` int(11) unsigned DEFAULT NULL,
  `statusId` tinyint(3) unsigned DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `company_id` (`companyId`)
) ENGINE=Aria DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1;

/*Data for the table `recycling_printers` */

/*Table structure for table `recycling_printers_cartridges` */

DROP TABLE IF EXISTS `recycling_printers_cartridges`;

CREATE TABLE `recycling_printers_cartridges` (
  `cartridgeModelId` int(11) DEFAULT NULL,
  `printerModelId` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `recycling_printers_cartridges` */

insert  into `recycling_printers_cartridges`(`cartridgeModelId`,`printerModelId`) values (5,1),(3,2),(4,2),(16,7),(13,7),(6,3),(2,5),(1,5),(4,4),(3,4),(8,6),(2,8),(1,8);

/*Table structure for table `recycling_printers_models` */

DROP TABLE IF EXISTS `recycling_printers_models`;

CREATE TABLE `recycling_printers_models` (
  `id` int(11) NOT NULL,
  `modelName` text CHARACTER SET utf8 DEFAULT NULL,
  `modelCode` text CHARACTER SET utf8 DEFAULT NULL,
  `deviceType` int(11) DEFAULT NULL,
  `hasNetwork` int(11) DEFAULT 0,
  `hasColor` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `recycling_printers_models` */

insert  into `recycling_printers_models`(`id`,`modelName`,`modelCode`,`deviceType`,`hasNetwork`,`hasColor`) values (1,'HP LaserJet Pro M426fdn','F6W14A',1,1,2),(2,'HP LaserJet Pro 400 M425dn','CF286A',1,1,2),(3,'HP LaserJet Enterprise M604dn','E6B68A',2,1,2),(4,'HP LaserJet Pro 400 M401a','CF270A',2,1,2),(5,'HP LaserJet M2727nfs MFP','CB533A',1,1,2),(6,'HP LaserJet M1120n MFP','CC459A',1,1,2),(7,'HP LaserJet Enterprise 600 M603dn','CE995A',2,1,2),(8,'HP LaserJet P2015dn','CB368A',2,1,2);

/*Table structure for table `recycling_printers_stasuses` */

DROP TABLE IF EXISTS `recycling_printers_stasuses`;

CREATE TABLE `recycling_printers_stasuses` (
  `id` tinyint(3) NOT NULL,
  `stasus` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1 TRANSACTIONAL=1;

/*Data for the table `recycling_printers_stasuses` */

insert  into `recycling_printers_stasuses`(`id`,`stasus`) values (1,'выведен из оборота'),(2,'в работе'),(3,'неисправен'),(4,'в ремонте'),(5,'в резерве');

/*Table structure for table `recycling_printers_types` */

DROP TABLE IF EXISTS `recycling_printers_types`;

CREATE TABLE `recycling_printers_types` (
  `id` int(11) NOT NULL,
  `printer_type_txt` text CHARACTER SET utf8mb4 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `recycling_printers_types` */

insert  into `recycling_printers_types`(`id`,`printer_type_txt`) values (1,'МФУ'),(2,'Принтер'),(3,'Копир');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
