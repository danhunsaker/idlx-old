<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/reports/pdf.php
		Implements the ReportGenerator to create a PDF version of a report.
	*/

	class ReportGen_PDF implements ReportGenerator {
		static function get_handler() {							//	Tells the core what report output format this module generates.  Uses MIME-Type.
			return 'application/pdf';
		}
		
		function generate($xml) {		//	Generates the report from the XML+XSL-FO in $xml.  Returns report on success; false on error.
			if ($xml === false || empty($xml)) return false;	//	Cannot process XSL-FO if there is none to process.
			
			$cmd = strtr(getcwd(), '\\', '/').'/support/fop/fop -dpi 300 -fo - -pdf -';

			$descriptorspec = array(
			   0 => array("pipe", "r"),	// stdin is a pipe that the child will read from
			   1 => array("pipe", "w"),	// stdout is a pipe that the child will write to
			   2 => array("pipe", "w"),	// stderr is a file to write to
			);

			$process = proc_open($cmd, $descriptorspec, $pipes);

			if (is_resource($process)) {
				fwrite($pipes[0], $xml);
				fclose($pipes[0]);

				$pdf = stream_get_contents($pipes[1]);
				fclose($pipes[1]);
				
				$err = stream_get_contents($pipes[2]);
				fclose($pipes[2]);
				
				$errs = explode("\n", strtr($err, "\r", ''));
				foreach ($errs as $err) error_log("ReportGen_PDF::generate || FOP returned error [{$err}]");

				// It is important to close any pipes before calling
				// proc_close in order to avoid a deadlock
				$return_value = proc_close($process);
				error_log("ReportGen_PDF::generate || FOP retval [{$return_value}]");

				return $pdf;
			}
			else {
				
				return false;
			}
		}
	}
	
	return 'ReportGen_PDF';
?>