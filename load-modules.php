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
//	error_log ("load-modules.php || Connecting to database with {$db_class}");
	$db = new $db_class($config['db-host'], $config['db-user'], $config['db-pass'], $config['db-name']);
	
	$uid = false;
	for ($mod_i = 0; isset($config['auth-engine-'.$mod_i]); $mod_i++) {
		$auth_engine = $config['auth-engine-'.$mod_i];
		include_once('mods/auth/'.mb_strtolower($auth_engine).'.php');
		$auth_class = 'Auth_'.$auth_engine;
		$auth = new $auth_class();
		
//		error_log ("load-modules.php || Attempting auth with {$auth_class} [{$mod_i}]...");
		$uid = $auth->auth($db);
		if ($uid !== false) {
			error_log ("load-modules.php || Successful auth with {$auth_class} [{$mod_i}]");
			break;
		}
	}
	
	if ($uid === false) {
		error_log ("load-modules.php || User authentication failed.  Stopping execution now.");
		die ('User authentication failed.');
	}
	
	$_SESSION['user_id'] = $uid;					//	User authenticated.  Store their UserID in the session.
	
	$report_mods = get_mods('reports');				//	Pull in all the Report Modules of both types.
	$rep_proc_mods = array();
	$rep_gen_mods = array();
	foreach ($report_mods as $key=>$mod) {				//	Segregate modules by type.
		if (is_a($mod, 'ReportProcessor')) $rep_proc_mods[$key] = $mod;			//	ReportProcessor Modules
		elseif (is_a($mod, 'ReportGenerator')) $rep_gen_mods[$key] = $mod;		//	ReportGenerator Modules
	}
	unset($report_mods);							//	Remove extraneous array.
	
	$config['output'] = array();					//	Uncollapse the list of desired output formats.
	for ($out_i = 0; isset($config["output-{$out_i}"]); $out_i++) {
		$config['output'][$out_i] = $config["output-{$out_i}"];
		unset($config["output-{$out_i}"]);
	}
	
	$xuid_mods = get_mods('xuid');					//	Pull in all XUID modules.
	$remove = array();
	foreach ($xuid_mods as $key=>$xuid) {			//	Check which ones support our desired output formats.
		if (!in_array($xuid->get_output_ns(), $config['output'])) $remove[] = $key;
	}
	foreach ($remove as $key) {						//	Remove XUID modules which we are guaranteed not to use.
		unset($xuid_mods[$key]);
	}

	$idlxs_mods = get_mods('idlxs');				//	Pull in all IDLX-S modules.
	
?>