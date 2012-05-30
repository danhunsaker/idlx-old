<?php
	include_once(dirname(__FILE__) . "/in-site-check.php");
	
	/** config.php
		Provides configuration information for the IDLX Framework.
		Project-specific settings will override server-wide settings given here.
		You will probably be building Projects on top of the Framework, and should
		use the Project-level config.php to set these, as well as any required by
		the modules you decide to use.
	*/

	$server_config = array (
		'db' => array (
			'engine' => 'MySQL',
			'host' => '127.0.0.1',
			'user' => 'idlx-default',
			'pass' => 'idlx-password',
			'name' => 'idlx',
			'encryption-password' => 'idlx-encrypt',		//	YOU WANT TO CHANGE THIS!  YOU SHOULD ALSO USE SOMETHING DIFFERENT IN YOUR PROJECT CONFIGS!
			'scripts' => array (							//	Scripts should use a different username and password, so as to limit what the DB will allow them to do.
				'user' => 'idlx-scripts',					//	So, we set a separate username here...
				'pass' => 'idlx-scripts-password',			//	...and a separate password here.
			),												//	
			'userinfo' => array (							//	These are the default names for the various IDLX-defined tables and their fields.  These are set in case a Project needs to override them.
				'tablename' => 'UserInfo',					//	
				'userid' => 'UserID',						//	The UserInfo table only mandates the presence of these three fields.  Each Auth_ module is responsible for defining other fields it may need.
				'login' => 'Login',							//	
				'password' => 'Password',					//
			),												//	
			'interfaces' => array (							//	The heart of the IDLX Framework, and the source of its name.
				'tablename' => 'Interfaces',				//	
				'id' => 'ID',								//	
				'interfacename' => 'InterfaceName',			//	
				'codename' => 'CodeName',					//	
				'codeblock' => 'CodeBlock',					//	
				'notes' => 'Notes',							//	
			),												//	
			'permissions' => array (						//	Defines which objects (interfaces, tables, and fields) can have access control applied to them.
				'tablename' => 'Permissions',				//	
				'id' => 'ID',								//	
				'permname' => 'PermName',					//	
				'interfaceid' => 'InterfaceID',				//	
				'tblname' => 'TableName',					//	
				'fieldname' => 'FieldName',					//	
				'parentperm' => 'ParentPerm',				//	
				'details' => 'Details',						//	
			),												//	
			'accesscontrollist' => array (					//	Associates permissions entries with users and groups.
				'tablename' => 'AccessControlList',			//	
				'id' => 'ID',								//	
				'userid' => 'UserID',						//	
				'groupid' => 'GroupID',						//	
				'permission' => 'Permission',				//	
				'permissionlevel' => 'PermissionLevel',		//	
			),												//	
			'groups' => array (								//	Defines groups to which users can belong, and for which access controls can be applied.
				'tablename' => 'Groups',					//	
				'groupid' => 'GroupID',						//	
				'groupname' => 'GroupName',					//	
				'administrator' => 'Administrator',			//	
				'notes' => 'Notes',							//	
			),												//	
			'groupmembership' => array (					//	Identifies which users are members of which groups.
				'tablename' => 'GroupMembership',			//	
				'id' => 'ID',								//	
				'userid' => 'UserID',						//	
				'groupid' => 'GroupID',						//	
			),												//	
		),													//	--------------------------------------------------------------------------------------------------------------
		'output' => array (									//	The output types; this case defines XHTML+CSS+JS.  All namespace URIs defined here will be passed to the browser unparsed.
			'http://www.w3c.org/1999/xhtml/',				//	To support different valid output namespaces depending on UI platform, use dynamically-set values in place of hard-coded ones.
		),													//	
		'auth' => array (									//	
			'engine' => array (								//	auth-engine supports a fallback mechanism:
				0 => 'CLO',									//	auth-engine-0 is the default auth mechanism,
				1 => 'NTLM',								//	auth-engine-1 is the first fallback mech if auth-engine-0 fails,
				2 => 'Pass',								//	and auth-engine-2 is the second fallback, etc.
			),												
		),
	);

	$server_config = collapse_multi_array($server_config);

	if (isset($config) && is_array($config))
		$config = collapse_multi_array($config);
	else
		$config = array();
	
	foreach ($server_config as $key=>$val)
		if (!isset($config[$key]))		//	Only add new settings to the array; don't replace existing ones.  This allows projects to override the defaults.
			$config[$key] = $val;
?>