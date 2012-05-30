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
	include_once('config.php');
	include_once('load-modules.php');
	
	//	Initialization complete.  Do your work!
	if (!isset($_GET['p'])) {
		$_GET['p'] = 'main';
	}
	elseif (is_numeric($_GET['p'])) {
		$_GET['p'] = 'iface-'.$_GET['p'];
	}
	
	$ajax = $db->get_data_value($config['db-interfaces-tablename'], "`{$config['db-interfaces-codename']}`=\"{$_GET['p']}\"", $config['db-interfaces-ajax']);
	if ($ajax === false) {
		die_deny ("ACCESS DENIED or BAD INTERFACE");
	}
	
	$response = eval($ajax);
	if ($response === false) {
		die_deny ("ERROR PARSING AJAX RESPONDER");
	}
	else {
		if (substr($response, 0, 5) == '<?xml') header('Content-type: text/xml');
		elseif (!empty($response)) header('HTTP/1.1 403 Forbidden');
		echo $response;
	}
	
	//	That's all she wrote, folks.  Clean up and go home.
	session_commit();
	chdir($proj_dir);
//	error_log ("ajax.php || [".ob_get_flush()."]");
	ob_end_flush();
?>