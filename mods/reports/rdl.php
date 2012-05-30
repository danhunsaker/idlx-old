<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/define/reports.php
		Defines the PHP interfaces for the two types of Reports modules.
		Each module should only implement ONE of these module types.
	*/
	
	class ReportProc_RDL implements ReportProcessor {
		static function get_handler() {							//	Tells the core what report definition format this module processes (example, RDL).
			return 'rdl';
		}
		
		function process($input_file) {							//	Processes the report defined in $input_file.  Returns the XML+XSL-FO version of the finalized report, or false on error.
			global $proj_dir;
			$rdl = file_get_contents($proj_dir.'/reports/'.$input_file);
			$rdl_dom = new DOMDocument();
			$rdl_dom->loadXML($rdl);
			$rdl_xp = new DOMXPath($rdl_dom);
			$rdl_xp->registerNamespace('rdl', $rdl_dom->documentElement->namespaceURI);		//	Register the namespace for RDL, in such a way that the exact version doesn't matter.
			$rdl_xp->registerNamespace('rd', $rdl_dom->lookupNamespaceURI('rd'));			//	Same with RD...
			$rdl_xp->registerNamespace('cl', $rdl_dom->lookupNamespaceURI('cl'));			//	...and CL.  Just in case we need either of them.
			
			//	TODO: Process all the data fields
			
			//	TODO: Apply stylesheet
			
			return false;
		}
	}
	
	return 'ReportProc_RDL';
?>