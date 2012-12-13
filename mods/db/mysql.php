<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/db/mysql.php
		Contains the MySQL DB module.
	*/
	
	class DB_MySQL implements DBModule {
		private $results;
		private $con;
		
		public function __construct ($server, $user, $pass, $database) {
			if (!need_ext('pdo')) return false;
			if (!need_ext('pdo_mysql')) return false;

			try {
				$this->con = new PDO('mysql:dbname='.$database.';host='.$server, $user, $pass);
				if (!is_a($this->con, 'PDO')) {
					error_log("Connection not established.  [" . var_export($this->con, true) . "]");
					return false;
				}
				$this->results = new PDOStatement();
			}
			catch (PDOException $e) {
				error_log("DB_MySQL::__construct || Connection Failed! [{$e->getMessage()} || {$server} || {$user} || {$pass} || {$database}]");
				die ("Connection Failed! [{$e->getMessage()}]");
				return false;
			}
		}
		
		public function raw_sql ($sql_query) {
//			error_log ("DB_MySQL::raw_sql || Running raw SQL query [{$sql_query}].");
			try {
				if (is_a($this->results, 'PDOStatment')) { do $this->results->fetchAll(); while ($this->results->nextRowSet()); }		//	Navigate through any unused results so the connection is available again.
				$this->results = $this->con->query($sql_query);
				if ($this->results === false) {
					$error_info = $this->con->errorInfo();
					error_log ("DB_MySQL::raw_sql || Query failure: {$sql_query} [{$error_info[0]} || {$error_info[1]} || {$error_info[2]}]");
					return false;
				}
			}
			catch (PDOException $e) {
				error_log ("DB_MySQL::raw_sql || Query failure: {$sql_query} [{$e->getMessage()}]");
				return false;
			}
			return true;
		}
		
		public function get_result_value ($column, $row) {
//			error_log ("DB_MySQL::get_result_value || Fetching column [{$column}] row [{$row}].");
			$res = $this->results->fetch(PDO::FETCH_BOTH, PDO::FETCH_ORI_ABS, $row);
			return isset($res[$column]) ? $res[$column] : false;
		}
		
		public function get_user (array $creds) {
			global $config;
			
			if (count($creds) == 0) return false;
			$where = '1 = 1';
			foreach ($creds as $key=>$val) {
				if ($key != $config['db-userinfo-password']) {
					$where .= " AND `{$key}` = \"{$val}\"";
				}
				else {
					$where .= " AND `{$key}` = AES_ENCRYPT(\"{$val}\", \"{$config['db-encryption-password']}\")";
				}
			}
//			error_log ("DB_MySQL::get_user || Pulling user from DB [{$where}]");
			$got_results = $this->raw_sql("select `{$config['db-userinfo-userid']}` from `{$config['db-userinfo-tablename']}` where {$where}");
			if (!$got_results) return false;
			return $this->get_result_value($config['db-userinfo-userid'], 0);
		}
		
		public function save_user ($uid, array $creds) {
			return false;
		}
		
		public function get_iface_id ($iid) {
			global $config;
			if (!$this->raw_sql("select `{$config['db-interfaces-codename']}` from `{$config['db-interfaces-tablename']}` where `{$config['db-interfaces-id']}`={$iid}")) return false;
			$cname = $this->get_result_value($config['db-interfaces-codename'], 0);
			return $cname === false ? false : $this->get_iface_cname($cname);
		}
		
		public function get_iface_cname ($cname) {
			global $config;
			if (($this->acl_iface($cname) & 1) == 0) return false;		//	Check for read rights.
			$found_iface = $this->raw_sql("select `{$config['db-interfaces-codeblock']}` from `{$config['db-interfaces-tablename']}` where `{$config['db-interfaces-codename']}`=\"{$cname}\"");
			if ($found_iface) {
				return $this->get_result_value($config['db-interfaces-codeblock'], 0);
			}
			else return false;
		}

		private function acl_check ($obj_type, $name) {
			global $config;
			
			//	First, figure out what kind of object we're asking about, and set up the applicable portion of the SQL query.
			switch ($obj_type) {
				case 'iface':
					$iid_found = $this->raw_sql("select `{$config['db-interfaces-id']}` from `{$config['db-interfaces-tablename']}` where `{$config['db-interfaces-codename']}`=\"{$name}\"");
					if ($iid_found) {
						$iid = $this->get_result_value($config['db-interfaces-id'], 0);
						if ($iid) {
							$obj = "`{$config['db-permissions-interfaceid']}`={$iid}";
						}
						else {
							return false;
						}
					}
					else {
						return false;
					}
					break;
				case 'table':
					$obj = "`{$config['db-permissions-tblname']}`=\"{$name}\"";
					break;
				case 'field':
					$obj = "`{$config['db-permissions-tblname']}`=\"{$name['table']}\" and `{$config['db-permissions-fieldname']}`=\"{$name['field']}\"";
					break;
				default:
					return false;
			}
			
			//	Check for a Permission ID for the object itself.
			$perm_found = $this->raw_sql("select `{$config['db-permissions-id']}` from `{$config['db-permissions-tablename']}` where {$obj}");
			if ($perm_found) {
				$obj_perm = $this->get_result_value($config['db-permissions-id'], 0);
			}
			else {
				$obj_perm = '';
			}
			
			//	Now, iteratively compile a list of all parent Perms.
			if (isset($obj_perm) && !empty($obj_perm)) {
				$stmt = $this->con->prepare("select `{$config['db-permissions-parentperm']}` from `{$config['db-permissions-tablename']}` where `{$config['db-permissions-id']}`=:permid");
				$pperms_found = $stmt->execute(array('permid'=>$obj_perm));
				if ($pperms_found) {
					$pperms = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
					foreach ($pperms as $perm) {
						if ($perm == 0) continue;
						$stmt->execute(array('permid'=>$perm));
						$more_pperms = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
						foreach ($more_pperms as $more_perm) {
							if ($more_perm == 0) continue;
							if (!in_array($more_perm, $pperms)) $pperms[] = $more_perm;
						}
					}
				}
			}
			
			//	Next, pull up the list of groups the User belongs to.
			$groups_found = $this->raw_sql("select `{$config['db-groupmembership-groupid']}` from `{$config['db-groupmembership-tablename']}` where `{$config['db-groupmembership-userid']}`=\"{$_SESSION['user_id']}\"");
			$ugroups = array();
			if ($groups_found) {
				$ugroups = $this->results->fetchAll(PDO::FETCH_COLUMN, 0);
			}

			//	Pull up the ACLs for all the Perms we found which apply to the user and all the groups they belong to.
			if (!isset($pperms) || !is_array($pperms)) $pperms = array();
			array_unshift($pperms, $obj_perm);
			$uacls = array();
			$gacls = array();
			foreach ($pperms as $perm) {
				if ($perm == 0) continue;
				$uacls_found = $this->raw_sql("select `{$config['db-accesscontrollist-permissionlevel']}`+0 from `{$config['db-accesscontrollist-tablename']}` where `{$config['db-accesscontrollist-userid']}`=\"{$_SESSION['user_id']}\" and `{$config['db-accesscontrollist-permission']}`={$perm}");
				if ($uacls_found) {
					$uacl = $this->results->fetchColumn();
					if ($uacl !== false) $uacls[] = $uacl;
				}
				foreach ($ugroups as $group) {
					$gacls_found = $this->raw_sql("select `{$config['db-accesscontrollist-permissionlevel']}`+0 from `{$config['db-accesscontrollist-tablename']}` where `{$config['db-accesscontrollist-groupid']}`=\"{$group}\" and `{$config['db-accesscontrollist-permission']}`={$perm}");
					if ($gacls_found) {
						$gacl = $this->results->fetchColumn();
						if ($gacl !== false) $gacls[] = $gacl;
					}
				}
			}
			$acls = array_merge($uacls, $gacls, array(3));
			
			//	Finally, calculate a Perm value based on all the ACLs, remembering that entries closer to the user/object override those further up.
			$acl_final = $acls[0];
//			error_log("DB_MySQL::acl_check || ACLS [".var_export($acls, true)."] :: [{$acl_final}]");
			
			return $acl_final;
		}
		
		public function acl_iface ($cname) {
			return $this->acl_check('iface', $cname);
		}
		
		public function acl_table ($name) {
			return $this->acl_check('table', $name);
		}
		
		public function acl_field ($table, $field) {
			return $this->acl_check('field', array('table' => $table, 'field' => $field));
		}
		
		public function get_data_value ($table, $record, $field, $alt = false) {
			if ((($this->acl_field($table, $field) & 1) == 0) || (($this->acl_table($table) & 1) == 0)) return $alt;		//	Check for read rights.
//			error_log ("DB_MySQL::get_data_value || User has read permissions for this field and table [{$table} || {$field} || {$record}]");
			$result = $this->raw_sql("select `{$field}` from `{$table}` where {$record}");
			if ($result === false) return $alt;
			return $this->get_result_value($field, 0);
		}
		
	}
?>
