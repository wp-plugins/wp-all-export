<?php 
/**
 * Manage Imports
 * 
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 */
class PMXE_Admin_Manage extends PMXE_Controller_Admin {
	
	public function init() {
		parent::init();
		
		if ('update' == PMXE_Plugin::getInstance()->getAdminCurrentScreen()->action) {
			$this->isInline = true;			
		}
	}
	
	/**
	 * Previous Imports list
	 */
	public function index() {
		
		$get = $this->input->get(array(
			's' => '',
			'order_by' => 'id',
			'order' => 'DESC',
			'pagenum' => 1,
			'perPage' => 25,
		));
		$get['pagenum'] = absint($get['pagenum']);
		extract($get);
		$this->data += $get;

		if ( ! in_array($order_by, array('registered_on', 'id', 'name'))){
			$order_by = 'registered_on';
		}

		if ( ! in_array($order, array('DESC', 'ASC'))){
			$order = 'DESC';
		}
		
		$list = new PMXE_Export_List();		
		$by = null;
		if ('' != $s) {
			$like = '%' . preg_replace('%\s+%', '%', preg_replace('/[%?]/', '\\\\$0', $s)) . '%';
			$by[] = array(array('name LIKE' => $like, 'type LIKE' => $like, 'path LIKE' => $like), 'OR');
		}
		
		$this->data['list'] = $list->setColumns(
				$list->getTable() . '.*'				
			)->getBy($by, "$order_by $order", $pagenum, $perPage, $list->getTable() . '.id');
			
		$this->data['page_links'] = paginate_links(array(
			'base' => add_query_arg('pagenum', '%#%', $this->baseUrl),
			'add_args' => array('page' => 'pmxe-admin-manage'),
			'format' => '',
			'prev_text' => __('&laquo;', 'PMXE_plugin'),
			'next_text' => __('&raquo;', 'PMXE_plugin'),
			'total' => ceil($list->total() / $perPage),
			'current' => $pagenum,
		));
		
		PMXE_Plugin::$session->clean_session();		

		$this->render();
	}	
	
	/**
	 * Edit Options
	 */
	public function options() {			
		
		// deligate operation to other controller
		$controller = new PMXE_Admin_Export();
		$controller->set('isTemplateEdit', true);
		$controller->options();
	}

	/**
	 * Edit Template
	 */
	public function template() {			
		
		// deligate operation to other controller
		$controller = new PMXE_Admin_Export();
		$controller->set('isTemplateEdit', true);
		$controller->template();
	}	

	/**
	 * Cron Scheduling
	 */
	public function scheduling() {
		$this->data['id'] = $id = $this->input->get('id');
		$this->data['cron_job_key'] = PMXE_Plugin::getInstance()->getOption('cron_job_key');
		$this->data['item'] = $item = new PMXE_Export_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_redirect($this->baseUrl); die();
		}

		$wp_uploads = wp_upload_dir();	

		$to_dirname = $wp_uploads['baseurl'] . DIRECTORY_SEPARATOR . PMXE_Plugin::CRON_DIRECTORY . DIRECTORY_SEPARATOR . md5($this->data['cron_job_key'] . $item->id);

		$this->data['file_path'] = $to_dirname . DIRECTORY_SEPARATOR . ( ( ! empty($item->friendly_name)) ? sanitize_file_name($item->friendly_name) : 'feed' ) . '.' . $item->options['export_to'];

