<?php

	/**	index.php
		The core of the IDLX Framework rests here.  This is where the magic all begins, and where the modules are bossed around from.
	*/
	
	//	Some initialization stuff.
	ob_start();
	session_start();
	
	if (!defined('IN_SITE')) define('IN_SITE', true);
	$proj_dir = getcwd();
	chdir(dirname(__FILE__));
	
	include_once('util-functions.php');
	include_once('config.php');
	include_once('load-modules.php');
	
	//	Initialization complete.  Do your work!
	$main_iface = $db->get_iface_cname('main');		//	An Interface whose CodeName == 'main' MUST exist.
	if ($main_iface == false) {
		error_log ("index.php || No 'main' Interface defined!!!  Cannot continue; reinstall the IDLX Framework and any IDLX Projects you may be using! [{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}]");
		die("Could not find 'main' Interface.  Cannot continue.  Please inform your site administrator of this error.  [Date/Time: ".gmdate(DATE_COOKIE)."]");
	}
	$if_dom = new DOMDocument();
	$if_dom->loadXML($main_iface);
	$xp = new DOMXPath($if_dom);
	$xp->registerNamespace('idlx', 'https://localhost/idlx/idlx-schema/');
	$nothingNew = false;
	while ($nothingNew == false) {
		$script_tags = $xp->evaluate('idlx:script');
		$report_tags = $xp->evaluate('idlx:report');
		$db_tags     = $xp->evaluate('idlx:table|idlx:record|idlx:field');
		$xuid_tags   = $xp->evaluate('//*[namespace-uri()!="https://localhost/idlx/idlx-schema/"]');
		error_log("index.php || Tag counts: scripts [{$script_tags->length}] reports [{$report_tags->length}] db value selectors [{$db_tags->length}] xuid nodes [{$xuid_tags->length}]");
		$nothingNew = true;
	}
	
	echo $if_dom->saveXML();
	
	//	That's all she wrote, folks.  Clean up and go home.
	echo "The IDLX Framework is still under construction.  Try again later.\n<br />\n";
	chdir($proj_dir);
	ob_end_flush();
?>