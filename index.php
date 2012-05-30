<?php

	/**	index.php
		The core of the IDLX Framework rests here.  This is where the magic all begins, and where the modules are bossed around from.
	*/
	
	//	Some initialization stuff.
	ob_start();
	session_start();

	if (!defined('IN_SITE')) define('IN_SITE', true);
	$proj_dir = strtr(getcwd(), array('\\'=>'/'));
	if (substr($proj_dir, -1, 1) != '/') $proj_dir .= '/';
	chdir(dirname(__FILE__));
	
	//	Can't use __FILE__ to determine $siteroot because of the way Projects are implemented...
	$siteroot = strtr(dirname($_SERVER['SCRIPT_FILENAME']), array('\\' => '/', $_SERVER['DOCUMENT_ROOT'] => ''));

	include_once('util-functions.php');

	if (!isset($_SESSION['user_id']) && (!empty($_SERVER['QUERY_STRING']) || !empty($_SERVER['PATH_INFO']))) {
		send_to_siteroot('User not logged in', 'index.php', $siteroot);
	}
	
	include_once('config.php');
	include_once('load-modules.php');
	
	//	Initialization complete.  Do your work!
	$main_iface = $db->get_iface_cname('main');		//	An Interface whose CodeName == 'main' MUST exist.
	if ($main_iface == false) {
		error_log ("index.php || No 'main' Interface defined!!!  Cannot continue; reinstall the IDLX Framework and any IDLX Projects you may be using! [{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}{$_SERVER['PATH_INFO']}?{$_SERVER['QUERY_STRING']}]");
		die("Could not find 'main' Interface.  Cannot continue.  Please inform your site administrator of this error.  [Date/Time: ".gmdate(DATE_COOKIE)."]");
	}
	$if_dom = new DOMDocument();
	$if_dom->formatOutput = true;
	$if_dom->preserveWhiteSpace = false;
	$if_dom->loadXML($main_iface);
	$footer = $if_dom->importNode(get_footer(), true);
	$if_dom->documentElement->appendChild($footer);
	$xp = new DOMXPath($if_dom);
	$xp->registerNamespace('idlx', IDLX_NS_URI);
	$nothingNew = false;
	while ($nothingNew == false) {
		$if_dom->formatOutput = true;
		$if_dom->preserveWhiteSpace = false;
		$if_dom->normalizeDocument();
		error_log("index.php || Begin processing pass [{$if_dom->saveXML()}]");
		$nothingNew = true;
		
		$iface_nodes  = $xp->evaluate('//idlx:interface');	//	Pull in any requested external Interfaces.
//		error_log("index.php || Tag counts: interfaces [{$iface_nodes->length}]");
		if ($iface_nodes->length > 0) {
			$nothingNew = false;
			foreach ($iface_nodes as $node) {
				$iface_name = trim(utf8_decode($node->textContent));
//				error_log("index.php || Interface name [{$iface_name}]");
				$iface_contents = $db->get_iface_cname($iface_name);
				if ($iface_contents !== false) {
//					error_log("index.php || Importing external Interface [{$iface_name}]");
					$import_dom = new DOMDocument();
					$import_dom->loadXML($iface_contents);
					$new_node = $node->ownerDocument->importNode($import_dom->documentElement);
					$node->parentNode->replaceChild($new_node, $node);
				}
				else {
					error_log("index.php || Interface [{$iface_name}] not found; ignoring");
					$node->parentNode->removeChild($node);
				}
			}
			continue;		//	Restart processing to catch anything new.
		}
		
		$script_nodes = $xp->evaluate('//idlx:script');		//	Locate and process any scripts.
//		error_log("index.php || Tag counts: scripts [{$script_nodes->length}]");
		if ($script_nodes->length > 0) {
			$nothingNew = false;
			foreach ($script_nodes as $node) {
				$script_type = mb_strtolower(utf8_decode($node->attributes->getNamedItem('lang')->textContent));
//				error_log("index.php || Script type [{$script_type}]");
				if (isset($idlxs_mods[$script_type])) {
					$result = $idlxs_mods[$script_type]->run_script(utf8_decode($node->textContent));
//					error_log("index.php || Script output [{$result}]");
					$new_node = importFragment(utf8_encode(trim($result)), $node->ownerDocument);
//					error_log("index.php || Imported fragment [{$new_node->ownerDocument->saveXML($new_node)}]");
					$node->parentNode->replaceChild($new_node, $node);
					$node->ownerDocument->normalizeDocument();
				}
				else {
					error_log("index.php || No module loaded to handle [{$script_type}] scripts; ignoring");
					$node->parentNode->removeChild($node);
				}
			}
			continue;		//	Restart processing to catch anything new.
		}

		$report_nodes = $xp->evaluate('//idlx:report');		//	Locate and process any reports.
//		error_log("index.php || Tag counts: reports [{$report_nodes->length}]");
		if ($report_nodes->length > 0) {
			$nothingNew = false;
			foreach ($report_nodes as $node) {				//	Iterate through report nodes.
				$input_type = '';
				$input_file = '';
				$output_type = '';
				$output_style = '';
				$output_file = '';
				foreach ($node->childNodes as $childNode) {		//	Determine what type of report to generate, and how.
					if ($childNode->nodeType != XML_ELEMENT_NODE) continue;
					switch ($childNode->localName) {
					case 'input':								//	Specifies the report input parameters.
//						error_log("index.php || Processing report input node...");
						foreach ($childNode->childNodes as $inputChildNode) {
							if ($inputChildNode->nodeType != XML_ELEMENT_NODE) continue;
							switch ($inputChildNode->localName) {
							case 'format':						//	Indicates the format of the report definition file.
								$input_type = mb_strtolower(utf8_decode($inputChildNode->textContent));
//								error_log("index.php || Report input type [{$input_type}]");
								break;
							case 'description':					//	Indicates the name of the report definition file.
								$input_file = mb_strtolower(utf8_decode($inputChildNode->textContent));
//								error_log("index.php || Report input file [{$input_file}]");
								break;
							default:							//	Undefined node type.  Ignore.
								error_log("index.php || Cannot process <{$inputChildNode->localName}> node; ignoring.");
//								$childNode->removeChild($inputChildNode);
								break;
							}
						}
						break;
					case 'output':								//	Specifies the report output parameters.
//						error_log("index.php || Processing report output node...");
						foreach ($childNode->childNodes as $outputChildNode) {
							if ($outputChildNode->nodeType != XML_ELEMENT_NODE) continue;
							switch ($outputChildNode->localName) {
							case 'format':						//	Indicates the format of the report output.
								$output_type = mb_strtolower(utf8_decode($outputChildNode->textContent));
//								error_log("index.php || Report output type [{$output_type}]");
								break;
							case 'return':						//	Indicates the return style of the report output.
								$output_style = mb_strtolower(utf8_decode($outputChildNode->textContent));
//								error_log("index.php || Report output style [{$output_style}]");
								break;
							case 'name':						//	Indicates the filename of the report output.  May have different meanings in some return styles.
								$output_file = mb_strtolower(utf8_decode($outputChildNode->textContent));
//								error_log("index.php || Report output file [{$output_file}]");
								break;
							default:							//	Undefined node type.  Ignore.
								error_log("index.php || Cannot process [{$outputChildNode->localName}] node; ignoring.");
//								$childNode->removeChild($outputChildNode);
								break;
							}
						}
						break;
					default:									//	Undefined node type.  Ignore.
						error_log("index.php || Cannot process [{$childNode->localName}] node; ignoring.");
//						$node->removeChild($childNode);
						break;
					}
				}

				if (isset($rep_proc_mods[$input_type]) && isset($rep_gen_mods[$output_type])) {		//	Can't generate a report if we don't have modules for it.
					$report = $rep_gen_mods[$output_type]->generate($rep_proc_mods[$input_type]->process($proj_dir.'reports/'.$input_file), $output_style, $output_file);
					if (empty($report) || $report === false) {		//	We want to replace the report node with the ReportGenerator output, but if there is none, we need to remove the node entirely.
						if (empty($report)) error_log("index.php || Report generated (responded with an empty string) [{$input_type}({$input_file}) => {$output_type}::{$output_style}({$output_file})]");
						else error_log("index.php || Report generator encountered an error [{$input_type}({$input_file}) => {$output_type}::{$output_style}({$output_file})]");
						$node->parentNode->removeChild($node);
					}
					else {										//	The ReportGenerator returned a non-empty result - add it to the output.
//						error_log("index.php || Report generated (responded with report presentation) [{$input_type}({$input_file}) => {$output_type}::{$output_style}({$output_file})]");
						$new_node = importFragment(utf8_encode(trim($report)), $node->ownerDocument);
						$node->parentNode->replaceChild($new_node, $node);
						$node->ownerDocument->normalizeDocument();
					}
				}
				else {		//	Clean up the report node so it doesn't clutter the output.
					error_log("index.php || Cannot generate report (missing one or more compatible modules) [{$input_type}({$input_file}) => {$output_type}::{$output_style}({$output_file})]");
					$node->parentNode->removeChild($node);
				}
			}
			continue;		//	Restart processing to catch anything new.
		}
		
		$db_nodes     = $xp->evaluate('//idlx:table|//idlx:record|//idlx:field');		// Locate and process data nodes.
//		error_log("index.php || Tag counts: db value selectors [{$db_nodes->length}]");
		if ($db_nodes->length > 0) {
			$nothingNew = false;
			$data_table = '';
			$data_record = '';
			$data_field = '';
			$data_alt = '';
			foreach ($db_nodes as $node) {
				$db_type = mb_strtolower(utf8_decode($node->localName));
//				error_log("index.php || Data node type [{$db_type}]");
				switch ($db_type) {
				case 'table':
					$data_table = utf8_decode($node->textContent);
					$node->parentNode->removeChild($node);
					break;
				case 'record':
					$data_record = utf8_decode($node->textContent);
					$node->parentNode->removeChild($node);
					break;
				case 'field':
					$data_field = utf8_decode($node->textContent);
					$data_alt = utf8_decode(@$node->attributes->getNamedItem('alt')->textContent);
					$data_alt = empty($data_alt) ? '::Undefined::' : $data_alt;
					$result = $db->get_data_value($data_table, $data_record, $data_field, $data_alt);
					$new_node = importFragment(utf8_encode(trim($result)), $node->ownerDocument);
					$node->parentNode->replaceChild($new_node, $node);
					$node->ownerDocument->formatOutput = true;
					$node->ownerDocument->preserveWhiteSpace = false;
					$node->ownerDocument->normalizeDocument();
					break;
				default:
					error_log("index.php || Could not interpret data node [{$db_type}]; ignoring.");
					$node->parentNode->removeChild($node);
					break;
				}
			}
			continue;		//	Restart processing to catch anything new.
		}
		
		//	Locate and process XUID nodes.
		$xuid_xpath = '//*[namespace-uri()!="'.IDLX_NS_URI.'" and namespace-uri()!="'.implode('" and namespace-uri()!="', $config['output']).'" and namespace-uri()!=namespace-uri(parent::*)]';
		$xuid_nodes   = $xp->evaluate($xuid_xpath);
//		error_log("index.php || Tag counts: xuid blocks [{$xuid_nodes->length}]");
		if ($xuid_nodes->length > 0) {
			$nothingNew = false;
			foreach ($xuid_nodes as $node) {
				$xuid_type = mb_strtolower(utf8_decode($node->namespaceURI));
				error_log("index.php || XUID namespace URI [{$xuid_type}]");
				if (isset($xuid_mods[$xuid_type])) {
					$result = $xuid_mods[$xuid_type]->translate($node);
					$new_node = $node->ownerDocument->importNode($result, true);
					$node->parentNode->replaceChild($new_node, $node);
				}
				else {
					$node->parentNode->removeChild($node);
				}
			}
			continue;		//	Restart processing to catch anything new.
		}
		
		$logout_nodes = $xp->evaluate('//idlx:logout');		//	Locate and handle any logout directives.
//		error_log("index.php || Tag counts: logout directives [{$logout_nodes->length}]");
		if ($logout_nodes->length > 0) {
			if (!$auth->unauth()) {
				error_log("index.php || Logout failed.  Check the code?");
			}
			header('Refresh: 5');
			$notice = $if_dom->createElementNS('http://www.w3c.org/1999/xhtml/', 'p', 'Successfully Logged Out');
			$notice->setAttribute('style', 'font-size: 3em;');
			$notice_given = false;
			foreach ($logout_nodes as $node) {
//				error_log("index.php || Removing logout directive.");
				if (!$notice_given) {
					$node->parentNode->replaceChild($notice, $node);
					$notice_given = true;
				}
				else {
					$node->parentNode->removeChild($node);
				}
			}
			continue;		//	This is only here in case any new checks are added below; otherwise, it's redundant.
		}
	}
	
	error_log("index.php || Processing complete; commencing cleanup of IDLX artifacts [{$if_dom->saveXML()}]");
	if (isset($xuid_mods[IDLX_NS_URI])) {
		$if_dom = $xuid_mods[IDLX_NS_URI]->translate($if_dom);
	}
	error_log("index.php || Cleanup complete [{$if_dom->saveXML()}]");
	
	@$if_dom->loadXML($if_dom->saveXML());
	$if_dom->formatOutput = true;
	$if_dom->preserveWhiteSpace = false;
	
	$if_dom = clean_whitespace_from_nodes($if_dom);
	echo $if_dom->saveXML();
	
	//	That's all she wrote, folks.  Clean up and go home.
//	header('Content-type: text/xhtml;');	//	Remove this when development is complete!
	chdir($proj_dir);
	ob_end_flush();
?>