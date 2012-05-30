<?php

	/**	index.php
		The core of the IDLX Framework rests here.  This is where the magic all begins, and where the modules are bossed around from.
	*/
	
	//	Some initialization stuff.
	ob_start();
	session_start();
	
	if (!defined('IN_SITE')) define('IN_SITE', true);
	$old_dir = getcwd();
	chdir(dirname(__FILE__));
	
	include_once('util-functions.php');
	include_once('config.php');
	include_once('load-modules.php');
	
	//	Initialization complete.  Do your work!
	
	
	//	That's all she wrote, folks.  Clean up and go home.
	echo "The IDLX Framework is still under construction.  Try again later.\n<br />\n";
	chdir($old_dir);
	ob_end_flush();
?>