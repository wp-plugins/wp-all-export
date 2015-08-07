<?php
/**
*	Export XML helper
*/
function pmxe_export_xml($exportQuery, $exportOptions, $preview = false, $is_cron = false, $file_path = false){
	
	$xmlWriter = new XMLWriter();
	$xmlWriter->openMemory();
	$xmlWriter->setIndent(true);
	$xmlWriter->setIndentString("\t");
	$xmlWriter->startDocument('1.0', $exportOptions['encoding']);
	$xmlWriter->startElement('data');	
	
	global $wpdb;

	while ( $exportQuery->have_posts() ) :				

		$exportQuery->the_post(); $record = get_post( get_the_ID() );		

		$xmlWriter->startElement('post');			

			if ($exportOptions['ids']):		

				if ( wp_all_export_is_compatible() and $exportOptions['is_generate_import'] and $exportOptions['import_id']){	
					$postRecord = new PMXI_Post_Record();
					$postRecord->clear();
					$postRecord->getBy(array(
						'post_id' => $record->ID,
						'import_id' => $exportOptions['import_id'],
					));

					if ($postRecord->isEmpty()){
						$postRecord->set(array(
							'post_id' => $record->ID,
							'import_id' => $exportOptions['import_id'],
							'unique_key' => $record->ID,
							'product_key' => get_post_meta($record->ID, '_sku', true)						
						))->save();
					}
					unset($postRecord);
				}								

				foreach ($exportOptions['ids'] as $ID => $value) {

					if (is_numeric($ID)){ 

						if (empty($exportOptions['cc_name'][$ID]) or empty($exportOptions['cc_type'][$ID])) continue;
						
						$element_name = ( ! empty($exportOptions['cc_name'][$ID]) ) ? str_replace(" ", "_", $exportOptions['cc_name'][$ID]) : 'untitled_' . $ID;				
						$fieldSnipped = ( ! empty($exportOptions['cc_php'][$ID]) and ! empty($exportOptions['cc_code'][$ID]) ) ? $exportOptions['cc_code'][$ID] : false;

						switch ($exportOptions['cc_type'][$ID]) {
							case 'id':
								$xmlWriter->writeElement($element_name, apply_filters('pmxe_post_id', pmxe_filter(get_the_ID(), $fieldSnipped), get_the_ID()));			
								break;
							case 'permalink':
								$xmlWriter->writeElement($element_name, apply_filters('pmxe_post_guid', pmxe_filter(get_permalink(), $fieldSnipped), get_the_ID()));
								break;
							case 'post_type':
								$pType = get_post_type();
								if ($pType == 'product_variation') $pType = 'product';
								$xmlWriter->writeElement($element_name, apply_filters('pmxe_post_type', pmxe_filter($pType, $fieldSnipped), get_the_ID()));											
								break;							
							case 'title':								
								$xmlWriter->startElement($element_name);
									$xmlWriter->writeCData(apply_filters('pmxe_post_title', pmxe_filter($record->post_title, $fieldSnipped) , get_the_ID()));
								$xmlWriter->endElement();								
								break;
							case 'content':
								$xmlWriter->startElement($element_name);
									$xmlWriter->writeCData(apply_filters('pmxe_post_content', pmxe_filter($record->post_content, $fieldSnipped), get_the_ID()));
								$xmlWriter->endElement();
								break;
							case 'media':
								$xmlWriter->startElement($element_name);
									
									$attachment_ids = array();

									$_featured_image = get_post_meta(get_the_ID(), '_thumbnail_id', true); 

									if ( ! empty($_featured_image)) $attachment_ids[] = $_featured_image;

									$_gallery = get_post_meta(get_the_ID(), '_product_image_gallery', true); 

									if (!empty($_gallery)){
										$gallery = explode(',', $_gallery);
										if (!empty($gallery) and is_array($gallery)){
											foreach ($gallery as $aid) {
												if (!in_array($aid, $attachment_ids)) $attachment_ids[] = $aid;
											}
										}
									}

									if ( ! empty($attachment_ids)):

										foreach ($attachment_ids as $attach_id) {

											$attach = get_post($attach_id);

											if ( $attach and ! is_wp_error($attach) and wp_attachment_is_image( $attach->ID ) ) {

												$xmlWriter->startElement('image');

													$val = wp_get_attachment_url( $attach->ID );														

													if (!empty($exportOptions['cc_options'][$ID])){
														switch ($exportOptions['cc_options'][$ID]) {															
															case 'filenames':
																$val = basename(wp_get_attachment_url( $attach->ID ));																
																break;
															case 'filepaths':
																$val = get_attached_file( $attach->ID );													
																break;
															
															default:
																# code...
																break;
														}
													}

													$xmlWriter->writeElement('file', apply_filters('pmxe_attachment_url', $val, get_the_ID(), $attach->ID));													
													$xmlWriter->writeElement('title', apply_filters('pmxe_attachment_title', $attach->post_title, get_the_ID(), $attach->ID));
													$xmlWriter->writeElement('caption', apply_filters('pmxe_attachment_caption', $attach->post_excerpt, get_the_ID(), $attach->ID));
													$xmlWriter->writeElement('description', apply_filters('pmxe_attachment_content', $attach->post_content, get_the_ID(), $attach->ID));													
													$xmlWriter->writeElement('alt', apply_filters('pmxe_attachment_alt', get_post_meta($record->ID, '_wp_attachment_image_alt', true), get_the_ID(), $attach->ID));

												$xmlWriter->endElement();
											}
										}

									endif;
								$xmlWriter->endElement();
								break;

							case 'date':
								if (!empty($exportOptions['cc_options'][$ID])){ 
									switch ($exportOptions['cc_options'][$ID]) {
										case 'unix':
											$post_date = get_post_time('U', true);
											break;										
										default:
											$post_date = date($exportOptions['cc_options'][$ID], get_post_time('U', true));
											break;
									}									
								}
								else{
									$post_date = date("Ymd", get_post_time('U', true));
								}
								$xmlWriter->writeElement($element_name, apply_filters('pmxe_post_date', pmxe_filter($post_date, $fieldSnipped), get_the_ID()));
								break;

							case 'attachments':
								$xmlWriter->startElement($element_name);
									$attachment_imgs = get_posts( array(
										'post_type' => 'attachment',
										'posts_per_page' => -1,
										'post_parent' => $record->ID,
									) );

									if ( ! empty($attachment_imgs)):

										foreach ($attachment_imgs as $attach) {
											if ( ! wp_attachment_is_image( $attach->ID ) ){
												$xmlWriter->startElement('attach');
													$xmlWriter->writeElement('url', apply_filters('pmxe_attachment_url', pmxe_filter(wp_get_attachment_url( $attach->ID ), $fieldSnipped), get_the_ID(), $attach->ID));														
												$xmlWriter->endElement();
											}
										}

									endif;
								$xmlWriter->endElement(); // end attachments
								break;

							case 'parent':
								$xmlWriter->writeElement($element_name, apply_filters('pmxe_post_parent', pmxe_filter($record->post_parent, $fieldSnipped), get_the_ID()));
								break;

							case 'comment_status':
								$xmlWriter->writeElement($element_name, apply_filters('pmxe_comment_status', pmxe_filter($record->comment_status, $fieldSnipped), get_the_ID()));
								break;

							case 'ping_status':
								$xmlWriter->writeElement($element_name, apply_filters('pmxe_ping_status', pmxe_filter($record->ping_status, $fieldSnipped), get_the_ID()));
								break;

							case 'template':
								$xmlWriter->writeElement($element_name, apply_filters('pmxe_post_template', pmxe_filter(get_post_meta($record->ID, '_wp_page_template', true), $fieldSnipped), get_the_ID()));
								break;

							case 'order':
								$xmlWriter->writeElement($element_name, apply_filters('pmxe_menu_order', pmxe_filter($record->menu_order, $fieldSnipped), get_the_ID()));
								break;

							case 'status':
								$xmlWriter->writeElement($element_name, apply_filters('pmxe_post_status', pmxe_filter($record->post_status, $fieldSnipped), get_the_ID()));
								break;

							case 'format':
								$xmlWriter->writeElement($element_name, apply_filters('pmxe_post_format', pmxe_filter(get_post_format($record->ID), $fieldSnipped), get_the_ID()));
								break;

							case 'author':
								$xmlWriter->writeElement($element_name, apply_filters('pmxe_post_author', pmxe_filter($record->post_author, $fieldSnipped), get_the_ID()));
								break;

							case 'slug':
								$xmlWriter->writeElement($element_name, apply_filters('pmxe_post_slug', pmxe_filter($record->post_name, $fieldSnipped), get_the_ID()));
								break;

							case 'excerpt':
								$xmlWriter->startElement($element_name);
									$xmlWriter->writeCData(apply_filters('pmxe_post_excerpt', pmxe_filter($record->post_excerpt, $fieldSnipped) , get_the_ID()));
								$xmlWriter->endElement();
								break;

							case 'cf':							
								if ( ! empty($exportOptions['cc_value'][$ID]) ){																		
									$cur_meta_values = get_post_meta($record->ID, $exportOptions['cc_value'][$ID]);																				
									if (!empty($cur_meta_values) and is_array($cur_meta_values)){
										foreach ($cur_meta_values as $key => $cur_meta_value) {
											$xmlWriter->startElement($element_name);
												$xmlWriter->writeCData(apply_filters('pmxe_custom_field', pmxe_filter(maybe_serialize($cur_meta_value), $fieldSnipped), $exportOptions['cc_value'][$ID], get_the_ID()));
											$xmlWriter->endElement();
										}
									}

									if (empty($cur_meta_values)){
										$xmlWriter->startElement($element_name);
											$xmlWriter->writeCData(apply_filters('pmxe_custom_field', pmxe_filter('', $fieldSnipped), $exportOptions['cc_value'][$ID], get_the_ID()));
										$xmlWriter->endElement();
									}																																																												
								}								
								break;
							case 'acf':							

								if ( ! empty($exportOptions['cc_label'][$ID]) and class_exists( 'acf' ) ){		

									global $acf;

									$field_value = get_field($exportOptions['cc_label'][$ID], $record->ID);

									$field_options = unserialize($exportOptions['cc_options'][$ID]);

									pmxe_export_acf_field_xml($field_value, $exportOptions, $ID, $record->ID, $xmlWriter, $element_name, $fieldSnipped, $field_options['group_id']);
																																																																					
								}				
												
								break;
							case 'woo':						
								
								XmlExportWooCommerce::getInstance()->export_xml($xmlWriter, $record, $exportOptions, $ID); 								
															
								break;
							case 'woo_order':								
								
								XmlExportWooCommerceOrder::getInstance()->export_xml($xmlWriter, $record, $exportOptions, $ID); 								

								break;
							case 'attr':								
								if ( ! empty($exportOptions['cc_value'][$ID])){
									if ($record->post_parent == 0){
										$is_variable_product = false;
										$product_terms = wp_get_post_terms( $record->ID, 'product_type' );
										if( ! empty($product_terms)){
							  				if( ! is_wp_error( $product_terms )){
							  					foreach($product_terms as $term){
							  						if ('variable' == $term->slug){
							  							$is_variable_product = true;
							  							break;
							  						}							  						
							  					}
							  				}
							  			}							  										  		
										$txes_list = get_the_terms($record->ID, $exportOptions['cc_value'][$ID]);
										if ( ! is_wp_error($txes_list)) {								
											$attr_new = array();										
											if (!empty($txes_list)):
												foreach ($txes_list as $t) {
													$attr_new[] = $t->name;												
												}		
												$xmlWriter->startElement($is_variable_product ? $element_name : 'attribute_' . $element_name);
													$xmlWriter->writeCData(apply_filters('pmxe_woo_attribute', pmxe_filter(implode('|', $attr_new), $fieldSnipped), get_the_ID()));
												$xmlWriter->endElement();		
											endif;									
										}
									}
									else{
										$attribute_pa = get_post_meta($record->ID, 'attribute_' . $exportOptions['cc_value'][$ID], true);
										if ( ! empty($attribute_pa)){
											$xmlWriter->startElement('attribute_' . $element_name);
												$xmlWriter->writeCData(apply_filters('woo_field', $attribute_pa));
											$xmlWriter->endElement();											
										}
									}
								}
								break;
							case 'cats':
								if ( ! empty($exportOptions['cc_value'][$ID]) ){																						
									$txes_list = get_the_terms($record->ID, $exportOptions['cc_value'][$ID]);
									if ( ! is_wp_error($txes_list)) {								
																				
										$txes_ids = array();										
										$hierarchy_groups = array();
																				
										if ( ! empty($txes_list) ):
											foreach ($txes_list as $t) {																						
												$txes_ids[] = $t->term_id;
											}

											foreach ($txes_list as $t) {
												if ( wp_all_export_check_children_assign($t->term_id, $exportOptions['cc_value'][$ID], $txes_ids) ){
													$ancestors = get_ancestors( $t->term_id, $exportOptions['cc_value'][$ID] );
													if (count($ancestors) > 0){
														$hierarchy_group = array();
														for ( $i = count($ancestors) - 1; $i >= 0; $i-- ) { 															
															$term = get_term_by('id', $ancestors[$i], $exportOptions['cc_value'][$ID]);
															if ($term){
																$hierarchy_group[] = $term->name;
															}
														}
														$hierarchy_group[]  = $t->name;
														$hierarchy_groups[] = implode(">", $hierarchy_group);
													}
													else{
														$hierarchy_groups[] = $t->name;
													}
												}
											}		

											if ( ! empty($hierarchy_groups) ){

												$xmlWriter->startElement($element_name);
													$xmlWriter->writeCData(apply_filters('pmxe_post_taxonomy', pmxe_filter(implode('|', $hierarchy_groups), $fieldSnipped), get_the_ID()));
												$xmlWriter->endElement();												
																							
											}
											
										endif;							

									}
									if ($exportOptions['cc_label'][$ID] == 'product_type' and get_post_type() == 'product_variation'){ 

										$xmlWriter->writeElement('parent_sku', get_post_meta($record->post_parent, '_sku', true));
										$xmlWriter->writeElement($element_name, 'variable');
										
									}
								}
								break;								
							
							case 'sql':

								if ( ! empty($exportOptions['cc_sql'][$ID]) ){									
									$val = $wpdb->get_var( $wpdb->prepare( stripcslashes(str_replace("%%ID%%", "%d", $exportOptions['cc_sql'][$ID])), get_the_ID() ));
									if ( ! empty($exportOptions['cc_php'][$ID]) and !empty($exportOptions['cc_code'][$ID])){
										// if shortcode defined
										if (strpos($exportOptions['cc_code'][$ID], '[') === 0){									
											$val = do_shortcode(str_replace("%%VALUE%%", $val, $exportOptions['cc_code'][$ID]));
										}	
										else{
											$val = eval('return ' . stripcslashes(str_replace("%%VALUE%%", $val, $exportOptions['cc_code'][$ID])) . ';');
										}										
									}
									$xmlWriter->startElement($element_name);
										$xmlWriter->writeCData(apply_filters('pmxe_sql_field', $val, $element_name, get_the_ID()));
									$xmlWriter->endElement();
								}
								break;							

							default:
								# code...
								break;
						}						
					}					
				}
			endif;		

		$xmlWriter->endElement(); // end post		
		
		if ($preview) break;

	endwhile;
	$xmlWriter->endElement(); // end data
	
	if ($preview) return wp_all_export_remove_colons($xmlWriter->flush(true));	

	if ($is_cron)
	{		
		
		$xml = $xmlWriter->flush(true);
		
		if (file_exists($file_path))
		{
			file_put_contents($file_path, wp_all_export_remove_colons(substr(substr($xml, 45), 0, -8)), FILE_APPEND);
		}		
		else
		{			
			file_put_contents($file_path, wp_all_export_remove_colons(substr($xml, 0, -8)));
		}
		
		return $file_path;	
		
	}
	else
	{

		if ( empty(PMXE_Plugin::$session->file) ){

			$is_secure_import = PMXE_Plugin::getInstance()->getOption('secure');

			$wp_uploads  = wp_upload_dir();

			$target = $is_secure_import ? wp_all_export_secure_file($wp_uploads['basedir'] . DIRECTORY_SEPARATOR . PMXE_Plugin::UPLOADS_DIRECTORY) : $wp_uploads['path'];
				
			$export_file = $target . DIRECTORY_SEPARATOR . preg_replace('%- \d{4}.*%', '', $exportOptions['friendly_name']) . '- ' . date("Y F d H_i") . '.' . $exportOptions['export_to'];			
			
			file_put_contents($export_file, wp_all_export_remove_colons(substr($xmlWriter->flush(true), 0, -8)));

			PMXE_Plugin::$session->set('file', $export_file);
			
			PMXE_Plugin::$session->save_data();

		}	
		else
		{
			file_put_contents(PMXE_Plugin::$session->file, wp_all_export_remove_colons(substr(substr($xmlWriter->flush(true), 45), 0, -8)), FILE_APPEND);
		}

		return true;

	}	

}