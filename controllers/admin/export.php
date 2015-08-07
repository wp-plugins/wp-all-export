<?php 
/**
 * Import configuration wizard
 * 
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 */

class PMXE_Admin_Export extends PMXE_Controller_Admin {
	
	protected $isWizard = true; // indicates whether controller is in wizard mode (otherwize it called to be deligated an edit action)	

	protected function init() {		

		parent::init();							
		
		if ('PMXE_Admin_Manage' == PMXE_Plugin::getInstance()->getAdminCurrentScreen()->base) { // prereqisites are not checked when flow control is deligated
			$id = $this->input->get('id');
			$this->data['export'] = $export = new PMXE_Export_Record();			
			if ( ! $id or $export->getById($id)->isEmpty()) { // specified import is not found
				wp_redirect(add_query_arg('page', 'pmxe-admin-manage', admin_url('admin.php'))); die();
			}
			$this->isWizard = false;		
		
		} else {						
			$action = PMXE_Plugin::getInstance()->getAdminCurrentScreen()->action; 
			$this->_step_ready($action);			
		}

		// preserve id parameter as part of baseUrl
		$id = $this->input->get('id') and $this->baseUrl = add_query_arg('id', $id, $this->baseUrl);					

	}

	public function set($var, $val)
	{
		$this->{$var} = $val;
	}
	public function get($var)
	{
		return $this->{$var};
	} 

	/**
	 * Checks whether corresponding step of wizard is complete
	 * @param string $action
	 */
	protected function _step_ready($action) {		

		// step #1: xml selction - has no prerequisites
		if ('index' == $action) return true;
				
		if ('element' == $action) return true;

		$this->data['update_previous'] = $update_previous = new PMXE_Export_Record();

		$update_previous->getById(PMXE_Plugin::$session->update_previous);

		if ('options' == $action) return true;

		if ( ! PMXE_Plugin::$session->has_session()){
			wp_redirect_or_javascript($this->baseUrl); die();
		}

		if ('process' == $action) return true;
		
	}
	
	/**
	 * Step #1: Choose CPT
	 */
	public function index() {	

		PMXE_Plugin::$session->clean_session();	

		$wp_uploads = wp_upload_dir();		
				
		$this->data['post'] = $post = $this->input->post(array(
			'cpt' => '',		
			'export_to' => 'xml',
			'export_type' => 'specific',
			'wp_query' => '',
			'filter_rules_hierarhy' => '',
			'product_matching_mode' => 'strict',
			'wp_query_selector' => 'wp_query'
		));				

		// Delete history
		$history_files = PMXE_Helper::safe_glob(PMXE_ROOT_DIR . '/history/*', PMXE_Helper::GLOB_RECURSE | PMXE_Helper::GLOB_PATH);
		if ( ! empty($history_files) ){ 
			foreach ($history_files as $filePath) {
				@file_exists($filePath) and @unlink($filePath);		
			}
		}

		if ($this->input->post('is_submitted')){  									

			PMXE_Plugin::$session->set('export_type', $post['export_type']);
			PMXE_Plugin::$session->set('filter_rules_hierarhy', $post['filter_rules_hierarhy']);
			PMXE_Plugin::$session->set('product_matching_mode', $post['product_matching_mode']);
			PMXE_Plugin::$session->set('wp_query_selector', $post['wp_query_selector']);			

			$engine = new XmlExportEngine($post, $this->errors);	
			$engine->init_additional_data();													

		} 	
		
		if ($this->input->post('is_submitted') and ! $this->errors->get_error_codes()) {			
				
			check_admin_referer('choose-cpt', '_wpnonce_choose-cpt');					 																		

			PMXE_Plugin::$session->save_data(); 				
								
			wp_redirect(add_query_arg('action', 'template', $this->baseUrl)); die();												
			
		}
		
		$this->render();
	}		

