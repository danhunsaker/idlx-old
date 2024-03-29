<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/idlx-s/php.php
		Contains the PHP IDLXS module.
	*/
	
	class IDLXS_PHP implements IDLXSModule {
		static function get_handler() {
			return 'php';
		}
		
		private function getAPICode() {
			global $config, $auth_file, $auth_class, $siteroot, $idlxroot;
			
			//	The $api_code segment defines the API class, and creates $api as an instance.
			$api_code = '
	define("IN_SITE", true);	//	Protection from redirect in include()d files...
	
//	error_log ("Running script ".__FILE__);	
	$_SESSION[\'user_id\'] = "'.$_SESSION['user_id'].'";
	
	class IDLXS_API {
		private $db_obj = null;
		public $_request = array(';
			foreach ($_REQUEST as $key=>$req) {
				$api_code .= "'{$key}' => '".addslashes($req)."', ";
			}
			$api_code .= ');
		public $project_path = "'.$siteroot.'";
		public $idlx_path = "'.$idlxroot.'";
		
		public function set_db(DBModule $db) {
			$this->db_obj = $db;
		}
		
		public function db() {
//			error_log ("IDLXS_API::db || Accessing DB.");
			return $this->db_obj;
		}
	}
	
	include_once("'.strtr(realpath(dirname(__FILE__) . '/../../'), array('\\'=>'/')).'/config.php");

	$api = new IDLXS_API();'."\n";
			
			//	The $db_code segment brings in the DBModule interface, then the class for the DBModule in use.
			//	It then creates a DBModule instance and assigns it to the $api object's ->db() "property".
			$db_code = "
	include_once('".strtr(realpath(dirname(__FILE__) . '/../../'), array('\\'=>'/'))."/mods/define/db.php');
	include_once('".strtr(realpath(dirname(__FILE__) . '/../../'), array('\\'=>'/'))."/mods/db/".mb_strtolower($config['db-engine']).".php');
	\$api->set_db(new DB_{$config['db-engine']}('{$config['db-host']}', '{$config['db-scripts-user']}', '{$config['db-scripts-pass']}', '{$config['db-name']}'));
//	error_log (\"Script API set up; continuing to actual script.\");\n";
			
			//	If anything else needs to be supplied by the API, add it here.
			
			$ret_code = $api_code . $db_code;		//	Combine the code segments before returning the full API code.
			return $ret_code;
		}
		
		public function run_script ($script) {
			global $config;
			//	Don't forget to add code to initialize the various variables and such that IDLX-S scripts need access to!!!
//			error_log("IDLXS_PHP::run_script || Running {$script}");
			$api = $this->getAPICode();
			$script = "<?php\n{$api}{$script}\n?>";
			$sfile = tempnam($config['temp_dir'], 'idlxs-');
			file_put_contents($sfile, $script);
			$output = exec("php -f \"{$sfile}\"", $out, $result);
			unlink($sfile);
			$output = implode("\n", $out);
			return $output;
		}
	}
	
	return 'IDLXS_PHP';
?>
