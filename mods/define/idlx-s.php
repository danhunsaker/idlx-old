<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/define/idlx-s.php
		Defines the PHP interface for the IDLX Scripting modules.
	*/
	
	interface IDLXSModule {
		static function get_handler();
		public function run_script ($script);							//	Runs the script and returns the output.
	}
?>