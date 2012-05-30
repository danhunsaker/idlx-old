<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/define/auth.php
		Defines the PHP interface for the Authentication modules.
	*/
	
	interface AuthModule {
		public function auth (DBModule $db_module);						//	Authenticates the user.  If a user is already authenticated, don't re-auth, just return.  Return value is the user's ID, or false on failure.
		public function unauth ();										//	Clears any user authentication information, effectively logging the user out.  No return value.
	}
?>