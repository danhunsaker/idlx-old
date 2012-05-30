<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/auth/clo.php
		Contains the CLO Auth module.
	*/

	if (!isset($config['db-userinfo-certdn'])) $config['db-userinfo-certdn'] = 'CertDN';
	
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
	}
?>