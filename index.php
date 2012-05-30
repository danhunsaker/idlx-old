<?php

	/**	index.php
		The core of the IDLX Framework rests here.  This is where the magic all begins, and where the modules are bossed around from.
	*/
	
	//	Some initialization stuff.
	ob_start();
	session_start();

	if (!defined('IN_SITE')) define('IN_SITE', true);

	$proj_dir = strtr(getcwd(), array('\\'=>'/'));
	if (substr($proj_dir, -1, 1) != '/') $proj_dir .= '/';
	chdir(dirname(__FILE__));
	
	//	Can't use __FILE__ to determine $siteroot because of the way Projects are implemented...
	$idlxroot = strtr(dirname(__FILE__), '\\', '/');
	$idlxroot = strtr($idlxroot, array($_SERVER['DOCUMENT_ROOT'] => ''));
	$siteroot = strtr(dirname($_SERVER['SCRIPT_FILENAME']), array('\\' => '/', $_SERVER['DOCUMENT_ROOT'] => ''));

	include_once('util-functions.php');

	if (!isset($_SESSION['user_id']) && (!empty($_SERVER['QUERY_STRING']) || !empty($_SERVER['PATH_INFO']))) {
		send_to_siteroot('User not logged in', 'index.php', $siteroot);
	}
	
	include_once('config.php');
	include_once('load-modules.php');
	
	//	Initialization complete.  Do your work!
	$main_iface = $db->get_iface_cname('main');		//	An Interface whose CodeName == 'main' MUST exist.
	if ($main_iface == false) {
		error_log ("index.php || No 'main' Interface defined!!!  Cannot continue; reinstall the IDLX Framework and any IDLX Projects you may be using! [{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}{$_SERVER['PATH_INFO']}?{$_SERVER['QUERY_STRING']}]");
		die("Could not find 'main' Interface.  Cannot continue.  Please inform your site administrator of this error.  [Date/Time: ".gmdate(DATE_COOKIE)."]");
	}
	
	echo process_iface($main_iface);	//	This functionality was moved to util-functions.php so it could be used elsewhere (i.e., AJAX).
	
	//	That's all she wrote, folks.  Clean up and go home.
	session_commit();
	chdir($proj_dir);
	ob_end_flush();
?>