	/**
	 * Step #2: Template
	 */ 
	public function template(){

		$default = PMXE_Plugin::get_default_import_options();

		if ($this->isWizard) {	
			$DefaultOptions = (PMXE_Plugin::$session->has_session() ? PMXE_Plugin::$session->get_clear_session_data() : array()) + $default;
			$post = $this->input->post($DefaultOptions);					
		}
		else{
			$DefaultOptions = $this->data['export']->options + $default;
			$post = $this->input->post($DefaultOptions);			
			$post['scheduled'] = $this->data['export']->scheduled;

			foreach ($post as $key => $value) {
				PMXE_Plugin::$session->set($key, $value);
			}
			
		}				

		PMXE_Plugin::$session->save_data(); 

		$this->data['post'] =& $post;								
		
		if ($this->input->post('is_submitted')) {

			check_admin_referer('template', '_wpnonce_template');

			if ( empty($post['cc_type'][0]) ){
				$this->errors->add('form-validation', __('You haven\'t selected any columns for export.', 'pmxe_plugin'));
			}	

			if ( 'csv' == $post['export_to'] and '' == $post['delimiter'] ){
				$this->errors->add('form-validation', __('CSV delimiter must be specified', 'pmxe_plugin'));
			}

			if ( ! $this->errors->get_error_codes()) {										

				if ($this->isWizard) {					
					foreach ($this->data['post'] as $key => $value) {
						PMXE_Plugin::$session->set($key, $value);	
					}
					PMXE_Plugin::$session->save_data(); 				
					wp_redirect(add_query_arg('action', 'options', $this->baseUrl)); die();	
				}							
				else {
					$this->data['export']->set(array( 'options' => $post, 'settings_update_on' => date('Y-m-d H:i:s')))->save();
					if ( ! empty($post['friendly_name']) ) {
						$this->data['export']->set( array( 'friendly_name' => $post['friendly_name'], 'scheduled' => (($post['is_scheduled']) ? $post['scheduled_period'] : '') ) )->save();	
					}
					wp_redirect(add_query_arg(array('page' => 'pmxe-admin-manage', 'pmxe_nt' => urlencode(__('Options updated', 'pmxi_plugin'))) + array_intersect_key($_GET, array_flip($this->baseUrlParamNames)), admin_url('admin.php'))); die();
				}
									
			}
			
		}		

		$engine = new XmlExportEngine($post, $this->errors);
		
		$engine->init_additional_data();		

		$this->data = array_merge($this->data, $engine->init_available_data());			

		$this->data['available_data_view'] = $engine->render();
				
		$this->render();		
	}

	public function options()
	{

		$default = PMXE_Plugin::get_default_import_options();

		if ($this->isWizard) {	
			$DefaultOptions = (PMXE_Plugin::$session->has_session() ? PMXE_Plugin::$session->get_clear_session_data() : array()) + $default;
			$post = $this->input->post($DefaultOptions);			
		}
		else{
			$DefaultOptions = $this->data['export']->options + $default;
			$post = $this->input->post($DefaultOptions);			
			$post['scheduled'] = $this->data['export']->scheduled;
			foreach ($post as $key => $value) {
				PMXE_Plugin::$session->set($key, $value);
			}						
			PMXE_Plugin::$session->save_data(); 

			$this->data['engine'] = new XmlExportEngine($post, $this->errors);	

			$this->data['engine']->init_available_data();	

		}

		$this->data['post'] =& $post;								
		
		if ($this->input->post('is_submitted')) {			

			check_admin_referer('options', '_wpnonce_options');
			
			if ($post['is_generate_templates'] and '' == $post['template_name']){	
				$friendly_name = '';
				$post_types = PMXE_Plugin::$session->get('cpt');
				if ( ! empty($post_types) )
				{					
					$post_type_details = get_post_type_object( array_shift($post_types) );					
					$friendly_name = $post_type_details->labels->name . ' Export - ' . date("Y F d H:i");
				}
				else
				{
					$friendly_name = 'WP_Query Export - ' . date("Y F d H:i");
				}			
				$post['template_name'] = $friendly_name;
			}			

			if ( ! $this->errors->get_error_codes()) {				
				if ($this->isWizard) {					
					foreach ($this->data['post'] as $key => $value) {
						PMXE_Plugin::$session->set($key, $value);	
					}
					PMXE_Plugin::$session->save_data(); 				
					wp_redirect(add_query_arg('action', 'process', $this->baseUrl)); die();	
				}							
				else {
					$this->data['export']->set(array( 'options' => $post, 'settings_update_on' => date('Y-m-d H:i:s')))->save();
					if ( ! empty($post['friendly_name']) ) {
						$this->data['export']->set( array( 'friendly_name' => $post['friendly_name'], 'scheduled' => (($post['is_scheduled']) ? $post['scheduled_period'] : '') ) )->save();	
					}
					wp_redirect(add_query_arg(array('page' => 'pmxe-admin-manage', 'pmxe_nt' => urlencode(__('Options updated', 'pmxi_plugin'))) + array_intersect_key($_GET, array_flip($this->baseUrlParamNames)), admin_url('admin.php'))); die();
				}
									
			}
			
		}

		$this->render();
	}

