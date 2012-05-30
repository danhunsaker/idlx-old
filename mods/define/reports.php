<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/define/reports.php
		Defines the PHP interfaces for the two types of Reports modules.
		Each module should only implement ONE of these module types.
	*/
	
	/** ReportProcessor interface
		ReportProcessor modules interpret the report markup and convert it to XML+XSL-FO.
		The result is an unambiguous description of how the report should look, regardless of final output format.
		All data will be filled in by these modules.
	*/
	interface ReportProcessor {
		static function get_handler();							//	Tells the core what report definition format this module processes (example, RDL).
		function process($input_file);							//	Processes the report defined in $input_file.  Returns the XML+XSL-FO version of the finalized report, or false on error.
	}
	
	/** ReportGenerator interface
		ReportGenerator modules process the XML+XSL-FO into the final output format.
	*/
	interface ReportGenerator {
		static function get_handler();							//	Tells the core what report output format this module generates (example, PDF).
		function generate($xml, $output_style, $output_file);	//	Generates the report from the XML+XSL-FO in $xml, according to the return style in $output_style.
																//	The meaning of $output_file and the return value vary based on $output_style.  Returns false on error.
	}
?>