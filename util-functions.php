<?php
	include_once(dirname(__FILE__) . "/in-site-check.php");
	
	/** util-functions.php
		Provides basic utility functions for use throughout the IDLX Framework and any child projects.
		Designed with the capability to override anything defined here by simply defining it before this file is called.
	*/
	
	define('IDLX_NS_URI', 'http://idlx.sourceforge.net/schema/2011/08/');
	
	if (!function_exists('collapse_multi_array')) {
		function collapse_multi_array (array $multi) {
			$collapsed = array();
			foreach ($multi as $key=>$val) {
				if (is_array($val)) {
					$val = collapse_multi_array($val);
					foreach ($val as $sub_key=>$sub_val) {
						$collapsed[$key.'-'.$sub_key] = $sub_val;
					}
				}
				else {
					$collapsed[$key] = $val;
				}
			}
			return $collapsed;
		}
	}

	if (!function_exists('get_mods')) {
		function get_mods ($mod_type) {
			$flist = scandir("mods/{$mod_type}/");
			$output = array();
			foreach ($flist as $fname) {
				//	Skip UNIX-style hidden files, all directories, and any non-PHP files.
				if (substr($fname, 0, 1) == '.' || is_dir($fname) || substr($fname, -4) != '.php') continue;
//				error_log("util-functions.php - get_mods || Loading module [mods/{$mod_type}/{$fname}]");
				$class_name = include_once("mods/{$mod_type}/{$fname}");		//	Module files will have to return(class_name); at their end.
				if (class_exists($class_name)) {
//					error_log("util-functions.php - get_mods || Class name [{$class_name}]");
					$output[$class_name::get_handler()] = new $class_name();
				}
				else {															//	But if they violate this rule, try to make things work anyway.
					error_log("util-functions.php - get_mods || Class name INVALID [{$class_name}] - add a return('Name_of_Class') to the end of [mods/{$mod_type}/{$fname}].");
					$class_name = "{$mod_type}_".substr($fname,0,-4);
					if (class_exists($class_name)) {
//						error_log("util-functions.php - get_mods || Retrying with class name [{$class_name}]");
						$output[$class_name::get_handler()] = new $class_name();
					}
					else error_log("util-functions.php - get_mods || Cannot determine class name [{$class_name}]");
				}
			}
			return $output;
		}
	}
	
	if (!function_exists('importFragment')) {
		function importFragment ($xml, DOMDocument $dom) {
			$frag = $dom->createDocumentFragment();
			$frag->appendXML($xml);
			foreach ($frag->childNodes as $node) {
				if ($node->nodeType != XML_ELEMENT_NODE) continue;
				if (empty($node->namespaceURI)) $node->setAttribute('xmlns', IDLX_NS_URI);
			}
			$frag_out = $frag->ownerDocument->saveXML($frag);
			$frag = $dom->createDocumentFragment();
			$frag->appendXML($frag_out);
			return $frag;
		}
	}
	
	if (!function_exists('send_to_siteroot')) {
		function send_to_siteroot ($message, $caller = 'in-site-check.php', $siteroot = null) {
			if ($siteroot === null) {
				$siteroot = strtr(dirname($_SERVER['SCRIPT_FILENAME']), array('\\' => '/', $_SERVER['DOCUMENT_ROOT'] => ''));
			}
			if (!in_array(substr($message, -1), array('.', '!', '?'))) $message .= '.';
			@error_log ("{$caller} || {$message}  Redirecting to project root [{$siteroot}].  Doc requested [{$_SERVER['REQUEST_URI']}{$_SERVER['PATH_INFO']}?{$_SERVER['QUERY_STRING']}]");
			header('Location: '.$siteroot.'/');
			ob_end_clean();
			die();
		}
	}
	
	if (!function_exists('clean_whitespace_from_nodes')) {
		function clean_whitespace_from_nodes (DOMNode $dom) {
			if (!$dom->hasChildNodes()) {
				$dom->nodeValue = preg_replace(array('/^(\s)\s+/', "/(\s)\s+$/"), array('$1', '$1'), $dom->nodeValue);
				return $dom;
			}
			foreach ($dom->childNodes as $node) {
				$node = clean_whitespace_from_nodes ($node);
				if ($node->nodeType == XML_TEXT_NODE && $node->isWhitespaceInElementContent())
					$dom->removeChild($node);
			}
			return $dom;
		}
	}
	
	if (!function_exists('get_footer')) {
		function get_footer() {
			if (!need_ext('libxml')) return false;

			//	Create a new DOMDocument.  Things are cleaner this way.
			$dom = new DOMDocument();
			
			//	The footer div
			$foot_node = $dom->createElementNS('http://www.w3c.org/1999/xhtml/', 'div');
			$foot_node->setAttribute('class', 'footer');
			
			//	The copyright span
			$copy_node = $dom->createElementNS('http://www.w3c.org/1999/xhtml/', 'span', '&copy; 2011'.(date('Y') > 2011 ? '-'.date('Y') : '').' by ');
			$copy_node->setAttribute('id', 'copy');
			
			//	The IDLX team (sf.net site) link
			$team_node = $dom->createElementNS('http://www.w3c.org/1999/xhtml/', 'a', 'The IDLX Team');
			$team_node->setAttribute('href', 'http://idlx.sourceforge.net/');
			
			//	The last bit of the copyright span
			$copy_node_2 = $dom->createTextNode('.  Released under the GPL.');
			
			//	The supporting technologies paragraph
			$para_node = $dom->createElementNS('http://www.w3c.org/1999/xhtml/', 'p', 'The IDLX Framework would not be possible without the work of these projects:');
			$para_node->setAttribute('class', 'thanks');
			
			//	A line break to force the images to their own line.
			$break_node = $dom->createElementNS('http://www.w3c.org/1999/xhtml/', 'br');
			
			//	Link to PHP home page
			$link_node_php = $dom->createElementNS('http://www.w3c.org/1999/xhtml/', 'a');
			$link_node_php->setAttribute('href', 'http://www.php.net/');
			
			//	PHP logo image
			$logo_node_php = $dom->createElementNS('http://www.w3c.org/1999/xhtml/', 'img');
			$logo_node_php->setAttribute('src', 'images/php-med-trans.png');
			$logo_node_php->setAttribute('alt', 'PHP');
			
			//	Link to Apache FOP home page
			$link_node_fop = $dom->createElementNS('http://www.w3c.org/1999/xhtml/', 'a');
			$link_node_fop->setAttribute('href', 'http://xmlgraphics.apache.org/fop/');
			
			//	Apache FOP logo image
			$logo_node_fop = $dom->createElementNS('http://www.w3c.org/1999/xhtml/', 'img');
			$logo_node_fop->setAttribute('src', 'images/fop-logo.jpg');
			$logo_node_fop->setAttribute('alt', 'Apache FOP');
			
			//	Link to Adobe Reader download page
			$link_node_pdf = $dom->createElementNS('http://www.w3c.org/1999/xhtml/', 'a');
			$link_node_pdf->setAttribute('href', 'http://get.adobe.com/reader/');
			
			//	Get Reader logo image
			$logo_node_pdf = $dom->createElementNS('http://www.w3c.org/1999/xhtml/', 'img');
			$logo_node_pdf->setAttribute('src', 'images/get_adobe_reader.gif');
			$logo_node_pdf->setAttribute('alt', 'Adobe PDF');
			
			//	Put the pieces together in the correct order
			$link_node_php->appendChild($logo_node_php);
			$link_node_fop->appendChild($logo_node_fop);
			$link_node_pdf->appendChild($logo_node_pdf);
			$para_node->appendChild($break_node);
			$para_node->appendChild($link_node_php);
			$para_node->appendChild($link_node_fop);
			$para_node->appendChild($link_node_pdf);
			$copy_node->appendChild($team_node);
			$copy_node->appendChild($copy_node_2);
			$foot_node->appendChild($copy_node);
			$foot_node->appendChild($para_node);
			
			//	Send the result back to the caller
			return $foot_node;
		}
	}
	
	if (!function_exists('tidy_config')) {
		function tidy_config() {
			global $config;
			$ret = array (
				'input-xml' => true,
				'output-xml' => true,
				'output-xhtml' => false,
				'add-xml-space' => true,
				'clean' => true,
				'escape-cdata' => true,
//				'hide-comments' => true,
				'lower-literals' => true,
				'preserve-entities' => true,
				'indent' => true,
				'indent-attributes' => false,
				'indent-spaces' => 4,
				'markup' => true,
				'wrap' => 0,
				'wrap-attributes' => false,
				'newline' => 'LF',
				'force-output' => true,
			);
			if (in_array('http://www.w3c.org/1999/xhtml/', $config['output'])) {
				$ret['output-xhtml'] = true;
				$ret['output-xml'] = false;
			}
			return $ret;
		}
	}
	
	if (!function_exists('process_iface')) {
		function process_iface($iface) {
			global $config, $idlxs_mods, $xuid_mods, $db, $rep_proc_mods, $rep_gen_mods, $uid, $auth, $proj_dir, $siteroot, $idlxroot;
			
			if (!need_ext('libxml')) return false;
			if (!need_ext('tidy')) return false;
			
			$if_dom = new DOMDocument();
			$if_dom->formatOutput = true;
			$if_dom->loadXML($iface);
			$footer = $if_dom->importNode(get_footer(), true);
			$if_dom->documentElement->appendChild($footer);
			$xp = new DOMXPath($if_dom);
			$xp->registerNamespace('idlx', IDLX_NS_URI);
			$nothingNew = false;
			while ($nothingNew == false) {
				$if_dom->formatOutput = true;
				$if_dom->normalizeDocument();
		//		error_log("index.php || Begin processing pass [{$if_dom->saveXML()}]");
				$nothingNew = true;
				
				$iface_nodes  = $xp->evaluate('//idlx:interface');	//	Pull in any requested external Interfaces.
				error_log("index.php || Tag counts: interfaces [{$iface_nodes->length}]");
				if ($iface_nodes->length > 0) {
					$nothingNew = false;
					foreach ($iface_nodes as $node) {
						$iface_name = trim(utf8_decode($node->textContent));
		//				error_log("index.php || Interface name [{$iface_name}]");
						$iface_contents = $db->get_iface_cname($iface_name);
						if ($iface_contents !== false) {
		//					error_log("index.php || Importing external Interface [{$iface_name} || {$iface_contents}]");
							$import_dom = $node->ownerDocument->createDocumentFragment();
							$import_dom->appendXML($iface_contents);
							$node->parentNode->replaceChild($import_dom, $node);
						}
						else {
							error_log("index.php || Interface [{$iface_name}] not found; ignoring");
							$node->parentNode->removeChild($node);
						}
					}
					continue;		//	Restart processing to catch anything new.
				}
				
				$script_nodes = $xp->evaluate('//idlx:script');		//	Locate and process any scripts.
				error_log("index.php || Tag counts: scripts [{$script_nodes->length}]");
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
				error_log("index.php || Tag counts: reports [{$report_nodes->length}]");
				if ($report_nodes->length > 0) {
					$nothingNew = false;
					foreach ($report_nodes as $node) {				//	Iterate through report nodes.
						$input_type = '';
						$input_file = '';
						$output_type = '';
						$output_style = '';
						$output_file = '';
						$link_text = mb_strtolower(utf8_decode($node->attributes->getNamedItem('id')->textContent));
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
							$report = $rep_gen_mods[$output_type]->generate($rep_proc_mods[$input_type]->process($input_file));
							if (empty($report) || $report === false) {		//	We want to replace the report node with the ReportGenerator output, but if there is none, we need to remove the node entirely.
								if (empty($report)) error_log("index.php || Report generated (responded with an empty string) [{$input_type}({$input_file}) => {$output_type}::{$output_style}({$output_file})]");
								else error_log("index.php || Report generator encountered an error [{$input_type}({$input_file}) => {$output_type}::{$output_style}({$output_file})]");
								if ($output_style == 'test') {
									$new_node = $node->ownerDocument->createElementNS('http://www.w3c.org/1999/xhtml/', 'p', 'Report generation failed.  Test UNSAT.');
									$node->parentNode->replaceChild($new_node, $node);
									$node->ownerDocument->normalizeDocument();
								}
								else {
									$node->parentNode->removeChild($node);
								}
							}
							else {										//	The ReportGenerator returned a non-empty result - add it to the output.
		//						error_log("index.php || Report generated (responded with report presentation) [{$input_type}({$input_file}) => {$output_type}::{$output_style}({$output_file})]");
								file_put_contents($proj_dir.'/reports/download/'.$output_file, $report);
								switch ($output_style) {
								case 'interface':		//	Output directly to the Interface (use $link_text as caption)
									$new_node = $node->ownerDocument->createElementNS('http://www.w3c.org/1999/xhtml/', 'object', 'Report generation succeeded.  Download it ');
									$new_link_node = $new_node->ownerDocument->createElementNS('http://www.w3c.org/1999/xhtml/', 'a', 'here');
									$new_link_node->setAttribute('href', $siteroot.'/reports/download/'.$output_file);
									$new_node->appendChild($new_link_node);
									$new_text_node = $new_node->ownerDocument->createTextNode('.  (Tried to display the report in the browser and failed.  Check that your browser supports embedded PDFs.)');
									$new_node->appendChild($new_text_node);
									$new_node->setAttribute('data', $siteroot.'/reports/download/'.$output_file);
									$new_node->setAttribute('type', $output_type);
									$new_node->setAttribute('width', '600');
									$new_node->setAttribute('height', '825');
									break;
								case 'separate':		//	Output as download
									header('Location: '.$siteroot.'/reports/download/'.$output_file);
									$new_node = $node->ownerDocument->createElementNS('http://www.w3c.org/1999/xhtml/', 'p', 'Report generation succeeded.  Your download should begin within the next few seconds.');
									break;
								case 'link':			//	Output as link (use $link_text as link text)
									$new_node = $node->ownerDocument->createElementNS('http://www.w3c.org/1999/xhtml/', 'p', 'Report generation succeeded.  Download it ');
									$new_link_node = $new_node->ownerDocument->createElementNS('http://www.w3c.org/1999/xhtml/', 'a', 'here');
									$new_link_node->setAttribute('href', $siteroot.'/reports/download/'.$output_file);
									$new_node->appendChild($new_link_node);
									$new_text_node = $new_node->ownerDocument->createTextNode('.');
									$new_node->appendChild($new_text_node);
									break;
								case 'local':			//	Output as local file (not available in Interface)
									$new_node = $node->ownerDocument->createElementNS('http://www.w3c.org/1999/xhtml/', 'p', 'Report generation succeeded.  Report saved in "/reports/download/" as "'.$output_file.'".  Contact the site administrator for access.');
									break;
								case 'test':			//	Don't output the report anywhere; just test results.
									unlink($proj_dir.'/reports/download/'.$output_file);
									$new_node = $node->ownerDocument->createElementNS('http://www.w3c.org/1999/xhtml/', 'p', 'Report generation succeeded.  Test SAT.');
									break;
								default:				//	Unknown value
									error_log("index.php || Report output style [{$output_style}] not understood.");
									$new_node = $node->ownerDocument->createElementNS('http://www.w3c.org/1999/xhtml/', 'p', 'Report display failed (unsupported display method).  Have the site administrator check the error logs.  Report saved in "/reports/download/" as "'.$output_file.'".  Contact the site administrator for access.');
									break;
								}
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
				error_log("index.php || Tag counts: db value selectors [{$db_nodes->length}]");
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
				error_log("index.php || Tag counts: xuid blocks [{$xuid_nodes->length}]");
				if ($xuid_nodes->length > 0) {
					$nothingNew = false;
					foreach ($xuid_nodes as $node) {
						$xuid_type = mb_strtolower(utf8_decode($node->namespaceURI));
						error_log("index.php || XUID namespace URI [{$xuid_type} || {$node->nodeName}]");
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
				error_log("index.php || Tag counts: logout directives [{$logout_nodes->length}]");
				if ($logout_nodes->length > 0) {
					if (!$auth->unauth()) {
						error_log("index.php || Logout failed.  Check the code?");
					}
					header('Refresh: 5');
					$notice = $if_dom->createElementNS('http://www.w3c.org/1999/xhtml/', 'p', 'Successfully Logged Out');
					$notice->setAttribute('style', 'font-size: 24pt;');
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
			
			if (isset($xuid_mods[IDLX_NS_URI])) {
				$if_dom = $xuid_mods[IDLX_NS_URI]->translate($if_dom);
			}
			
			$temp_dom = '';
			foreach ($if_dom->childNodes as $node)
				$temp_dom .= $if_dom->saveXML($node);
			
			$tidy = new tidy;
			return $tidy->repairString($temp_dom, tidy_config(), 'UTF8');
		}
	}
	
	if (!function_exists('die_deny')) {
		function die_deny($reason) {
			header ("HTTP/1.1 403 Forbidden");
			@error_log("util-functions.php die_deny || Ending execution [{$_SERVER['REQUEST_URI']}?{$_SERVER['QUERY_STRING']}{$_SERVER['PATH_INFO']} || {$reason}]");
			die ($reason);
		}
	}
	
	if (!function_exists('need_ext')) {
		function need_ext ($ext_name) {
			if (!extension_loaded($ext_name)) {
				$prefix = (PHP_SHLIB_SUFFIX === 'dll') ? 'php_' : '';
				if (ini_get('enable_dl') != 1) return false;			//	Cannot dynamically load extensions.
				return dl($prefix . $ext_name . PHP_SHLIB_SUFFIX);		//	Try loading and return success/fail to the caller.
			}
			else {
				return true;											//	Already loaded.  Continue with your day.
			}
		}
	}
	
?>