	/**
	 * Step #3: Export
	 */ 
	public function process()
	{										

		@set_time_limit(0);		

		$export = $this->data['update_previous'];		

		if ( ! PMXE_Plugin::is_ajax() ) {

			if ("" == PMXE_Plugin::$session->friendly_name){
				$friendly_name = '';
				$post_types  = PMXE_Plugin::$session->get('cpt');
				if ( ! empty($post_types) )
				{	
					if ( ! in_array('users', $post_types)){				
						$post_type_details = get_post_type_object( array_shift($post_types) );					
						$friendly_name = $post_type_details->labels->name . ' Export - ' . date("Y F d H:i");
					}
					else
					{
						$friendly_name = 'Users Export - ' . date("Y F d H:i");
					}
				}
				else
				{
					$friendly_name = 'WP_Query Export - ' . date("Y F d H:i");
				}

				PMXE_Plugin::$session->set('friendly_name', $friendly_name);
			} 

			PMXE_Plugin::$session->set('file', '');
			PMXE_Plugin::$session->save_data(); 		

			$export->set(
				array(
					'triggered' => 0,		
					'processing' => 0,
					'exported'  => 0,
					'executing' => 1,
					'canceled' => 0,
					'options'   => PMXE_Plugin::$session->get_clear_session_data(),
					'friendly_name' => PMXE_Plugin::$session->friendly_name,
					'scheduled' => (PMXE_Plugin::$session->is_scheduled) ? PMXE_Plugin::$session->scheduled_period : '',
					'registered_on' => date('Y-m-d H:i:s'),
					'last_activity' => date('Y-m-d H:i:s')
				)
			)->save();						

			$options = $export->options;

			if ( $options['is_generate_import'] and wp_all_export_is_compatible() ){				
				
				$import = new PMXI_Import_Record();

				if ( ! empty($options['import_id']) ) $import->getById($options['import_id']);

				if ($import->isEmpty()){

					$import->set(array(		
						'parent_import_id' => 99999,
						'xpath' => '/',			
						'type' => 'upload',																
						'options' => array('empty'),
						'root_element' => 'root',
						'path' => 'path',
						//'name' => '',
						'imported' => 0,
						'created' => 0,
						'updated' => 0,
						'skipped' => 0,
						'deleted' => 0,
						'iteration' => 1					
					))->save();					

					PMXE_Plugin::$session->set('import_id', $import->id);

					$options['import_id'] = $import->id;

					$export->set(array(
						'options' => $options
					))->save();
				}
				else{

					if ( $import->parent_import_id != 99999 ){

						$newImport = new PMXI_Import_Record();

						$newImport->set(array(		
							'parent_import_id' => 99999,
							'xpath' => '/',			
							'type' => 'upload',																
							'options' => array('empty'),
							'root_element' => 'root',
							'path' => 'path',
							//'name' => '',
							'imported' => 0,
							'created' => 0,
							'updated' => 0,
							'skipped' => 0,
							'deleted' => 0,
							'iteration' => 1					
						))->save();					

						PMXE_Plugin::$session->set('import_id', $newImport->id);

						$options['import_id'] = $newImport->id;

						$export->set(array(
							'options' => $options
						))->save();

					}

				}

			}

			PMXE_Plugin::$session->set('update_previous', $export->id);

			PMXE_Plugin::$session->save_data();

			do_action('pmxe_before_export', $export->id);

		}		

		$this->render();

	}	
	
}