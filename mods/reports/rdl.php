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
		
		private function filter_to_where($exp, $op, $vals) {		//	Checks an RDL Filter to determine whether a given Field can be displayed in the final report.
			foreach ($vals as &$val) {
				if (!is_numeric($val)) {
					$val = '"'.$val.'"';
				}
			}
			switch ($op) {
			case 'Equal':
				return 'Where '.$exp.' = '.$vals[0];
			case 'Like':
				return 'Where '.$exp.' Like '.$vals[0];
			case 'NotEqual':
				return 'Where '.$exp.' != '.$vals[0];
			case 'GreaterThan':
				return 'Where '.$exp.' > '.$vals[0];
			case 'GreaterThanOrEqual':
				return 'Where '.$exp.' >= '.$vals[0];
			case 'LessThan':
				return 'Where '.$exp.' < '.$vals[0];
			case 'LessThanOrEqual':
				return 'Where '.$exp.' <= '.$vals[0];
			case 'TopN':
				return 'Order By '.$exp.' Asc Limit '.$vals[0];
			case 'BottomN':
				return 'Order By '.$exp.' Desc Limit '.$vals[0];
			case 'TopPercent':											//	Round up...
				return 'Order By '.$exp.' Asc Limit Ceiling(%TOTAL%*('.$vals[0].'/100))';
			case 'BottomPercent':										//	Round down...
				return 'Order By '.$exp.' Desc Limit Floor(%TOTAL%*('.$vals[0].'/100))';
			case 'In':
				return 'Where '.$exp.' In ('.implode(',', $vals).')';
			case 'Between':
				return 'Where '.$exp.' Between '.$vals[0].' And '.$vals[1];
			default:
				return '';
			}
			return '';
		}
		
		public function process($input_file) {					//	Processes the report defined in $input_file.  Returns the XML+XSL-FO version of the finalized report, or false on error.
			global $proj_dir, $db;
			$rdl_dom = DOMDocument::load($proj_dir.'/reports/'.$input_file);
			if ($rdl_dom === false) return false;
			$rdl_xp = new DOMXPath($rdl_dom);
			$rdl_xp->registerNamespace('rdl', $rdl_dom->documentElement->namespaceURI);		//	Register the namespace for RDL, in such a way that the exact version doesn't matter.
			$rdl_xp->registerNamespace('rd', $rdl_dom->lookupNamespaceURI('rd'));			//	Same with RD (reportdesigner)...
			$rdl_xp->registerNamespace('cl', $rdl_dom->lookupNamespaceURI('cl'));			//	...and CL (componentdefinition).  Just in case we need either of them.
			
			/*	Process all the DataSet elements */
			$data_set_list = array();	//	[idx][query/fields[idx][field/value]/filters[idx][expression/operator/values[idx]]]
			$datasets = $rdl_xp->evaluate('//rdl:DataSets/rdl:DataSet');
			foreach ($datasets as $dset) {
				if ($dset->hasAttribute('Name')) {		//	Strictly speaking, the Name attribute is required, but if it's missing, handle it gracefully...
					$dset_index = $dset->getAttribute('Name');
				}
				else {
					$dset_index = count($data_set_list);
				}
				$data_set_list[$dset_index] = array();		//	[query/fields[idx][field/value]/filters[idx][expression/operator/values[idx]]]
				$data_set_list[$dset_index]['query'] = $rdl_xp->evaluate('rdl:Query/rdl:CommandText', $dset)->item(0);
				$data_set_list[$dset_index]['fields'] = array();	//	[idx][field/value]
				$data_set_list[$dset_index]['filters'] = array();	//	[idx][expression/operator/values[idx]]
				$fields = $rdl_xp->evaluate('rdl:Fields/rdl:Field', $dset);
				foreach ($fields as $field) {		//	Process the Fields in this DataSet
					if ($field->hasAttribute('Name')) {		//	Name is a required attribute here also, but handle it gracefully as well...
						$field_index = $field->getAttribute('Name');
					}
					else {
						$field_index = count($data_set_list[$dset_index]);
					}
					$data_set_list[$dset_index]['fields'][$field_index] = array();	//	[field/value]
					$field_datafield = $field->getElementsByTagNameNS($field->namespaceURI, 'DataField');
					if ($field_datafield->length == 0) {		//	Generally speaking, DataField will be set, but some cases will have fixed Values instead.
						$data_set_list[$dset_index]['fields'][$field_index]['value'] = $field->getElementsByTagNameNS($field->namespaceURI, 'Value')->item(0)->textContent;
						$data_set_list[$dset_index]['fields'][$field_index]['field'] = false;
					}
					else {
						$data_set_list[$dset_index]['fields'][$field_index]['field'] = $field_datafield->item(0)->textContent;
						$data_set_list[$dset_index]['fields'][$field_index]['value'] = false;
					}
				}
				
				$filters = $rdl_xp->evaluate('rdl:Filters/rdl:Filter', $dset);
				foreach ($filters as $filter) {		//	Process the Filters in this DataSet
					$filter_index = count($data_set_list[$dset_index]['filters']);		//	Generate the array structure to hold this Filter, starting with an index...
					$data_set_list[$dset_index]['filters'][$filter_index] = array();	//	[expression/operator/values[idx]]
					$data_set_list[$dset_index]['filters'][$filter_index]['expression'] = strtr($filter->getElementsByTagNameNS($filter->namespaceURI, 'FilterExpression')->item(0)->textContent, array('=Fields!'=>'', '.Value'=>''));
					$data_set_list[$dset_index]['filters'][$filter_index]['operator'] = $filter->getElementsByTagNameNS($filter->namespaceURI, 'FilterOperator')->item(0)->textContent;
					$data_set_list[$dset_index]['filters'][$filter_index]['values'] = array();	//	[idx]
					$filter_values = $rdl_xp->evaluate('rdl:FilterValues/rdl:FilterValue', $filter);
					foreach ($filter_values as $filtval) {		//	Multiple Values can be specified - be sure to handle them all.
						$data_set_list[$dset_index]['filters'][$filter_index]['values'][] = $filtval->textContent;
					}
				}
			}
			
			/**	TODO:	Add support for RDL Parameters!*, ReportItems!*, Globals!*, User!*, DataSources!*, DataSets!* and Variables!*,
						as well as the Aggregate Functions.  SubReports would also be nice.	*/
			
			/*	Translate Field requests (=Fields!*.Value). */
			//	Grab all Tablix Rows and Columns with descendant Values starting with "=Fields!".
			$tablix_elements = $rdl_xp->evaluate('//*[self::rdl:TablixRow or self::rdl:TablixColumn][descendent::rdl:Value[starts-with(.,"=Fields!")]]');
			foreach ($tablix_elements as $tx_elmnt) {
				$dset_req = $rdl_xp->evaluate('ancestor::rdl:Tablix[1]/rdl:DataSetName', $tx_elmnt);		//	Locate the DataSet for this Tablix.
				if ($dset_req->length === 0) $dset_req = 0;
				else $dset_req = $dset_req->item(0)->textContent;
				if (isset($data_set_list[$dset_req])) {		//	Only proceed if the DataSet actually exists.
					$filter_where = '';
					foreach ($data_set_list[$dset_req]['filters'] as $filter) {				//	Convert RDL Filters to SQL Where clause
						$this_where = $this->filter_to_where($filter['expression'], $filter['operation'], $filter['values']);
						$filter_where .= empty($filter_where) ? ' '.$this_where : strtr($this_where, array('Where'=>' And'));
					}
					if (strpos($filter_where, '%TOTAL%') !== false) {						//	If there is a Filter which is based on the total number of rows, insert
																							//	that total into the Where clause before running the query
						if ($db->raw_sql(preg_replace('/select .* from/i', 'Select Count(*) From', $data_set_list[$dset_req]['query']))) {
							$total_val = $db->get_result_value(0, 0);		//	Pull the count.
						}
						else {		//	If we can't get the count for whatever reason, assume 0.
							$total_val = 0;
						}
						strtr($filter_where, array('%TOTAL%' => $total_val));		//	Replace '%TOTAL%' with the count.
					}
					if ($db->raw_sql($data_set_list[$dset_req]['query'].$filter_where) !== false) {		//	Run the query for the DataSet
						foreach ($data_set_list[$dset_req]['fields'] as $idx=>$field) {		//	Translate the returned values into result rows
							if ($field['field'] === false) {
								$out_rows[0][$idx] = $field['value'];		//	Use Value if DataField is not supplied.
							}
							else {
								for ($i = 0; $out_rows[$i][$idx] = $db->get_result_value($field['field'], $i); $i++);		//	Grab data values from DB for this Field.
							}
						}
						foreach ($out_rows[0] as $col_idx => $col) {		//	Distribute Values through the rest of the Rows.  (For non-DataField Fields.)
							foreach ($out_rows as $row_idx => $row) {
								if ($row_idx == 0) continue;
								if (!isset($row[$col_idx])) $row[$col_idx] = $col;
							}
						}
						foreach ($out_rows as $row) {		//	Create a new TablixRow or TablixColumn for each result Row.
							$tx_new = $tx_elmnt->cloneNode(true);
							$tablix_values = $rdl_xp->evaluate('descendent::rdl:Value[starts-with(.,"=Fields!")]', $tx_new);		//	Grab the Value nodes wanting replacements.
							foreach ($tablix_values as $tx_val) {		//	Replace each of the Values grabbed above.
								$tx_val->textContent = $row[strtr($tx_val->textContent, array('=Fields!'=>'', '.Value'=>''))];		//	We need to be sure we're using the requested Field...
							}
							$tx_new->ownerDocument->insertBefore($tx_new, $tx_elmnt);		//	Insert this row before the master.
						}
					}
				}
				$tx_elmnt->parentNode->removeChild($tx_elmnt);		//	Remove the master node.  We want to do this regardless of success above.
			}
			
			$rdl_dom->normalizeDocument();		//	Normalize the document to improve the results from XSLT processing.
			
			/*	Apply stylesheet */
			$xsl_dom = DOMDocument::load(getcwd().'/support/rdl-to-fo.xsl');
			if ($xsl_dom === false) return false;
			$xslt_proc = new XSLTProcessor();
			$xslt_proc->importStylesheet($xsl_dom);
			return $xslt_proc->transformToXML($rdl_dom);
		}
		
	}
	
	return 'ReportProc_RDL';
?>