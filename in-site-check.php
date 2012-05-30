<?php
	if (!defined('IN_SITE')) {
		error_log ("in-site-check.php || Doc requested [{$_SERVER['REQUEST_URI']}]");
		$siteroot = strtr(dirname(__FILE__), '\\', '/');		//	Make UNIX-safe path or we won't get anywhere...
		$siteroot = strtr($siteroot, array($_SERVER['DOCUMENT_ROOT'] => ''));
		header('Location: '.$siteroot.'/');
		ob_end_clean();
		die();
	}
?>