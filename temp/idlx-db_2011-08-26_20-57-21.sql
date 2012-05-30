-- phpMyAdmin SQL Dump
-- version 3.4.3.2
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306

-- Generation Time: Aug 26, 2011 at 08:57 PM
-- Server version: 5.5.15
-- PHP Version: 5.3.6

SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `idlx`
--

-- --------------------------------------------------------

--
-- Table structure for table `accesscontrollist`
--
-- Creation: Aug 11, 2011 at 07:16 PM
-- Last update: Aug 26, 2011 at 04:10 PM
--

DROP TABLE IF EXISTS `accesscontrollist`;
CREATE TABLE "accesscontrollist" (
  "ID" bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Internal ID',
  "UserID" varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'ID of User to which this ACL applies (do not use GroupID!)',
  "GroupID" varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'ID of Group to which this ACL applies (do not use UserID!)',
  "Permission" bigint(20) unsigned NOT NULL COMMENT 'Permission ID for object to which this ACL applies.',
  "PermissionLevel" set('read','write','backup','add','remove','import','recode','invert') COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'The level of permission allowed.',
  PRIMARY KEY ("ID")
);

--
-- Dumping data for table `accesscontrollist`
--

INSERT INTO `accesscontrollist` (`ID`, `UserID`, `GroupID`, `Permission`, `PermissionLevel`) VALUES
(1, NULL, '1', 2, 'read,write,backup,add,remove,import,recode'),
(2, NULL, '1', 3, 'read,write,backup,add,remove,import,recode'),
(3, NULL, '2', 3, '');

-- --------------------------------------------------------

--
-- Table structure for table `groupmembership`
--
-- Creation: Aug 11, 2011 at 07:16 PM
-- Last update: Aug 26, 2011 at 04:02 PM
--

DROP TABLE IF EXISTS `groupmembership`;
CREATE TABLE "groupmembership" (
  "ID" bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Internal ID (just in case)',
  "UserID" varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'User ID of the Group member.',
  "GroupID" varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Group ID of the Group.',
  PRIMARY KEY ("ID")
);

--
-- Dumping data for table `groupmembership`
--

INSERT INTO `groupmembership` (`ID`, `UserID`, `GroupID`) VALUES
(1, '1', '1'),
(2, '2', '2');

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--
-- Creation: Aug 11, 2011 at 07:16 PM
-- Last update: Aug 26, 2011 at 03:55 PM
--

DROP TABLE IF EXISTS `groups`;
CREATE TABLE "groups" (
  "GroupID" varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Internal ID',
  "GroupName" varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Group Name (UI)',
  "Administrator" varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'The UserID of the Group''s Administrator.',
  "Notes" longtext COLLATE utf8_unicode_ci COMMENT 'Notes.',
  PRIMARY KEY ("GroupID")
);

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`GroupID`, `GroupName`, `Administrator`, `Notes`) VALUES
('1', 'Admins', '1', 'Users with administrative rights throughout the Framework.'),
('2', 'Normal Users', '1', 'A group for users without Administrative rights throughout the Framework.');

-- --------------------------------------------------------

--
-- Table structure for table `interfaces`
--
-- Creation: Aug 25, 2011 at 04:50 PM
-- Last update: Aug 26, 2011 at 07:40 PM
--

DROP TABLE IF EXISTS `interfaces`;
CREATE TABLE "interfaces" (
  "ID" bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Internal ID',
  "InterfaceName" varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Descriptive name',
  "CodeName" varchar(30) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Name in code',
  "CodeBlock" longtext COLLATE utf8_unicode_ci NOT NULL COMMENT 'The actual IDLX code block.',
  "Ajax" longtext COLLATE utf8_unicode_ci COMMENT 'PHP code for handling AJAX requests.',
  "Notes" longtext COLLATE utf8_unicode_ci COMMENT 'Notes about the IDLX.',
  PRIMARY KEY ("ID")
);

--
-- Dumping data for table `interfaces`
--

