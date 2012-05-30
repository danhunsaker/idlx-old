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
		
		public function add_user ($uid) {
			global $config, $db;
			
			if (!$db->get_user(array($config['db-userinfo-userid'] => $uid))) {
				error_log ("Auth_CLO::add_user || User already exists [{$uid}]!  Sending to Auth_CLO::change_creds()");
				return $this->change_creds($uid);
			}
			
			//	Get new cert details, somehow.
			
			return $db->save_user($uid, array($config['db-userinfo-certdn'] => $cert['name']));
		}
		
		public function change_creds ($uid) {
			global $config, $db;
			
			if (!$db->get_user(array($config['db-userinfo-userid'] => $uid))) {
				error_log ("Auth_CLO::change_creds || User does not already exist [{$uid}].  Sending to Auth_CLO::add_user()");
				return $this->add_user($uid);
			}
			
			//	Get new cert details, somehow.
			
			return $db->save_user($uid, array($config['db-userinfo-certdn'] => $cert['name']));
		}
	}
?>