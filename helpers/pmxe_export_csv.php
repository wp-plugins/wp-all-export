<?php
/**
*	Export CSV helper
*/
function pmxe_export_csv($exportQuery, $exportOptions, $preview = false, $is_cron = false, $file_path = false, $exported_by_cron = 0){

	ob_start();		

	// Prepare headers

	$headers = array();

	$stream = fopen("php://output", 'w');

	$max_attach_count = 0;
	$max_images_count = 0;						

	$cf = array();
	$woo = array();
	$woo_order = array();
	$acfs = array();
	$taxes = array();
	$attributes = array();
	$articles = array();

	$implode_delimiter = ($exportOptions['delimiter'] == ',') ? '|' : ',';			

	while ( $exportQuery->have_posts() ) :

		$attach_count = 0;
		$images_count = 0;								

		$exportQuery->the_post();

		$record = get_post( get_the_ID() );

		$article = array();

		$article['post_type'] = $record->post_type;
		$article['ID'] = apply_filters('pmxe_post_id', get_the_ID());
		$article['permalink'] = get_permalink();

		global $wpdb;
		$table_prefix = $wpdb->prefix;		

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

					if ( empty($exportOptions['cc_name'][$ID])  or empty($exportOptions['cc_type'][$ID]) ) continue;
					
					$element_name = ( ! empty($exportOptions['cc_name'][$ID]) ) ? $exportOptions['cc_name'][$ID] : 'untitled_' . $ID;
					$fieldSnipped = ( ! empty($exportOptions['cc_php'][$ID] ) and ! empty($exportOptions['cc_code'][$ID])) ? $exportOptions['cc_code'][$ID] : false;

					switch ($exportOptions['cc_type'][$ID]){
						case 'id':
							$article[$element_name] = apply_filters('pmxe_post_id', pmxe_filter(get_the_ID(), $fieldSnipped), get_the_ID());				
							break;
						case 'permalink':
							$article[$element_name] = apply_filters('pmxe_post_guid', pmxe_filter(get_permalink(), $fieldSnipped), get_the_ID());				
							break;
						case 'post_type':
							$pType = get_post_type();
							if ($pType == 'product_variation') $pType = 'product';
							$article[$element_name] = apply_filters('pmxe_post_type', pmxe_filter($pType, $fieldSnipped), get_the_ID());				
							break;					
						case 'title':								
							$article[$element_name] = apply_filters('pmxe_post_title', pmxe_filter($record->post_title, $fieldSnipped), get_the_ID());				
							break;						
						case 'content':
							$val = apply_filters('pmxe_post_content', pmxe_filter($record->post_content, $fieldSnipped), get_the_ID());
							$article[$element_name] = ($preview) ? trim(preg_replace('~[\r\n]+~', ' ', htmlspecialchars($val))) : $val;							
							break;
						case 'media':

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
								$img_urls = array();
								$img_titles = array();
								$img_captions = array();
								$img_alts = array();
								$img_descriptions = array();
								foreach ($attachment_ids as $key => $attach_id) {

									$attach = get_post($attach_id);									

									if ( $attach and ! is_wp_error($attach) and wp_attachment_is_image( $attach->ID ) ){																					

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

										$img_urls[] = apply_filters('pmxe_attachment_url', $val, get_the_ID(), $attach->ID);										
										$img_titles[] = apply_filters('pmxe_attachment_title', $attach->post_title, get_the_ID(), $attach->ID);
										$img_captions[] = apply_filters('pmxe_attachment_caption', $attach->post_excerpt, get_the_ID(), $attach->ID);
										$img_descriptions[] = apply_filters('pmxe_attachment_content', $attach->post_content, get_the_ID(), $attach->ID);										
										$img_alts[] = apply_filters('pmxe_attachment_alt', get_post_meta($record->ID, '_wp_attachment_image_alt', true), get_the_ID(), $attach->ID);										

										$images_count++;						
									}
								}
								if (! empty($img_urls))
									$article[$element_name . '_images'] = implode($implode_delimiter, $img_urls);
																
								if (!empty($img_titles)) $article[$element_name. '_titles'] = implode($implode_delimiter, $img_titles);
								if (!empty($img_captions)) $article[$element_name . '_captions'] = implode($implode_delimiter, $img_captions);
								if (!empty($img_alts)) $article[$element_name . '_alts'] = implode($implode_delimiter, $img_alts);
								if (!empty($img_descriptions)) $article[$element_name . '_descriptions'] = implode($implode_delimiter, $img_descriptions);								

								if ($max_images_count > $images_count) $max_images_count = $images_count;

							endif;

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

							$article[$element_name] = apply_filters('pmxe_post_date', pmxe_filter($post_date, $fieldSnipped), get_the_ID()); 

							break;

						case 'attachments':

							$attachment_imgs = get_posts( array(
								'post_type' => 'attachment',
								'posts_per_page' => -1,
								'post_parent' => $record->ID,
							) );

							if ( ! empty($attachment_imgs)):
								$attachment_urls = array();
								foreach ($attachment_imgs as $key => $attach) {
									if ( ! wp_attachment_is_image( $attach->ID ) ){
										$attachment_urls[] = apply_filters('pmxe_attachment_url', pmxe_filter(wp_get_attachment_url( $attach->ID ), $fieldSnipped), get_the_ID(), $attach->ID);
										$attach_count++;
									}
								}
								if (!empty($attachment_urls)) $article[$element_name . '_attachments'] = implode($implode_delimiter, $attachment_urls);

								if ($attach_count > $max_attach_count) $max_attach_count = $attach_count;

							endif;

							break;

						case 'parent':

							$article[$element_name] = apply_filters('pmxe_post_parent', pmxe_filter($record->post_parent, $fieldSnipped), get_the_ID()); 

							break;

						case 'comment_status':

							$article[$element_name] = apply_filters('pmxe_comment_status', pmxe_filter($record->comment_status, $fieldSnipped), get_the_ID()); 

							break;

						case 'ping_status':

							$article[$element_name] = apply_filters('pmxe_ping_status', pmxe_filter($record->ping_status, $fieldSnipped), get_the_ID()); 

							break;

						case 'template':

							$article[$element_name] = apply_filters('pmxe_post_template', pmxe_filter(get_post_meta($record->ID, '_wp_page_template', true), $fieldSnipped), get_the_ID());

							break;

						case 'order':

							$article[$element_name] = apply_filters('pmxe_menu_order', pmxe_filter($record->menu_order, $fieldSnipped), get_the_ID());

							break;

						case 'status':

							$article[$element_name] = apply_filters('pmxe_post_status', pmxe_filter($record->post_status, $fieldSnipped), get_the_ID()); 

							break;

						case 'format':

							$article[$element_name] = apply_filters('pmxe_post_format', pmxe_filter(get_post_format($record->ID), $fieldSnipped), get_the_ID());

							break;

						case 'author':

							$article[$element_name] = apply_filters('pmxe_post_author', pmxe_filter($record->post_author, $fieldSnipped), get_the_ID());

							break;

						case 'slug':

							$article[$element_name] = apply_filters('pmxe_post_slug', pmxe_filter($record->post_name, $fieldSnipped), get_the_ID()); 

							break;

						case 'excerpt':

							$val = apply_filters('pmxe_post_excerpt', pmxe_filter($record->post_excerpt, $fieldSnipped), get_the_ID());
							$article[$element_name] = ($preview) ? trim(preg_replace('~[\r\n]+~', ' ', htmlspecialchars($val))) : $val;								

							break;

						case 'cf':
							if ( ! empty($exportOptions['cc_value'][$ID]) ){																		
								$cur_meta_values = get_post_meta($record->ID, $exportOptions['cc_value'][$ID]);																				
								if (!empty($cur_meta_values) and is_array($cur_meta_values)){
									foreach ($cur_meta_values as $key => $cur_meta_value) {
										if (empty($article[$element_name])){
											$article[$element_name] = apply_filters('pmxe_custom_field', pmxe_filter(maybe_serialize($cur_meta_value), $fieldSnipped), $exportOptions['cc_value'][$ID], get_the_ID());										
											if (!in_array($element_name, $cf)) $cf[] = $element_name;
										}
										else{
											$article[$element_name] = apply_filters('pmxe_custom_field', pmxe_filter($article[$element_name] . $implode_delimiter . maybe_serialize($cur_meta_value), $fieldSnipped), $exportOptions['cc_value'][$ID], get_the_ID());
										}
									}
								}		

								if (empty($cur_meta_values)){
									if (empty($article[$element_name])){
										$article[$element_name] = apply_filters('pmxe_custom_field', pmxe_filter('', $fieldSnipped), $exportOptions['cc_value'][$ID], get_the_ID());										
										if (!in_array($element_name, $cf)) $cf[] = $element_name;
									}
									// else{
									// 	$article[$element_name . '_' . $key] = apply_filters('pmxe_custom_field', pmxe_filter('', $fieldSnipped), $exportOptions['cc_value'][$ID], get_the_ID());
									// 	if (!in_array($element_name . '_' . $key, $cf)) $cf[] = $element_name . '_' . $key;
									// }
								}																																																																
							}	
							break;

						case 'acf':							

							if ( ! empty($exportOptions['cc_label'][$ID]) and class_exists( 'acf' ) ){		

								global $acf;

								$field_options = unserialize($exportOptions['cc_options'][$ID]);

								switch ($field_options['type']) {
									case 'textarea':
									case 'oembed':
									case 'wysiwyg':
									case 'wp_wysiwyg':
									case 'date_time_picker':
									case 'date_picker':
										
										$field_value = get_field($exportOptions['cc_label'][$ID], $record->ID, false);

										break;
									
									default:
										
										$field_value = get_field($exportOptions['cc_label'][$ID], $record->ID);								

										break;
								}															

								pmxe_export_acf_field_csv($field_value, $exportOptions, $ID, $record->ID, $article, $acfs, $element_name, $fieldSnipped, $field_options['group_id'], $preview);
																																																																				
							}				
										
						break;

						case 'woo':
							
							XmlExportWooCommerce::getInstance()->export_csv($article, $woo, $record, $exportOptions, $ID); 	
							
							break;
						
						case 'woo_order':								

							XmlExportWooCommerceOrder::getInstance()->export_csv($article, $woo_order, $record, $exportOptions, $ID); 																							

							break;

						case 'attr':
							
							if ( ! empty($exportOptions['cc_value'][$ID])){											
								if ($record->post_parent == 0){									
									$txes_list = get_the_terms($record->ID, $exportOptions['cc_value'][$ID]);
									if ( ! is_wp_error($txes_list) and ! empty($txes_list)) {								
										$attr_new = array();										
										foreach ($txes_list as $t) {
											$attr_new[] = $t->name;
										}										
										$article[$element_name] = apply_filters('pmxe_woo_attribute', pmxe_filter(implode($implode_delimiter, $attr_new), $fieldSnipped), get_the_ID());										
									}									
									if ( ! in_array($element_name, $attributes)) $attributes[] = $element_name;
								}
								else{
									$attribute_pa = get_post_meta($record->ID, 'attribute_' . $exportOptions['cc_value'][$ID], true);						
									$article['attribute_' . $element_name] = $attribute_pa;																			
									if ( ! in_array('attribute_' . $element_name, $attributes)) $attributes[] = 'attribute_' . $element_name;
								}								
							}							
							break;
						
						case 'cats':
							if ( ! empty($exportOptions['cc_value'][$ID]) ){											
								$txes_list = get_the_terms($record->ID, $exportOptions['cc_value'][$ID]);
								if ( ! is_wp_error($txes_list) and ! empty($txes_list) ) {								
																	
									$txes_ids = array();										
									$hierarchy_groups = array();
																		
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
												$hierarchy_groups[] = implode('>', $hierarchy_group);
											}
											else{
												$hierarchy_groups[] = $t->name;
											}
										}
									}	

									if ( ! empty($hierarchy_groups) ){										
										$article[$element_name] = apply_filters('pmxe_post_taxonomy', pmxe_filter(implode($implode_delimiter, $hierarchy_groups), $fieldSnipped), get_the_ID());
									}																	
									
								}	
								
								if ( ! in_array($element_name, $taxes)) $taxes[] = $element_name;

								if ($exportOptions['cc_label'][$ID] == 'product_type' and get_post_type() == 'product_variation'){ 
									
									$article[$element_name] = 'variable';										
									$article['parent_sku'] = get_post_meta($record->post_parent, '_sku', true);																			
									
								}	
							}							
							break;
							
						case 'sql':							
							if ( ! empty($exportOptions['cc_sql'][$ID]) ) {																	
								$val = $wpdb->get_var( $wpdb->prepare( stripcslashes(str_replace("%%ID%%", "%d", $exportOptions['cc_sql'][$ID])), get_the_ID() ));
								if ( ! empty($exportOptions['cc_php'][$ID]) and !empty($exportOptions['cc_code'][$ID]) ){
									// if shortcode defined
									if (strpos($exportOptions['cc_code'][$ID], '[') === 0){									
										$val = do_shortcode(str_replace("%%VALUE%%", $val, $exportOptions['cc_code'][$ID]));
									}	
									else{
										$val = eval('return ' . stripcslashes(str_replace("%%VALUE%%", $val, $exportOptions['cc_code'][$ID])) . ';');
									}										
								}
								$article[$element_name] = apply_filters('pmxe_sql_field', $val, $element_name, get_the_ID());		
							}
							break;
						
						default:
							# code...
							break;
					}															
				}				
			}
		endif;		

		$articles[] = $article;
			
		$articles = apply_filters('wp_all_export_csv_rows', $articles, $exportOptions);	

		if ($preview) break;		

	endwhile;

	if ($exportOptions['ids']):		
	
		foreach ($exportOptions['ids'] as $ID => $value) {

			if (is_numeric($ID)){ 

				if (empty($exportOptions['cc_name'][$ID]) or empty($exportOptions['cc_type'][$ID])) continue;

				$element_name = ( ! empty($exportOptions['cc_name'][$ID]) ) ? $exportOptions['cc_name'][$ID] : 'untitled_' . $ID;

				switch ($exportOptions['cc_type'][$ID]) {
					case 'media':

						$headers[] = $element_name . '_images';						
						$headers[] = $element_name . '_titles';
						$headers[] = $element_name . '_captions';
						$headers[] = $element_name . '_descriptions';
						$headers[] = $element_name . '_alts';						
							
						break;

					case 'attachments':
						
						$headers[] = $element_name . '_attachments';
						
						break;
					case 'cats':					
						if ( ! empty($taxes) ){
							$tx = array_shift($taxes);
							$headers[] = $tx;
							if ($tx == 'product_type'){
								$headers[] = 'parent_sku';
							}	
						}												
						break;
					case 'attr':
						if ( ! empty($attributes) ){
							$headers[] = array_shift($attributes);							
							if (in_array('attribute_' . $element_name, $attributes)) {
								$headers[] = 'attribute_' . $element_name;
								foreach ($attributes as $akey => $avalue) {
									if ($avalue == 'attribute_' . $element_name){
										unset($attributes[$akey]);
										break;
									}
								}																
							}
						}						
						break;
					case 'cf':

						if ( ! empty($cf) ){
							$headers[] = array_shift($cf);									
						}
						
						break;
					case 'woo':

						XmlExportWooCommerce::getInstance()->get_element_header( $headers, $exportOptions, $ID );	
						
						break;

					case 'woo_order':

						XmlExportWooCommerceOrder::getInstance()->get_element_header( $headers, $exportOptions, $ID );												
						
						break;

					case 'acf':

						if ( ! empty($acfs) ){
							$headers[] = array_shift($acfs);							
						}
						
						break;
					
					default:
						$headers[] = $element_name;
						break;
				}							
				
			}			
		}

	endif;	

	if ($is_cron)
	{
		if ( ! $exported_by_cron ) fputcsv($stream, $headers, $exportOptions['delimiter']);	
	}
	else
	{
		if ($preview or empty(PMXE_Plugin::$session->file)) fputcsv($stream, $headers, $exportOptions['delimiter']);		
	}
	

	foreach ($articles as $article) {
		$line = array();
		foreach ($headers as $header) {
			$line[$header] = ( isset($article[$header]) ) ? $article[$header] : '';	
		}	
		fputcsv($stream, $line, $exportOptions['delimiter']);			

	}			

	if ($preview) return ob_get_clean();	

	if ($is_cron)
	{
		
		file_put_contents($file_path, ob_get_clean(), FILE_APPEND);

		return $file_path;

	}
	else
	{
		if ( empty(PMXE_Plugin::$session->file) ){			

			$is_secure_import = PMXE_Plugin::getInstance()->getOption('secure');

			$wp_uploads  = wp_upload_dir();

			$target = $is_secure_import ? wp_all_export_secure_file($wp_uploads['basedir'] . DIRECTORY_SEPARATOR . PMXE_Plugin::UPLOADS_DIRECTORY) : $wp_uploads['path'];				
				
			$export_file = $target . DIRECTORY_SEPARATOR . preg_replace('%- \d{4}.*%', '', $exportOptions['friendly_name']) . '- ' . date("Y F d H_i") . '.' . $exportOptions['export_to'];			
			
			file_put_contents($export_file, ob_get_clean());

			PMXE_Plugin::$session->set('file', $export_file);
			
			PMXE_Plugin::$session->save_data();

		}	
		else
		{
			file_put_contents(PMXE_Plugin::$session->file, ob_get_clean(), FILE_APPEND);
		}

		return true;
	}
	
}
