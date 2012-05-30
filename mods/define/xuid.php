<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/define/xuid.php
		Defines the PHP interface for the XML User Interface Dialect modules.
		
		XUID modules serve two purposes:
			1:	Interpret IDLX Interfaces to prepare them for user interaction.
			2:	Generate output for the target user interface platform.
		
		This means that there isn't a single module for each XUID - there must be one for each Interface/UI combination.
		
		As an example, the initial version of IDLX will support XForms in Interfaces, and XHTML+CSS+JS as the UI.
		However, an additional module would be needed for XForms-to-XHTML+CSS+JS+XForms (a version which doesn't translate the XForms into XHTML).
		This gets even more complex as additional XUIDs and UI platforms become supported.
		
		The alternative would be to define an internal format which each XUID module translates Interfaces to, and establish UI modules to translate
		the internal format to the UI format.  This is not out of the question, but will not be done until a much later version of the IDLX.  This
		modification to the internal structure of the Framework should be entirely transparent to Interfaces developed on it.
	*/
	
	interface XUIDModule {
		static function get_handler();			//	Tells the core what namespace this module processes.
		static function get_output_ns();		//	Tells the core what namespace this module produces.
		function translate(DOMNode $node);		//	Takes the XUID $node and translates it to the desired output format.  Returns false on failure.
	}
?>