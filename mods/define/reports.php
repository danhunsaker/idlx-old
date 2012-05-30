<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/define/reports.php
		Defines the PHP interfaces for the two types of Reports modules.
	*/
	
	/** ReportProcessor interface
		ReportProcessor modules interpret the report markup and convert it to XML+XSL-FO.
		The result is an unambiguous description of how the report should look, regardless of final output format.
		All data will be filled in by these modules.
		
		Example: RDL
	*/
	interface ReportProcessor {
		
	}
	
	/** ReportGenerator interface
		ReportGenerator modules process the XML+XSL-FO into the final output format.
		
		Example: PDF
	*/
	interface ReportGenerator {
		
	}
?>