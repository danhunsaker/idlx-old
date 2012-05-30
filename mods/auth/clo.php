<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/auth/clo.php
		Contains the CLO Auth module.
	*/

	if (!isset($config['db-userinfo-certdn'])) $config['db-userinfo-certdn'] = 'CertDN';
	if (!isset($config['auth-clo-ldap'])) $config['auth-clo-ldap'] = false;
	
	class Auth_CLO implements AuthModule {
		public function auth (DBModule $db_module) {
			global $config;
			if (!isset($_SERVER['SSL_CLIENT_CERT']) || empty($_SERVER['SSL_CLIENT_CERT'])) return false;
			$cert = openssl_x509_parse ($_SERVER['SSL_CLIENT_CERT']);
			if (empty($cert['name'])) return false;
			return $db_module->get_user(array($config['db-userinfo-certdn'] => $cert['name']));
		}
		
		public function unauth() {
			if (!isset($_SESSION['user_id'])) return false;
			session_unset($_SESSION['user_id']);
			return true;
		}
		
		public function user_add_update ($uid) {
			global $config, $db;
			
			if ($uid === null) {
				$uid = uniqid('idlx'.dechex(crc32($_SERVER['SERVER_NAME'])+0), true);		//	35 characters long!
				error_log ("Auth_CLO::user_add_update || UserID passed was NULL.  Generating one.  [{$uid}]");
			}
			
			if (!$db->get_user(array($config['db-userinfo-userid'] => $uid))) {
				error_log ("Auth_CLO::user_add_update || User doesn't yet exist [{$uid}].");
			}
			
			//	Get new cred details, somehow.
			
			return $db->save_user($uid, array($config['db-userinfo-login'] => $user, $config['db-userinfo-password'] => $pass));
		}
	}
?>