INSERT INTO `interfaces` (`ID`, `InterfaceName`, `CodeName`, `CodeBlock`, `Ajax`, `Notes`) VALUES
(1, 'Main Interface', 'main', '<iface xmlns="http://idlx.sourceforge.net/schema/2011/08/">\r\n	<script lang="php">\r\n		<![CDATA[\r\n			echo file_get_contents (''main-iface.idlx'');\r\n		]]>\r\n	</script>\r\n</iface>\r\n', '', 'The "main" Interface is REQUIRED.\r\n\r\nThis is the Interface which handles most of the actual work of processing user input, including which areas to access.  This design allows for ease of developing a uniform UI, as well as control over QUERY_STRING construction, among other things.  Whatever you do with the rest of the Interfaces, DO NOT DESTROY THIS ONE - simply rewrite it to fit your needs.'),
(2, 'Home', 'home', '<h1 xmlns="http://www.w3c.org/1999/xhtml/">IDLX Home</h1>\r\n<img src="images/IDLX%20Logo%20v1.png" alt="IDLX Logo (v1)" xmlns="http://www.w3c.org/1999/xhtml/" />\r\n<p xmlns="http://www.w3c.org/1999/xhtml/">Welcome to IDLX!  This is the Default Interface Set, which means you probably want to replace most of it with your own code and content.  In the meantime, feel free to look around a bit and get familiar with how things are set up.  After that, go ahead and create yourself a new IDLX Project!</p>\r\n', '', 'Content area for the main page.'),
(3, 'Admin', 'admin-nav', '<h1 xmlns="http://www.w3c.org/1999/xhtml/">Admin Interface</h1>\n<span class="subnav" xmlns="http://www.w3c.org/1999/xhtml/">[\n<a href="?p=admin-nav&amp;s=iface-admin" xmlns="http://www.w3c.org/1999/xhtml/">Interface Editor</a> | \n<a href="?p=admin-nav&amp;s=perms-admin" xmlns="http://www.w3c.org/1999/xhtml/">Permissions</a>\n]</span>\n<idlx:script lang="php" xmlns:idlx="http://idlx.sourceforge.net/schema/2011/08/">\n<![CDATA[\nif (isset($api->_request[''s'']) && !empty($api->_request[''s''])) {\n	if (!is_numeric($api->_request[''s''])) {\n		echo ''<interface xmlns="''.IDLX_NS_URI.''">''.$api->_request[''s''].''</interface>'';\n	} else {\n		echo ''<interface xmlns="''.IDLX_NS_URI.''">iface-''.$api->_request[''s''].''</interface>'';\n	}\n}\n]]>\n</idlx:script>', '', 'Main administrative interface.  The other admin Interfaces are all listed and accessed from here.'),
(4, 'Interface Editor', 'iface-admin', '<script lang="php" xmlns="http://idlx.sourceforge.net/schema/2011/08/">\n	<![CDATA[\n		echo file_get_contents (''temp/iface-admin.idlx'');\n	]]>\n</script>', 'return include (''temp/iface-admin-ajax.php'');\n', 'Rudimentary (for now, at least) Interface editing page.'),
(8, 'Permissions', 'perms-admin', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--
-- Creation: Aug 11, 2011 at 02:31 PM
-- Last update: Aug 26, 2011 at 04:10 PM
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE "permissions" (
  "ID" bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Internal ID',
  "PermName" varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'UI Name',
  "InterfaceID" bigint(20) unsigned DEFAULT NULL COMMENT 'Interface ID for this Perm (do not use TableName or FieldName!)',
  "TableName" varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Table Name for this Perm (do not use InterfaceID!)',
  "FieldName" varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Field Name for this Perm (do not use InterfaceID!  TableName is MANDATORY!)',
  "ParentPerm" bigint(20) unsigned DEFAULT NULL COMMENT 'ID of Perm from which this one should inherit ACLs.',
  "Details" longtext COLLATE utf8_unicode_ci COMMENT 'Notes',
  PRIMARY KEY ("ID")
);

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`ID`, `PermName`, `InterfaceID`, `TableName`, `FieldName`, `ParentPerm`, `Details`) VALUES
(1, 'Main Interface', 1, NULL, NULL, 0, 'This Permission should never be used on anyone''s ACLs.  To deny someone access to the entire Project, either remove their user entry, or change their authentication information to something impossible (adding ! at the beginning tends to disrupt things immensely, since anything the user can enter or change manually is encrypted in the DB...).'),
(2, 'Interfaces', NULL, 'Interfaces', NULL, 0, 'This Permission can be used to grant permissions for modification of the Interfaces.'),
(3, 'Admin Interface', 3, NULL, NULL, 2, 'Controls access to the primary Admin Interface.'),
(4, 'Interface Editor', 4, NULL, NULL, 3, 'Controls access to the Interface Editor Interface.');

-- --------------------------------------------------------

--
-- Table structure for table `userinfo`
--
-- Creation: Aug 17, 2011 at 03:12 PM
-- Last update: Aug 26, 2011 at 04:10 PM
--

DROP TABLE IF EXISTS `userinfo`;
CREATE TABLE "userinfo" (
  "UserID" varchar(35) COLLATE utf8_unicode_ci NOT NULL,
  "Login" varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  "Password" varbinary(255) NOT NULL,
  "CertDN" varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'The full Subject DN of a valid cert.',
  PRIMARY KEY ("UserID")
);

--
-- Dumping data for table `userinfo`
--

INSERT INTO `userinfo` (`UserID`, `Login`, `Password`, `CertDN`) VALUES
('1', 'admin.hunsaker', '*•S¤ë¢lÖæÉº¿RÅ', '/C=US/O=U.S. Government/OU=DoD/OU=PKI/OU=USN/CN=HUNSAKER.DANIEL.JOSEPH.1279107728'),
('2', 'daniel.hunsaker', '*•S¤ë¢lÖæÉº¿RÅ', '');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
