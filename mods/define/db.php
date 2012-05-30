<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/define/db.php
		Defines the PHP interface for the Database modules.
	*/
	
	interface DBModule {
		public function __construct ($server, $user, $pass, $database);	//	Connects to the database server and selects the requested database for use by queries.
		public function raw_sql ($sql_query);							//	Performs a SQL query against the database.  True on success; false otherwise.
		public function get_result_value ($column, $row);				//	Retrieves a value from a prior request, specified by column name/number, and row number.  Returns false on failure.
		public function get_user (array $creds);						//	Returns a UID corresponding to the credentials handled by the AuthModule, or false if the credentials don't apply to an existing user.
		public function save_user ($uid, array $creds);					//	Saves a user to the database according to UID and the credentials used by the AuthModule performing the call.  Returns true on success; false otherwise.
	}
?>