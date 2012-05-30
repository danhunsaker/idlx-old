<?php
if (isset($_REQUEST['cname']) && !isset($_REQUEST['iname']) && !isset($_REQUEST['savename']) && !isset($_REQUEST['slayname'])) {	//	cName only - Retrieve an Interface.
	$dom = new DOMDocument();
	$dom->loadXML("<reply><code></code><ajax></ajax></reply>");
	$code_dom = $dom->createCDATASection($db->get_iface_cname($_REQUEST['cname']));
	$ajax_dom = $dom->createCDATASection($db->get_data_value($config['db-interfaces-tablename'], "`{$config['db-interfaces-codename']}`=\"{$_REQUEST['cname']}\"", $config['db-interfaces-ajax']));
	$dom->getElementsByTagName('code')->item(0)->appendChild($code_dom);
	$dom->getElementsByTagName('ajax')->item(0)->appendChild($ajax_dom);

	$response = $dom->saveXML();
//	error_log ("Interface--iface-admin AJAX || Response generated [{$response}]");
	return $response;
}
elseif (isset($_REQUEST['cname']) && isset($_REQUEST['iname']) && !isset($_REQUEST['savename']) && !isset($_REQUEST['slayname'])) {	//	cName and iName - Create an Interface.
	$cname = addslashes($_REQUEST['cname']);
	$iname = addslashes($_REQUEST['iname']);
	
	$exists = $db->get_iface_cname($cname);
	if ($exists !== false) {
		return ("INTERFACE ALREADY EXISTS [{$cname}]");
	}
	
	$iftbl_perms = $db->acl_table($config['db-interfaces-tablename']);
	$cnfld_perms = $db->acl_field($config['db-interfaces-tablename'], $config['db-interfaces-codename']);
	$infld_perms = $db->acl_field($config['db-interfaces-tablename'], $config['db-interfaces-interfacename']);
	
	$cname_perms = $iftbl_perms | $cnfld_perms;
	$iname_perms = $iftbl_perms | $infld_perms;
	
	if ((($cname_perms & 0x42) == 0x42) && (($iname_perms & 0x42) == 0x42)) {		//	0x40 == recode perms; 0x02 == write perms; User has perms to modify this Interface's code.
		$db->raw_sql("insert into `{$config['db-interfaces-tablename']}` (`{$config['db-interfaces-codename']}`, `{$config['db-interfaces-interfacename']}`) values (\"{$cname}\", \"{$iname}\")");
	}
	else {
		return ("Create Interface failed; insufficient perms [{$cname_perms} || {$iname_perms}]");
	}
	
	return '';		//	Success!  Return an empty string so the AJAX handler won't spit out a 403 header.
}
elseif (!isset($_REQUEST['cname']) && !isset($_REQUEST['iname']) && isset($_REQUEST['savename']) && !isset($_REQUEST['slayname'])) {	//	saveName only - Save an Interface.
	$code = isset($_POST['iface_code']) ? addslashes($_POST['iface_code']) : null;
	$ajax = isset($_POST['iface_ajax']) ? addslashes($_POST['iface_ajax']) : null;
	
	$iface_perms = $db->acl_iface($_REQUEST['savename']);
	$iftbl_perms = $db->acl_table($config['db-interfaces-tablename']);
	$cdfld_perms = $db->acl_field($config['db-interfaces-tablename'], $config['db-interfaces-codeblock']);
	$ajfld_perms = $db->acl_field($config['db-interfaces-tablename'], $config['db-interfaces-ajax']);
	
	$code_perms = $iface_perms | $iftbl_perms | $cdfld_perms;
	$ajax_perms = $iface_perms | $iftbl_perms | $ajfld_perms;
	
	if (($code_perms & 0x42) == 0x42) {		//	0x40 == recode perms; 0x02 == write perms; User has perms to modify this Interface's code.
		$db->raw_sql("update `{$config['db-interfaces-tablename']}` set `{$config['db-interfaces-codeblock']}`=\"{$code}\" where `{$config['db-interfaces-codename']}`=\"{$_REQUEST['savename']}\"");
	}
	else {
		error_log ("Interface--iface-admin AJAX || Interface Code save failed; insufficient perms [{$code_perms}]");
	}
	
	if (($ajax_perms & 0x42) == 0x42) {		//	0x40 == recode perms; 0x02 == write perms; User has perms to modify this Interface's AJAX responder.
		$db->raw_sql("update `{$config['db-interfaces-tablename']}` set `{$config['db-interfaces-ajax']}`=\"{$ajax}\" where `{$config['db-interfaces-codename']}`=\"{$_REQUEST['savename']}\"");
	}
	else {
		error_log ("Interface--iface-admin AJAX || AJAX Responder save failed; insufficient perms [{$ajax_perms}]");
	}
		
	return '';		//	Success!  Return an empty string so the AJAX handler won't spit out a 403 header.
}
elseif (!isset($_REQUEST['cname']) && !isset($_REQUEST['iname']) && !isset($_REQUEST['savename']) && isset($_REQUEST['slayname'])) {	//	slayName only - Destroy an Interface.
	return 'DELETE INTERFACE NOT YET IMPLEMENTED';
}
else {	//	Something else we don't understand
	return 'UNKNOWN REQUEST';
}

?>