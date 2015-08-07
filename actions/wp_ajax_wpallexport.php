<?php
/**
*	AJAX action export processing
*/
function pmxe_wp_ajax_wpallexport(){

	if ( ! check_ajax_referer( 'wp_all_export_secure', 'security', false )){
		exit( __('Security check', 'wp_all_export_plugin') );
	}

	if ( ! current_user_can('manage_options') ){
		exit( __('Security check', 'wp_all_export_plugin') );
	}
	
	$wp_uploads = wp_upload_dir();	

	$export = new PMXE_Export_Record();

	$export->getById(PMXE_Plugin::$session->update_previous);	
	
	$exportOptions = (PMXE_Plugin::$session->has_session() ? PMXE_Plugin::$session->get_clear_session_data() : array()) + PMXE_Plugin::get_default_import_options();		

	wp_reset_postdata();	

	XmlExportEngine::$exportOptions  = $exportOptions;	
	XmlExportEngine::$is_user_export = $exportOptions['is_user_export'];

	$posts_per_page = $exportOptions['records_per_iteration'];	

	if ('advanced' == $exportOptions['export_type']) 
	{ 
		if (XmlExportEngine::$is_user_export)
		{
			exit( json_encode(array('html' => __('Upgrade to the professional edition of WP All Export to export users.', 'wp_all_export_plugin'))) );
		}
		else
		{
			$exportQuery = eval('return new WP_Query(array(' . $exportOptions['wp_query'] . ', \'orderby\' => \'ID\', \'order\' => \'ASC\', \'offset\' => ' . $export->exported . ', \'posts_per_page\' => ' . $posts_per_page . ' ));');
		}		
	}
	else
	{
		XmlExportEngine::$post_types = $exportOptions['cpt'];

		if ( ! in_array('users', $exportOptions['cpt']))
		{						
			$exportQuery = new WP_Query( array( 'post_type' => $exportOptions['cpt'], 'post_status' => 'any', 'orderby' => 'ID', 'order' => 'ASC', 'offset' => $export->exported, 'posts_per_page' => $posts_per_page ));						
		}
		else
		{			
			exit( json_encode(array('html' => __('Upgrade to the professional edition of WP All Export to export users.', 'wp_all_export_plugin'))) );
		}	
	}		

	XmlExportEngine::$exportQuery = $exportQuery;	

	$foundPosts = ( ! XmlExportEngine::$is_user_export ) ? $exportQuery->found_posts : $exportQuery->get_total();

	$postCount  = ( ! XmlExportEngine::$is_user_export ) ? $exportQuery->post_count : count($exportQuery->get_results());

	if ( ! $export->exported )
	{
		if ( ! empty($export->attch_id)){
			wp_delete_attachment($export->attch_id, true);
		}

		$is_secure_import = PMXE_Plugin::getInstance()->getOption('secure');

		if ( $is_secure_import and ! empty($exportOptions['filepath'])){

			wp_all_export_remove_source(wp_all_export_get_absolute_path($exportOptions['filepath']));

			$exportOptions['filepath'] = '';

		}
		
		PMXE_Plugin::$session->set('count', $foundPosts);		
		PMXE_Plugin::$session->save_data();
	}

	// if posts still exists then export them
	if ( $postCount )
	{
		switch ( $exportOptions['export_to'] ) {

			case 'xml':		
				
				pmxe_export_xml($exportQuery, $exportOptions);

				break;

			case 'csv':
				
				pmxe_export_csv($exportQuery, $exportOptions);

				break;								

			default:
				# code...
				break;
		}		

		wp_reset_postdata();	

	}

	if ($postCount){

		$export->set(array(
			'exported' => $export->exported + $postCount
		))->save();		
		
	}

	if ($posts_per_page != -1 and $postCount){		

		wp_send_json(array(
			'exported' => $export->exported,										
			'percentage' => ceil(($export->exported/$foundPosts) * 100),			
			'done' => false,
			'records_per_request' => $exportOptions['records_per_iteration']
		));	
	
	}
	else
	{

		wp_reset_postdata();		

		if ( file_exists(PMXE_Plugin::$session->file)){

			if ($exportOptions['export_to'] == 'xml') file_put_contents(PMXE_Plugin::$session->file, '</data>', FILE_APPEND);					

			$is_secure_import = PMXE_Plugin::getInstance()->getOption('secure');

			if ( ! $is_secure_import ){

				$wp_filetype = wp_check_filetype(basename(PMXE_Plugin::$session->file), null );
				$attachment_data = array(
				    'guid' => $wp_uploads['baseurl'] . '/' . _wp_relative_upload_path( PMXE_Plugin::$session->file ), 
				    'post_mime_type' => $wp_filetype['type'],
				    'post_title' => preg_replace('/\.[^.]+$/', '', basename(PMXE_Plugin::$session->file)),
				    'post_content' => '',
				    'post_status' => 'inherit'
				);		

				$attach_id = wp_insert_attachment( $attachment_data, PMXE_Plugin::$session->file );			
				if ( ! $export->isEmpty() ){
					$export->set(array(
						'attch_id' => $attach_id
					))->save();
				}

			}
			else{

				$exportOptions['filepath'] = wp_all_export_get_relative_path(PMXE_Plugin::$session->file);
				
				if ( ! $export->isEmpty() ){
					$export->set(array(
						'options' => $exportOptions
					))->save();	
				}

			}

			if ( wp_all_export_is_compatible() and ($exportOptions['is_generate_templates'] or $exportOptions['is_generate_import'])){
			
				$custom_type = (empty($exportOptions['cpt'])) ? 'post' : $exportOptions['cpt'][0];

				$templateOptions = array(
					'type' => ( ! empty($exportOptions['cpt']) and $exportOptions['cpt'][0] == 'page') ? 'page' : 'post',
					'wizard_type' => 'new',
					'deligate' => 'wpallexport',
					'custom_type' => (XmlExportEngine::$is_user_export) ? 'import_users' : $custom_type,
					'status' => 'xpath',
					'is_multiple_page_parent' => 'no',
					'unique_key' => '',
					'acf' => array(),
					'fields' => array(),
					'is_multiple_field_value' => array(),				
					'multiple_value' => array(),
					'fields_delimiter' => array(),				
					
					'update_all_data' => 'no',
					'is_update_status' => 0,
					'is_update_title'  => 0,
					'is_update_author' => 0,
					'is_update_slug' => 0,
					'is_update_content' => 0,
					'is_update_excerpt' => 0,
					'is_update_dates' => 0,
					'is_update_menu_order' => 0,
					'is_update_parent' => 0,
					'is_update_attachments' => 0,
					'is_update_acf' => 0,
					'update_acf_logic' => 'only',
					'acf_list' => '',					
					'is_update_product_type' => 0,
					'is_update_attributes' => 0,
					'update_attributes_logic' => 'only',
					'attributes_list' => '',
					'is_update_images' => 0,
					'is_update_custom_fields' => 0,
					'update_custom_fields_logic' => 'only',
					'custom_fields_list' => '',												
					'is_update_categories' => 0,
					'update_categories_logic' => 'only',
					'taxonomies_list' => '',
					'export_id' => $export->id
				);		

				if ( in_array('product', $exportOptions['cpt']) )
				{
					$templateOptions['_virtual'] = 1;
					$templateOptions['_downloadable'] = 1;
				}			

				if ( XmlExportEngine::$is_user_export )
				{					
					$templateOptions['is_update_first_name'] = 0;
					$templateOptions['is_update_last_name'] = 0;
					$templateOptions['is_update_role'] = 0;
					$templateOptions['is_update_nickname'] = 0;
					$templateOptions['is_update_description'] = 0;
					$templateOptions['is_update_login'] = 0;
					$templateOptions['is_update_password'] = 0;
					$templateOptions['is_update_nicename'] = 0;
					$templateOptions['is_update_email'] = 0;
					$templateOptions['is_update_registered'] = 0;
					$templateOptions['is_update_display_name'] = 0;
					$templateOptions['is_update_url'] = 0;
				}

				if ( 'xml' == $exportOptions['export_to'] ) 
				{						
					wp_all_export_prepare_template_xml($exportOptions, $templateOptions);															
				}
				else
				{						
					wp_all_export_prepare_template_csv($exportOptions, $templateOptions);																		
				}

				$options = $templateOptions + PMXI_Plugin::get_default_import_options();

				if ($exportOptions['is_generate_templates']){

					$template = new PMXI_Template_Record();

					$tpl_options = $options;

					if ( 'csv' == $exportOptions['export_to'] ) 
					{						
						$tpl_options['delimiter'] = $exportOptions['delimiter'];
					}
					
					$tpl_options['update_all_data'] = 'yes';
					$tpl_options['is_update_status'] = 1;
					$tpl_options['is_update_title']  = 1;
					$tpl_options['is_update_author'] = 1;
					$tpl_options['is_update_slug'] = 1;
					$tpl_options['is_update_content'] = 1;
					$tpl_options['is_update_excerpt'] = 1;
					$tpl_options['is_update_dates'] = 1;
					$tpl_options['is_update_menu_order'] = 1;
					$tpl_options['is_update_parent'] = 1;
					$tpl_options['is_update_attachments'] = 1;
					$tpl_options['is_update_acf'] = 1;
					$tpl_options['update_acf_logic'] = 'full_update';
					$tpl_options['acf_list'] = '';
					$tpl_options['is_update_product_type'] = 1;
					$tpl_options['is_update_attributes'] = 1;
					$tpl_options['update_attributes_logic'] = 'full_update';
					$tpl_options['attributes_list'] = '';
					$tpl_options['is_update_images'] = 1;
					$tpl_options['is_update_custom_fields'] = 1;
					$tpl_options['update_custom_fields_logic'] = 'full_update';
					$tpl_options['custom_fields_list'] = '';
					$tpl_options['is_update_categories'] = 1;
					$tpl_options['update_categories_logic'] = 'full_update';
					$tpl_options['taxonomies_list'] = '';					

					$tpl_data = array(						
						'name' => $exportOptions['template_name'],
						'is_keep_linebreaks' => 0,
						'is_leave_html' => 0,
						'fix_characters' => 0,
						'options' => $tpl_options,							
					);

					if ( ! empty($exportOptions['template_name'])) { // save template in database
						$template->getByName($exportOptions['template_name'])->set($tpl_data)->save();						
					}

				}

				// associate exported posts with new import
				if ($exportOptions['is_generate_import']){
										
					$import = new PMXI_Import_Record();

					$import->getById($exportOptions['import_id']);	

					if ( ! $import->isEmpty() and $import->parent_import_id == 99999 ){

						$xmlPath = PMXE_Plugin::$session->file;

						$root_element = '';

						$historyPath = PMXE_Plugin::$session->file;

						if ( 'csv' == $exportOptions['export_to'] ) 
						{
							$options['delimiter'] = $exportOptions['delimiter'];

							include_once( PMXI_Plugin::ROOT_DIR . '/libraries/XmlImportCsvParse.php' );	

							$path_info = pathinfo($xmlPath);

							$path_parts = explode(DIRECTORY_SEPARATOR, $path_info['dirname']);

							$security_folder = array_pop($path_parts);

							$target = $is_secure_import ? $wp_uploads['basedir'] . DIRECTORY_SEPARATOR . PMXE_Plugin::UPLOADS_DIRECTORY . DIRECTORY_SEPARATOR . $security_folder : $wp_uploads['path'];						

							$csv = new PMXI_CsvParser( array( 'filename' => $xmlPath, 'targetDir' => $target ) );						
							
							$historyPath = $csv->xml_path;

							$root_element = 'node';

						}
						else
						{
							$root_element = 'post';
						}

						$import->set(array(
							//'parent_import_id' => 99999,
							'xpath' => '/' . $root_element,
							'type' => 'upload',											
							'options' => $options,
							'root_element' => $root_element,
							'path' => $xmlPath,
							'name' => basename($xmlPath),
							'imported' => 0,
							'created' => 0,
							'updated' => 0,
							'skipped' => 0,
							'deleted' => 0,
							'iteration' => 1,		
							'count' => PMXE_Plugin::$session->count						
						))->save();				

						$history_file = new PMXI_File_Record();
						$history_file->set(array(
							'name' => $import->name,
							'import_id' => $import->id,
							'path' => $historyPath,
							'registered_on' => date('Y-m-d H:i:s')
						))->save();		

						$exportOptions['import_id']	= $import->id;					
						
						$export->set(array(
							'options' => $exportOptions
						))->save();		
					}							
				}			
			}
		}

		$export->set(array(
			'executing' => 0,
			'canceled'  => 0
		))->save();

		do_action('pmxe_after_export', $export->id);

		wp_send_json(array(
			'exported' => $export->exported,										
			'percentage' => 100,			
			'done' => true,
			'records_per_request' => $exportOptions['records_per_iteration'],
			'file' => PMXE_Plugin::$session->file
		));	

	}

}