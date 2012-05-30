<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/idlx-s/php.php
		Contains the PHP IDLXS module.
	*/
	
	class IDLXS_PHP implements IDLXSModule {
		public function run_script ($script) {
			//	Don't forget to add code to initialize the various variables and such that IDLX-S scripts need access to!!!
			$sfile = tempnam(sys_get_temp_dir(), 'idlxs-');
			file_put_contents($sfile, $script);
			exec("php -f {$sfile}", $out, $result);
			unlink($sfile);
			$output = implode("\n", $out);
			return $output;
		}
	}
	
	return 'IDLXS_PHP';
?>