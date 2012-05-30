<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/db/mysql.php
		Contains the MySQL DB module.
	*/
	
	class DB_MySQL implements DBModule {
		private $results;
		private $con;
		
		public function __construct ($server, $user, $pass, $database) {
			try {
				$this->con = new PDO('mysql:dbname='.$database.';host='.$server, $user, $pass);
				$this->results = new PDOStatement();
			}
			catch (PDOException $e) {
				die ('Connection Failed! [' . $e->getMessage() . ']');
				return false;
			}
		}
		
		public function raw_sql ($sql_query) {
			error_log ("DB_MySQL::raw_sql || Running raw SQL query [{$sql_query}].");
			try {
				do $this->results->fetchAll(); while ($this->results->nextRowSet());		//	Navigate through any unused results so the connection is available again.
				$this->results = $this->con->query($sql_query);
				if ($this->results === false) return false;
			}
			catch (PDOException $e) {
				error_log ("DB_MySQL::raw_sql || Query failure: {$sql_query} [{$e->getMessage()}]");
				return false;
			}
			return true;
		}
		
		public function get_result_value ($column, $row) {
			error_log ("DB_MySQL::get_result_value || Fetching column [{$column}] row [{$row}].");
			$res = $this->results->fetch(PDO::FETCH_BOTH, PDO::FETCH_ORI_ABS, $row);
			return isset($res[$column]) ? $res[$column] : false;
		}
		
		public function get_user (array $creds) {
			global $config;
			
			if (count($creds) == 0) return false;
			$where = '1 = 1';
			foreach ($creds as $key=>$val) {
				$where .= " AND `{$key}` = \"{$val}\"";
			}
			$got_results = $this->raw_sql("select `{$config['db-userinfo-userid']}` from `{$config['db-userinfo-tablename']}` where {$where}");
			if (!$got_results) return false;
			return $this->get_result_value($config['db-userinfo-userid'], 0);
		}
		
		public function save_user ($uid, array $creds) {
			return false;
		}
	}
?>