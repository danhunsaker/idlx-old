<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/auth/NTLM.php
		The Auth Module for NTLM authentication.
	*/
	
	if (!isset($config['auth-ntlm-domain'])) $config['auth-ntlm-domain'] = null;
	if (!isset($config['auth-ntlm-server'])) $config['auth-ntlm-server'] = 'localhost';
	if (!isset($config['auth-ntlm-ldap'])) $config['auth-ntlm-ldap'] = false;

	include_once('support/ntlm.php');
	
	$ldap = null;
	
	class Auth_NTLM implements AuthModule {
		private $user = null;
		private $pass = null;
		
		public function auth (DBModule $db_module) {		//	Authenticates the user.  If a user is already authenticated, don't re-auth, just return.  Return value is the user's ID, or false on failure.
			global $config, $ldap;
			
//			error_log("Auth_NTLM::auth || Requesting NTLM credentials.");
			$auth = ntlm_prompt('IDLX Framework', $config['auth-ntlm-domain'], $config['auth-ntlm-server'], $config['auth-ntlm-domain'], $config['auth-ntlm-server'], 'ntlm_auth_user_hash');
			
			if ($auth['authenticated'] === true) {
				if ($config['auth-ntlm-ldap']) {
					include_once('support/idlx_ldap_helper.php');
					$ldap = new _LDAP_Verify_();
					$authed = $ldap->verify_auth($this->user, $this->pass);
//					if ($authed) error_log("Auth_NTLM::auth || Auth succeeded against LDAP!");
				}
				else {
//					error_log("Auth_NTLM::auth || Auth succeeded against DB!");
					$authed = true;
				}
				
				if ($authed) {
					$db_module->get_user(array($config['db-userinfo-login'] => $this->user, $config['db-userinfo-password'] => $this->pass));
				}
				else {
					error_log("Auth_NTLM::auth || LDAP auth verification failed!");
					$this->user = null;
					$this->pass = null;
					return false;
				}
			}
			else {
				error_log("Auth_NTLM::auth || Auth failed! [".var_export($auth, true)."]");
					$this->user = null;
					$this->pass = null;
				return false;
			}
		}
		
		public function unauth () {							//	Clears any user authentication information, effectively logging the user out.  No return value.
			if (!isset($_SESSION['user_id'])) return false;
//			error_log ("Auth_NTLM::unauth || Instructed to log out...");
			ntlm_unset_auth();
			$this->user = null;
			$this->pass = null;
			unset($_SESSION['user_id']);
			header('HTTP/1.1 401 Unauthorized');
			echo '<span style="font-size: 3em;">Successfully Logged Out</span>';
			return true;
		}
		
		public function store_details ($u, $p) {
			$this->user = $u;
			$this->pass = $p;
			return true;
		}
		
		public function user_add_update ($uid) {
			global $config, $db;
			
			if ($uid === null) {
				$uid = uniqid('idlx'.dechex(crc32($_SERVER['SERVER_NAME'])+0), true);		//	35 characters long!
				error_log ("Auth_NTLM::user_add_update || UserID passed was NULL.  Generating one.  [{$uid}]");
			}
			
			if (!$db->get_user(array($config['db-userinfo-userid'] => $uid))) {
				error_log ("Auth_NTLM::user_add_update || User doesn't yet exist [{$uid}].");
			}
			
			//	Get new cert details, somehow.
			//	Probably by calling a function to get them, since the process here and in ::change_creds is the same...
			
			return $db->save_user($uid, array($config['db-userinfo-login'] => $user, $config['db-userinfo-password'] => $pass));
		}

	}
	
	function ntlm_auth_user_hash ($user) {
		global $config, $db, $auth;
		
//		error_log("Auth_NTLM  ntlm_auth_user_hash || NTLM request [{$user}]");
		
		$user_exists = $db->raw_sql("select AES_DECRYPT(`{$config['db-userinfo-password']}`, '{$config['db-encryption-password']}') as {$config['db-userinfo-password']} from `{$config['db-userinfo-tablename']}` where `{$config['db-userinfo-login']}`=\"{$user}\"");
		if ($user_exists === false) {
			error_log ("Auth_NTLM  ntlm_auth_user_hash || Username [{$user}] not in database.");
			return $this->send_digest_request($realm);			//	Request new credentials.
		}
		$pass = $db->get_result_value($config['db-userinfo-password'], 0);		//	Because storing unencrypted passwords in the database is inherently dangerous, $pass is equivalent to the A1 section of a Digest Auth response.
		$auth->store_details($user, $pass);
		
		return ntlm_md4(ntlm_utf8_to_utf16le($pass));
	}
	
?>