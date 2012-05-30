<?php
	include_once(dirname(__FILE__) . "/in-site-check.php");
	
	/** config.php
		Provides configuration information for the IDLX Framework.
		Project-specific settings will override server-wide settings given here.
	*/

	$server_config = array (
		'db' => array (
			'engine' => 'MySQL',
			'host' => '127.0.0.1',
			'user' => 'idlx-default',
			'pass' => 'idlx-password',
			'name' => 'idlx',
			'userinfo' => array (							//	These are the default names for the various IDLX-defined tables and their fields.  These are set in case a Project needs to override them.
				'tablename' => 'UserInfo',					//	
				'userid' => 'UserID',						//	The UserInfo table only mandates the presence of a UserID field.  Each Auth_ module is responsible for defining other fields for authentication.
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
			),												//	--------------------------------------------------------------------------------------------------------------
		),
		'output' => 'Web',									//	The output type; this case defines XHTML+CSS+JS.  Only one value can be set for a given project.  To support multiple methods, use a dynamically-set value in place of a hard-coded one.
		'auth' => array (
			'engine' => array (								//	auth-engine supports a fallback mechanism:
				0 => 'CLO',									//	auth-engine-0 is the default auth mechanism,
				1 => 'Pass',								//	auth-engine-1 is the first fallback mech if auth-engine-0 fails,
			),												//	and auth-engine-2 would be the second fallback, etc.
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