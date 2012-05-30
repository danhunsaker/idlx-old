<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/auth/_ldap_.php
		This is an auxiliary module for LDAP verification of user info in the (project) database.
		The class it defines is only defined to keep the requisite functions from cluttering the global function tables,
		and to reduce the chance of accidental misuse of the same.  There are a number of settings involved, most of which
		should be set on the project level to associate each project with the correct LDAP directory.
	*/
	
	if (!isset($config['auth-ldap-account-suffix'])) $config['auth-ldap-account-suffix'] = null;
	if (!isset($config['auth-ldap-base-dn'])) $config['auth-ldap-base-dn'] = null;
	if (!isset($config['auth-ldap-domain-controllers-0']))	{
		$config['auth-ldap-domain-controllers'] = array(
			'localhost'
		);
	}
	else {
		$config['auth-ldap-domain-controllers'] = array();
		for ($count = 0; isset($config["auth-ldap-domain-controllers-{$count}"]); $count++) {
			$config['auth-ldap-domain-controllers'][$count] = $config["auth-ldap-domain-controllers-{$count}"];
			unset($config["auth-ldap-domain-controllers-{$count}"]);
		}
	}
	if (!isset($config['auth-ldap-admin-uname'])) $config['auth-ldap-admin-uname'] = null;
	if (!isset($config['auth-ldap-admin-pass'])) $config['auth-ldap-admin-pass'] = null;
	if (!isset($config['auth-ldap-use-ssl'])) $config['auth-ldap-use-ssl'] = false;
	if (!isset($config['auth-ldap-use-tls'])) $config['auth-ldap-use-tls'] = false;
	
	include_once('support/adLDAP/adLDAP.php');

	class _LDAP_Verify_ {
		private $ldap = null;
		
		function __construct () {
			global $config;

			if (!is_a($this->ldap, 'adLDAP')) {
				try {
//					error_log ("Auth_ldap::ldap_auth_user || Attempting to connect to LDAP server [{$config['auth-ldap-account-suffix']} || ".var_export($config['auth-ldap-domain-controllers'], true)."]");
					$this->ldap = new adLDAP(array(
						'account_suffix' => $config['auth-ldap-account-suffix'],
						'base_dn' => $config['auth-ldap-base-dn'],
						'domain_controllers' => $config['auth-ldap-domain-controllers'],
						'use_ssl' => $config['auth-ldap-use-ssl'],
						'use_tls' => $config['auth-ldap-use-tls'],
					));
				}
				catch (adLDAPException $e) {
					error_log ("Auth_ldap::ldap_auth_user || Failed to create LDAP object [{$e}]");
					return false;
				}
			}
		}
		
		function verify_auth ($user, $pass) {
			$auth_status = $this->ldap->authenticate($user, $pass);
			if (!$auth_status) error_log("Auth_ldap::ldap_auth_user || Failed to authenticate [{$this->ldap->getLastError()}]");
			return $auth_status;
		}
	}
?>