		$this->render();
	}

	/**
	 * Download import templates
	 */
	public function templates() {
		$this->data['id'] = $id = $this->input->get('id');		
		$this->data['item'] = $item = new PMXE_Export_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_redirect($this->baseUrl); die();
		}

		$this->render();
	}

	/**
	 * Cancel import processing
	 */
	public function cancel(){
		
		$id = $this->input->get('id');
		
		PMXE_Plugin::$session->clean_session( $id );

		$item = new PMXE_Export_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_redirect($this->baseUrl); die();
		}
		$item->set(array(
			'triggered'   => 0,
			'processing'  => 0,
			'executing'   => 0,
			'canceled'    => 1,
			'canceled_on' => date('Y-m-d H:i:s')
		))->update();		

		wp_redirect(add_query_arg('pmxe_nt', urlencode(__('Export canceled', 'wp_all_import_plugin')), $this->baseUrl)); die();
	}
	
	/**
	 * Reexport
	 */
	public function update() {

		$id = $this->input->get('id');
		
		PMXE_Plugin::$session->clean_session($id);	

		$action_type = $this->input->get('type');

		$this->data['item'] = $item = new PMXE_Export_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_redirect($this->baseUrl); die();
		}							

		if ($this->input->post('is_confirmed')) {

			check_admin_referer('update-export', '_wpnonce_update-export');	

			$default = PMXE_Plugin::get_default_import_options();

			// compose data to look like result of wizard steps				
			$sesson_data = $item->options + array('update_previous' => $item->id ) + $default;
				
			foreach ($sesson_data as $key => $value) {
				PMXE_Plugin::$session->set($key, $value);
			}

			$this->data['engine'] = new XmlExportEngine($sesson_data, $this->errors);	
			$this->data['engine']->init_additional_data();
			$this->data['engine']->init_available_data();	

			PMXE_Plugin::$session->save_data();			

			if ( ! $this->errors->get_error_codes()) {		

				// deligate operation to other controller
				$controller = new PMXE_Admin_Export();
				$controller->data['update_previous'] = $item;
				$controller->process();
				return;
				
			}

		}
		
		$this->render();
	}
	
	/**
	 * Delete an import
	 */
	public function delete() {
		$id = $this->input->get('id');
		$this->data['item'] = $item = new PMXE_Export_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_redirect($this->baseUrl); die();
		}
		
		if ($this->input->post('is_confirmed')) {
			check_admin_referer('delete-export', '_wpnonce_delete-export');					
			$item->delete();
			wp_redirect(add_query_arg('pmxe_nt', urlencode(__('Export deleted', 'pmxe_plugin')), $this->baseUrl)); die();
		}
		
		$this->render();
	}
	
	/**
	 * Bulk actions
	 */
	public function bulk() {
		check_admin_referer('bulk-exports', '_wpnonce_bulk-exports');
		if ($this->input->post('doaction2')) {
			$this->data['action'] = $action = $this->input->post('bulk-action2');
		} else {
			$this->data['action'] = $action = $this->input->post('bulk-action');
		}		
		$this->data['ids'] = $ids = $this->input->post('items');
		$this->data['items'] = $items = new PMXE_Export_List();
		if (empty($action) or ! in_array($action, array('delete')) or empty($ids) or $items->getBy('id', $ids)->isEmpty()) {
			wp_redirect($this->baseUrl); die();
		}		
		if ($this->input->post('is_confirmed')) {			
			foreach($items->convertRecords() as $item) {
				
				if ($item->attch_id) wp_delete_attachment($item->attch_id, true);

				$item->delete();
			}			
			wp_redirect(add_query_arg('pmxe_nt', urlencode(sprintf(__('%d %s deleted', 'pmxe_plugin'), $items->count(), _n('export', 'exports', $items->count(), 'pmxe_plugin'))), $this->baseUrl)); die();
		}		
		$this->render();
	}

	public function get_template(){
		$nonce = (!empty($_REQUEST['_wpnonce'])) ? $_REQUEST['_wpnonce'] : '';
		if ( ! wp_verify_nonce( $nonce, '_wpnonce-download_template' ) ) {		    
		    die( __('Security check', 'wp_all_export_plugin') ); 
		} else {	
			
			$id = $this->input->get('id');

			$export = new PMXE_Export_Record();

			$filepath = '';

			$export_data = array();
			
			if ( ! $export->getById($id)->isEmpty()){
				$template = new PMXI_Template_Record();
				if ( ! empty($export->options['template_name'])) { 
					$template->getByName($export->options['template_name']);
					if ( ! $template->isEmpty() ){
						$export_data[] = $template->toArray(TRUE);
						$uploads = wp_upload_dir();
						$targetDir = $uploads['basedir'] . DIRECTORY_SEPARATOR . PMXI_Plugin::TEMP_DIRECTORY;
						$export_file_name = "templates_".uniqid().".txt";
						file_put_contents($targetDir . DIRECTORY_SEPARATOR . $export_file_name, json_encode($export_data));						
						PMXI_download::csv($targetDir . DIRECTORY_SEPARATOR . $export_file_name);						
					}					
				}
			}
		}
	}

	/*
	 * Download import log file
	 *
	 */
	public function get_file(){

		$nonce = (!empty($_REQUEST['_wpnonce'])) ? $_REQUEST['_wpnonce'] : '';
		if ( ! wp_verify_nonce( $nonce, '_wpnonce-download_feed' ) ) {		    
		    die( __('Security check', 'wp_all_export_plugin') ); 
		} else {

			$is_secure_import = PMXE_Plugin::getInstance()->getOption('secure');

			$id = $this->input->get('id');

			$export = new PMXE_Export_Record();

			$filepath = '';
			
			if ( ! $export->getById($id)->isEmpty()){


				if ( ! $is_secure_import){
					$filepath = wp_get_attachment_url($export->attch_id);
				}
				else{
					$filepath = wp_all_export_get_absolute_path($export->options['filepath']);
				}				

				if ( @file_exists($filepath) ){

					switch ($export['options']['export_to']) {
						case 'xml':
							PMXE_download::xml($filepath);		
							break;
						case 'csv':
							PMXE_download::csv($filepath);		
							break;
						
						default:
							wp_redirect(add_query_arg('pmxe_nt', urlencode(__('File format not supported', 'pmxe_plugin')), $this->baseUrl)); die();
							break;
					}

				}	
				else{
					wp_redirect(add_query_arg('pmxe_nt', urlencode(__('The exported file is missing and can\'t be downloaded. Please re-run your export to re-generate it.', 'pmxe_plugin')), $this->baseUrl)); die();
				}
			}
			else {
				wp_redirect(add_query_arg('pmxe_nt', urlencode(__('The exported file is missing and can\'t be downloaded. Please re-run your export to re-generate it.', 'pmxe_plugin')), $this->baseUrl)); die();
			}		
		}
	}

}