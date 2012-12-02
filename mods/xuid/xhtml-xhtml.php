<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/xuid/xhtml.php
		Defines the XUID module for handling XHTML.
	*/
	
	class XUID_XHTML_XHTML implements XUIDModule {
		static function get_handler() {			//	Tells the core what namespace this module processes.
			return "http://www.w3c.org/1999/xhtml/";
		}
		static function get_output_ns() {		//	Tells the core what namespace this module produces.
			return "http://www.w3c.org/1999/xhtml/";
		}
		function translate(DOMNode $node) {		//	Takes the XUID $node and translates it to the desired output format.  Returns false on failure.
			return $node;
		}
	}
	
	return 'XUID_XHTML_XHTML';
?>