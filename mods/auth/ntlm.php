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
			//	If auth failed, check that the response wasn't NTLMv1...
			if ($auth['authenticated'] === false) $auth = ntlm_prompt('IDLX Framework', $config['auth-ntlm-domain'], $config['auth-ntlm-server'], $config['auth-ntlm-domain'], $config['auth-ntlm-server'], 'ntlm_auth_user_hash', 'ntlm_verify_des');
			
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
					return $db_module->get_user(array($config['db-userinfo-login'] => $this->user, $config['db-userinfo-password'] => $this->pass));
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
//			echo '<span style="font-size: 3em;">Successfully Logged Out</span>';
			session_unset();
			session_destroy();
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
			
			//	Get new cred details, somehow.
			
			return $db->save_user($uid, array($config['db-userinfo-login'] => $user, $config['db-userinfo-password'] => $pass));
		}

	}
	
	function ntlm_auth_user_hash ($user) {
		global $config, $db, $auth;
		
//		error_log("Auth_NTLM  ntlm_auth_user_hash || NTLM request [{$user}]");
		
		$user_exists = $db->raw_sql("select AES_DECRYPT(`{$config['db-userinfo-password']}`, '{$config['db-encryption-password']}') as {$config['db-userinfo-password']} from `{$config['db-userinfo-tablename']}` where `{$config['db-userinfo-login']}`=\"{$user}\"");
		if ($user_exists === false) {
			error_log ("Auth_NTLM  ntlm_auth_user_hash || Username [{$user}] not in database.");
			return false;
		}
		$pass = $db->get_result_value($config['db-userinfo-password'], 0);		//	Because storing unencrypted passwords in the database is inherently dangerous, $pass is equivalent to the A1 section of a Digest Auth response.
		$auth->store_details($user, $pass);

//		error_log("Auth_NTLM  ntlm_auth_user_hash || Generating NTLM password hash [{$pass}]");
		
		return ntlm_md4(ntlm_utf8_to_utf16le(utf8_encode($pass)));
	}
	
	function ntlm_verify_des($challenge, $user, $domain, $workstation, $clientblobhash, $clientblob, $get_ntlm_user_hash) {
//		error_log ("mods/auth/ntlm.php ntlm_verify_des || Attempting NTLM auth with DES instead of HMAC_MD5");
		
		$md4hash = $get_ntlm_user_hash($user);
		if (!$md4hash)
			return false;
		
		$md4hash .= str_repeat("\x00", 5);
		for ($i = 0, $passalong = array(), $md4h2 = ''; $i < strlen($md4hash); $i++) {
			$tmpval = array_merge($passalong, str_split(sprintf('%08s', decbin(ord(substr($md4hash, $i, 1))))));
			
			$passalong = array_slice($tmpval, 7);
			$tmpval = array_slice($tmpval, 0, 7);
			$parity = array_count_values($tmpval);
//			error_log ("mods/auth/ntlm.php ntlm_verify_des || Parity check [".var_export($parity, true)."]");
			$tmpval[7] = (isset($parity['1']) && (($parity['1'] % 2) == 1)) ? '1' : '0';
			$md4h2 .= chr(bindec(implode('', $tmpval)));
			
			if (count($passalong) == 7) {
				$pa_parity = array_count_values($passalong);
//				error_log ("mods/auth/ntlm.php ntlm_verify_des || PassAlong Parity check [".var_export($pa_parity, true)."]");
				$passalong[7] = (isset($pa_parity['1']) && (($pa_parity['1'] % 2) == 1)) ? '1' : '0';
				$md4h2 .= chr(bindec(implode('', $passalong)));
				$passalong = array();
			}
		}
		
//		error_log ("mods/auth/ntlm.php ntlm_verify_des || Keystring converted [".bin2hex($md4hash)." => ".bin2hex($md4h2)."]");
		
		$key1 = substr($md4h2, 0, 8);
		$key2 = substr($md4h2, 8, 8);
		$key3 = substr($md4h2, 16, 8);
/*
		$key1 = substr($md4hash, 0, 7);
		$key2 = substr($md4hash, 7, 7);
		$key3 = substr($md4hash, 14, 7);
*/		
		$blobhash  = mcrypt_encrypt(MCRYPT_DES, $key1, $challenge, MCRYPT_MODE_ECB);
		$blobhash .= mcrypt_encrypt(MCRYPT_DES, $key2, $challenge, MCRYPT_MODE_ECB);
		$blobhash .= mcrypt_encrypt(MCRYPT_DES, $key3, $challenge, MCRYPT_MODE_ECB);
		
//		error_log ("mods/auth/ntlm.php ntlm_verify_des || Returning result of DES comparison [{$user}-@-{$domain} || ".bin2hex($blobhash)." || ".bin2hex($clientblobhash.$clientblob)."]");
		return ($blobhash == $clientblobhash.$clientblob);
	}
?>