<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/auth/pass.php
		Contains the Password Auth module.
	*/
	
	if (!isset($config['auth-pass-realm'])) $config['auth-pass-realm'] = 'IDLX Framework';
	if (!isset($config['auth-pass-ldap'])) $config['auth-pass-ldap'] = false;
	
	class Auth_Pass implements AuthModule {
		public function auth (DBModule $db_module) {
			global $config;			//	Need to use the database info from the current config, in case the database we're using has different names for these.
			$realm = $config['auth-pass-realm'];
//			if (isset($_SESSION['user_id'])) return $_SESSION['user_id'];
			if (!isset($_SESSION['nonce'])) {						//	Check whether a nonce was already set.
				error_log ("Auth_Pass::auth || Session expired or user logged off.  Requesting new login.");		//	Nope.  Bad browser!
				$_SESSION['nonce'] = uniqid();						//	It wasn't; generate one.
				return $this->send_digest_request($realm);			//	Request credentials.
			}
			if (empty($_SERVER['PHP_AUTH_DIGEST'])) {				//	Check whether credentials have been supplied.
				$_SESSION['nonce'] = uniqid();						//	They haven't; generate a nonce.
				return $this->send_digest_request($realm);			//	Request credentials.
			}
			if (!($data = $this->http_digest_parse($_SERVER['PHP_AUTH_DIGEST']))) {		//	Credentials were supplied, but let's see if the browser returned them all...
				error_log ("Auth_Pass::auth || Digest Auth failed to provide valid data [{$_SERVER['PHP_AUTH_DIGEST']}]");		//	Nope.  Bad browser!
				return $this->send_digest_request($realm);			//	Request new credentials.
			}
			$user_exists = $db_module->raw_sql("select AES_DECRYPT(`{$config['db-userinfo-password']}`, '{$config['db-encryption-password']}') as {$config['db-userinfo-password']} from `{$config['db-userinfo-tablename']}` where `{$config['db-userinfo-login']}`=\"{$data['username']}\"");
			if ($user_exists === false) {
				error_log ("Auth_Pass::auth || Username [{$data['username']}] not in database.");
				return $this->send_digest_request($realm);			//	Request new credentials.
			}
			$pass = $db_module->get_result_value($config['db-userinfo-password'], 0);		//	Because storing unencrypted passwords in the database is inherently dangerous, $pass is encrypted.
			$a1 = md5("{$data['username']}:{$realm}:{$pass}");
			$a2 = md5("{$_SERVER['REQUEST_METHOD']}:{$data['uri']}");
			$valid_response = md5("{$a1}:{$_SESSION['nonce']}:{$data['nc']}:{$data['cnonce']}:{$data['qop']}:{$a2}");
			if ($data['response'] != $valid_response) {
				error_log ("Auth_Pass::auth || Incorrect username/password combination. [{$data['response']} || {$valid_response} || {$_SERVER['PHP_AUTH_DIGEST']}]");
				return $this->send_digest_request($realm);			//	Request new credentials.
			}
			
			return $db_module->get_user(array($config['db-userinfo-login'] => $data['username'], $config['db-userinfo-password'] => $pass));
		}
		
		private function send_digest_request($realm) {
			header('WWW-Authenticate: Digest realm="' . $realm . '",qop="auth",nonce="' . $_SESSION['nonce'] . '",opaque="' . md5($realm) . '"');
			header('HTTP/1.1 401 Unauthorized');					//	Request credentials from the browser.
			return false;
		}
		
		private function http_digest_parse ($txt) {
			$needed = array(
				'nonce' => 1,
				'nc' => 1,
				'cnonce' => 1,
				'qop' => 1,
				'username' => 1,
				'uri' => 1,
				'response' => 1
			);
			$data = array();
			$keys = implode('|', array_keys($needed));
			
			preg_match_all('@('.$keys.')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);
			
			foreach ($matches as $m) {
				$data[$m[1]] = $m[3] ? $m[3] : $m[4];
				unset($needed[$m[1]]);
			}
			
			return $needed ? false : $data;
		}
		
		public function unauth() {
			if (!isset($_SESSION['user_id'])) return false;
//			error_log ("Auth_Pass::unauth || Instructed to log out...");
			unset($_SESSION['user_id']);
			unset($_SESSION['nonce']);
			header('HTTP/1.1 401 Unauthorized');
			echo '<span style="font-size: 3em;">Successfully Logged Out</span>';
			return true;
		}
		
		public function user_add_update ($uid) {
			global $config, $db;
			
			if (!$db->get_user(array($config['db-userinfo-userid'] => $uid))) {
				error_log ("Auth_Pass::add_user || User already exists [{$uid}]!  Sending to Auth_Pass::change_creds()");
				return $this->change_creds($uid);
			}
			
			//	Get new cert details, somehow.
			
			return $db->save_user($uid, array($config['db-userinfo-login'] => $username, $config['db-userinfo-password'] => $pass));
		}
		
	}
?>