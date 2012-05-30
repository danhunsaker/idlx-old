<?php
	include_once(dirname(__FILE__) . "/in-site-check.php");
	
	/** util-functions.php
		Provides basic utility functions for use throughout the IDLX Framework and any child projects.
		Designed with the capability to override anything defined here by simply defining it before this file is called.
	*/
	
	define('IDLX_NS_URI', 'http://idlx.sourceforge.net/schema/');
	
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
			$dom = new DOMDocument();
			$foot_node = $dom->createElementNS('http://www.w3c.org/1999/xhtml/', 'div');
			$foot_node->setAttribute('class', 'footer');
			$copy_node = $dom->createElementNS('http://www.w3c.org/1999/xhtml/', 'span', '&copy; 2011'.(date('Y') > 2011 ? '-'.date('Y') : '').' by ');
			$copy_node->setAttribute('id', 'copy');
			$team_node = $dom->createElementNS('http://www.w3c.org/1999/xhtml/', 'a', 'The IDLX Team');
			$team_node->setAttribute('href', 'http://idlx.sourceforge.net/');
			$copy_node_2 = $dom->createTextNode('.  Released under the GPL.');
			$copy_node->appendChild($team_node);
			$copy_node->appendChild($copy_node_2);
			$foot_node->appendChild($copy_node);
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
	
?>