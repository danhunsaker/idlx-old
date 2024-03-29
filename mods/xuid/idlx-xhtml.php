<?php
	include_once(dirname(__FILE__) . "/../../in-site-check.php");
	
	/** mods/xuid/xhtml.php
		Defines the XUID module for handling XHTML.
	*/
	
	class XUID_IDLX_XHTML implements XUIDModule {
		const out_ns = "http://www.w3c.org/1999/xhtml/";
		
		static function get_handler() {			//	Tells the core what namespace this module processes.
			return IDLX_NS_URI;
		}
		
		static function get_output_ns() {		//	Tells the core what namespace this module produces.
			return self::out_ns;
		}
		
		function translate(DOMNode $node) {		//	Takes the XUID $node and translates it to the desired output format.  Returns false on failure.
			$xp = new DOMXPath($node);
			$xp->registerNamespace('idlx', IDLX_NS_URI);
			$xp->registerNamespace('xhtml', self::out_ns);
			
			$out_root = $xp->evaluate("//xhtml:html");
			if ($out_root->length > 1) {
				error_log("XUID_IDLX_XHTML::translate || Too many XHTML root elements!  Returning full input instead of processing.");
				return $node;
			}
			elseif ($out_root->length == 0) {
				error_log("XUID_IDLX_XHTML::translate || No XHTML root elements!  Returning full input instead of processing.");
				return $node;
			}
			$out_root = $out_root->item(0);
			
			$idlx_root = $xp->evaluate("//idlx:iface");
			foreach ($idlx_root as $idlx) {
				$out_children = $xp->evaluate("//idlx:iface//*[namespace-uri()!=\"".IDLX_NS_URI."\" and namespace-uri()!=namespace-uri(parent::*)]", $idlx);
				foreach ($out_children as $out_node) {
//					error_log("XUID_IDLX_XHTML::translate || Moving [{$out_node->tagName}] to end of [{$out_root->tagName}]");
					if ($out_node->isSameNode($out_root)) continue;
					$out_node->parentNode->removeChild($out_node);
					$out_root->appendChild($out_node);
				}
				if ($node->documentElement->isSameNode($idlx)) continue;
				$out_node = $idlx->ownerDocument->createTextNode($idlx->textContent);
				$idlx->textContent = '';
				$idlx->parentNode->insertBefore($out_node, $idlx);
				$idlx->parentNode->removeChild($idlx);
			}

			if (!$node->documentElement->isSameNode($out_root)) {
//				error_log("XUID_IDLX_XHTML::translate || Moving output node to root position.  All done here!");
				$node->replaceChild($out_root, $node->documentElement);
			}
			
			$out_ofplace = $xp->evaluate("//xhtml:html/*[namespace-uri()='".self::out_ns."' and name()!='head' and name()!='body']");
			if ($out_ofplace->length > 0) {				//	Do we have XHTML tags that are in the wrong spot?  Let's move them inside the body tag.
				$body_tag = $xp->evaluate("//xhtml:html/xhtml:body");
				if ($body_tag->length != 1) {
					error_log ("XUID_IDLX_XHTML::translate || Cannot find body tag or too many body tags [{$body_tag->length}].  Cannot resolve out-of-place tags.");
				}
				else {
					$body_node = $body_tag->item(0);
					foreach ($out_ofplace as $oop_node) {
						error_log ("XUID_IDLX_XHTML::translate || Moving out-of-place node into the body node [{$oop_node->nodeName}]");
						$oop_node->parentNode->removeChild($oop_node);
						$body_node->appendChild($oop_node);
					}
				}
			}
			
			@$node->loadXML($node->saveXML());
			
			//	Now clean up the document so the default namespace is correct.
			$prefix = $node->lookupPrefix(self::out_ns);
			$node->documentElement->removeAttributeNS(self::out_ns, $prefix);
			foreach ($xp->evaluate("//*") as $desc_node) {
				$prefix = $desc_node->lookupPrefix(self::out_ns);
				$desc_node->removeAttributeNS(self::out_ns, $prefix);
			}

			//	And remove the IDLX namespace, if it's still hanging around.
			$prefix = $node->lookupPrefix(IDLX_NS_URI);
			$node->documentElement->removeAttributeNS(IDLX_NS_URI, $prefix);
			
			return $node;
		}
	}
	
	return 'XUID_IDLX_XHTML';
?>