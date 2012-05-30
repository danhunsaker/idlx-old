<?php
	include_once(dirname(__FILE__) . "/in-site-check.php");
	
	/** load-modules.php
		Loads the files which define the PHP interfaces that the various modules will need to implement,
		and then loads the modules requested by the configuration in $config[].  This prevents undesired
		modules from loading, while ensuring all the required modules are in place.
	*/
	
	require_once('mods/define/auth.php');			//	Authentication modules (PBA, CLO)
	require_once('mods/define/db.php');				//	Databases - modules which provide data access (MySQL)
	require_once('mods/define/reports.php');		//	This one does TWO interfaces - one for report generation (XML+XSL-FO), and one for report output (PDF)
	require_once('mods/define/xuid.php');			//	XML User Interface Dialects - supports handling of various XUIDs, as encountered in an IDLX Interface (XForms/XHTML+CSS+JS)
	require_once('mods/define/idlx-s.php');			//	IDLX Scripting - supports handling of various scripting languages, as encountered in an IDLX Interface (PHP)
	
	include_once('mods/db/'.mb_strtolower($config['db-engine']).'.php');
	$db_class = 'DB_'.$config['db-engine'];
	error_log ("load-modules.php || Connecting to database with {$db_class}");
	$db = new $db_class($config['db-host'], $config['db-user'], $config['db-pass'], $config['db-name']);
	
	$i = 0;
	$uid = false;
	while (isset($config['auth-engine-'.$i])) {
		include_once('mods/auth/'.mb_strtolower($config['auth-engine-'.$i]).'.php');
		$auth_class = 'Auth_'.$config['auth-engine-'.$i];
		error_log ("load-modules.php || Trying to auth with {$auth_class}");
		$auth = new $auth_class();
		
		$uid = $auth->auth($db);
		if ($uid !== false)
			break;
		
		$i++;
	}
	
	if ($uid === false) {
		die ('User authentication failed.');
	}
	
	$_SESSION['user_id'] = $uid;					//	User authenticated.  Store their UserID in the session.
	
	$report_mods = get_mods('reports');
	$xuid_mods = get_mods('xuid');
	$idlxs_mods = get_mods('idlxs');
	
?>