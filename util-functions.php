<?php
	include_once(dirname(__FILE__) . "/in-site-check.php");
	
	/** util-functions.php
		Provides basic utility functions for use throughout the IDLX Framework and any child projects.
		Designed with the capability to override anything defined here by simply defining it before this file is called.
	*/
	
	define('IDLX_NS_URI', 'https://localhost/idlx/idlx-schema/');
	
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
				error_log("util-functions.php - get_mods || Loading module [mods/{$mod_type}/{$fname}]");
				$class_name = include_once("mods/{$mod_type}/{$fname}");		//	Module files will have to return(class_name); at their end.
				if (class_exists($class_name)) {
					error_log("util-functions.php - get_mods || Class name [{$class_name}]");
					$output[$class_name::get_handler()] = new $class_name();
				}
				else {															//	But if they violate this rule, try to make things work anyway.
					error_log("util-functions.php - get_mods || Class name INVALID [{$class_name}] - add a return('Name_of_Class') to the end of [mods/{$mod_type}/{$fname}].");
					$class_name = "{$mod_type}_".substr($fname,0,-4);
					if (class_exists($class_name)) {
						error_log("util-functions.php - get_mods || Retrying with class name [{$class_name}]");
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
	
?>