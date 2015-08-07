<?php

function pmxe_export_acf_field_csv($field_value, $exportOptions, $ID, $recordID, &$article, &$acfs, $element_name = '', $fieldSnipped = '', $group_id = '', $preview = false){	

	$put_to_csv = true;	

	$field_name = ($ID) ? $exportOptions['cc_label'][$ID] : $exportOptions['name'];			

	$field_options = ($ID) ? unserialize($exportOptions['cc_options'][$ID]) : $exportOptions;

	if ( ! empty($field_value) ) {		

		$field_value = maybe_unserialize($field_value);																					

		$implode_delimiter = ($exportOptions['delimiter'] == ',') ? '|' : ',';	

		// switch ACF field type
		switch ($field_options['type']) {			

			case 'date_time_picker':
			case 'date_picker':

				$field_value = date('Ymd', strtotime($field_value));

				break;
			
			case 'file':
			case 'image':

				if (is_numeric($field_value)){
					$field_value = wp_get_attachment_url($field_value);
				}
				elseif(is_array($field_value)){
					$field_value = $field_value['url'];
				}

				break;
														
			case 'gallery':											
				
				$v = array();
				foreach ($field_value as $key => $item) {
					$v[] = $item['url'];											
				}
				$field_value = implode($implode_delimiter, $v);

				break;																																										
			case 'location-field':

				$localion_parts = explode("|", $field_value);

				$acfs[] = $element_name . '_address';
				$acfs[] = $element_name . '_lat';
				$acfs[] = $element_name . '_lng';							

				if (!empty($localion_parts)){

					$article[$element_name . '_address'] = $localion_parts[0];					
					
					if (!empty($localion_parts[1])){
						$coordinates = explode(",", $localion_parts[1]);
						if (!empty($coordinates)){
							$article[$element_name . '_lat'] = $coordinates[0];							
							$article[$element_name . '_lng'] = $coordinates[1];							
						}
					}					
				}												

				$put_to_csv = false;

				break;
			case 'paypal_item':						

				$acfs[] = $element_name . '_item_name';
				$acfs[] = $element_name . '_item_description';
				$acfs[] = $element_name . '_price';

				if ( is_array($field_value) ){
					foreach ($field_value as $key => $value) {
						$article[$element_name . '_' . $key] = $value;												
					}
				}																	

				$put_to_csv = false;

				break;
			case 'google_map':

				$article[$element_name . '_address'] = $field_value['address'];
				$acfs[] = $element_name . '_address';
								
				$article[$element_name . '_lat'] = $field_value['lat'];
				$acfs[] = $element_name . '_lat';

				$article[$element_name . '_lng'] = $field_value['lng'];
				$acfs[] = $element_name . '_lng';							
									
				$put_to_csv = false;

				break;

			case 'acf_cf7':
			case 'gravity_forms_field':
				
				if ( ! empty($field_options['multiple']) )
					$field_value = implode($implode_delimiter, $field_value);

				break;											

			case 'page_link':

				if (is_array($field_value))
					$field_value = implode($implode_delimiter, $field_value);

				break;
			case 'post_object':													

				if ( ! empty($field_options['multiple'])){
					$v = array();
					foreach ($field_value as $key => $pid) {														

						if (is_numeric($pid)){
							$entry = get_post($pid);
							if ($entry)
							{
								$v[] = $entry->post_name;
							}
						}
						else{
							$v[] = $pid->post_name;
						}
					}
					$field_value = implode($implode_delimiter, $v);
				}
				else{							
					if (is_numeric($field_value)){
						$entry = get_post($field_value);
						if ($entry)
						{
							$field_value = $entry->post_name;
						}
					}
					else{
						$field_value = $field_value->post_name;
					}
				}

				break;				
			case 'relationship':

				$v = array();
				foreach ($field_value as $key => $pid) {
					$entry = get_post($pid);
					if ($entry)
					{
						$v[] = $entry->post_title;
					}
				}
				$field_value = implode($implode_delimiter, $v);

				break;																													
			case 'user':	

				if ( ! empty($field_options['multiple'])){
					$v = array();
					foreach ($field_value as $key => $user) {																												
						if (is_numeric($user)){
							$entry = get_user_by('ID', $user);
							if ($entry)
							{
								$v[] = $entry->user_email;
							}
						}				
						else{
							$v[] = $user['user_email'];
						}										
					}
					$field_value = implode($implode_delimiter, $v);
				}
				else{													
					if (is_numeric($field_value)){
						$entry = get_user_by('ID', $field_value);
						if ($entry)
						{
							$field_value = $entry->user_email;
						}
					}
					else{
						$field_value = $field_value['user_email'];
					}
				}	

				break;									
			case 'taxonomy':

				if ( ! in_array($field_options['field_type'], array('radio', 'select'))){
					$v = array();
					foreach ($field_value as $key => $tid) {
						$entry = get_term($tid , $field_options['taxonomy']);
						if ($entry and !is_wp_error($entry))
						{
							$v[] = $entry->name;
						}
					}
					$field_value = implode($implode_delimiter, $v);
				}
				else{
					$entry = get_term($field_value, $field_options['taxonomy']);
					if ($entry)
					{
						$field_value = $entry->name;
					}
				}

				break;
			case 'select':

				if ( ! empty($field_options['multiple'])){
					$field_value = implode($implode_delimiter, $field_value);
				}

				break;
			case 'checkbox':		
				
				$field_value = implode($implode_delimiter, $field_value);																							

				break;
			
			case 'repeater':																				

				if( have_rows($field_name, $recordID) ){

					$rowValues = array();
 										
				    while( have_rows($field_name, $recordID) ): the_row(); 					

				    	$row = acf_get_row();				    	

				    	foreach ($row['field']['sub_fields'] as $sub_field) {				    					    		

				    		// get
							$v = $row['value'][ $row['i'] ][ $sub_field['key'] ];//acf_format_value($row['value'][ $row['i'] ][ $sub_field['key'] ], $row['post_id'], $sub_field);

				    		$rowValues[$sub_field['name']][] = $v;				    		
							
							//pmxe_export_acf_field_csv($v, $sub_field, false, $recordID, $article, $acfs, str_replace('acf' . $group_id, '', $element_name) . '_' . $sub_field['name'], '');													

				    	}				    				    					       				    					    	
				        				        				        				        				    
				    endwhile;			

				    foreach ($rowValues as $key => $values) {
				    	$article[$element_name . '_' . $key] =  ($preview) ? trim(preg_replace('~[\r\n]+~', ' ', htmlspecialchars(implode($implode_delimiter, $values)))) : implode($implode_delimiter, $values);				    	
				    	if ( ! in_array($element_name . '_' . $key, $acfs)) $acfs[] = $element_name . '_' . $key;				    	
				    }					    
				 
				}							

				$put_to_csv = false;

				break;

			case 'flexible_content':																	

				// check if the flexible content field has rows of data
				if( have_rows($field_name) ):	

				 	// loop through the rows of data
				    while ( have_rows($field_name) ) : the_row();				

						$row = acf_get_row();						

						foreach ($row['field']['layouts'] as $layout) {	

							if ($layout['name'] == $row['value'][ $row['i'] ]['acf_fc_layout']){									

						    	foreach ($layout['sub_fields'] as $sub_field) {				    					    		
						    		
						    		if (isset($row['value'][ $row['i'] ][ $sub_field['key'] ])){
							    		// get
										$v = $row['value'][ $row['i'] ][ $sub_field['key'] ]; //acf_format_value($row['value'][ $row['i'] ][ $sub_field['key'] ], $row['post_id'], $sub_field);																				

										$article[$element_name . '_' . $layout['name'] . '_' . $row['i'] . '_' . $sub_field['name']] = $v;
				    					$acfs[] = $element_name . '_' . $layout['name'] . '_' . $row['i'] . '_' . $sub_field['name'];	

										//pmxe_export_acf_field_csv($v, $sub_field, false, $recordID, $article, $acfs, str_replace('acf' . $group_id, '', $element_name) . '_' . $row['value'][ $row['i'] ]['acf_fc_layout'] . '_' . $row['i'] . '_' . $sub_field['name'], '', '', true);													
									}

						    	}						    	
						    }						    					    	
					    }

				    endwhile;

				else :

				    // no layouts found

				endif;					

				$put_to_csv = false;
				
				break;											
			
			default:
				
				break;
		}
			
	}

	if ($put_to_csv){


		switch ($field_options['type']) {

			case 'repeater':

				global $acf;

				if ($acf->settings['version'] and version_compare($acf->settings['version'], '5.0.0') >= 0){		

					$acf_fields = get_posts(array('posts_per_page' => -1, 'post_type' => 'acf-field', 'post_parent' => $field_options['ID'], 'post_status' => 'publish', 'orderby' => 'menu_order', 'order' => 'ASC'));				

					if ( ! empty($acf_fields) ){

						foreach ($acf_fields as $field) {			

							$sub_name = $element_name . '_' . $field->post_excerpt;						

							if ( ! in_array($sub_name, $acfs)) $acfs[] = $sub_name;

						}

					}						

				}

			break;

			case 'google_map':
			case 'location-field':				

				$acfs[] = $element_name . '_address';
				$acfs[] = $element_name . '_lat';
				$acfs[] = $element_name . '_lng';							
				
				break;
			case 'paypal_item':						

				$acfs[] = $element_name . '_item_name';
				$acfs[] = $element_name . '_item_description';
				$acfs[] = $element_name . '_price';

				break;			
								
			default:

				$val = apply_filters('pmxe_acf_field', pmxe_filter( ( ! empty($field_value) ) ? maybe_serialize($field_value) : '', $fieldSnipped), $field_name, $recordID);			
				$article[$element_name] = ($preview) ? trim(preg_replace('~[\r\n]+~', ' ', htmlspecialchars($val))) : $val;
				$acfs[] = $element_name;	

			break;

		}						

	}
}

