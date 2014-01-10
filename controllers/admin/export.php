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
				
		$action = PMXE_Plugin::getInstance()->getAdminCurrentScreen()->action; 
		$this->_step_ready($action);						

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

		if (empty(PMXE_Plugin::$session->data['pmxe_export'])){
			wp_redirect_or_javascript($this->baseUrl); die();
		}

		if ('process' == $action) return true;
		
	}
	
	/**
	 * Step #1: Choose CPT
	 */
	public function index() {	

		$wp_uploads = wp_upload_dir();		
				
		$this->data['post'] = $post = $this->input->post(array(
			'cpt' => array(),		
			'export_to' => 'xml'
		));		

		// Delete history
		foreach (PMXE_Helper::safe_glob(PMXE_ROOT_DIR . '/history/*', PMXE_Helper::GLOB_RECURSE | PMXE_Helper::GLOB_PATH) as $filePath) {
			@file_exists($filePath) and @unlink($filePath);		
		}	

		if ($this->input->post('is_submitted')){  						

			pmxe_session_unset();																		

			PMXE_Plugin::$session['pmxe_export'] = array(
				'cpt' => ( ! is_array($post['cpt']) ) ? array($post['cpt']) : $post['cpt']				
			);

		} 	
		
		if ($this->input->post('is_submitted') and ! $this->errors->get_error_codes()) {			
				
			check_admin_referer('choose-cpt', '_wpnonce_choose-cpt');					 																		

			pmxe_session_commit(); 						
											
			wp_redirect(add_query_arg('action', 'element', $this->baseUrl)); die();												
		}
		
		$this->render();
	}
	
	/**
	 * Step #2: Choose data to export
	 */
	public function element()
	{

		$default = PMXE_Plugin::get_default_import_options();
		$DefaultOptions = (isset(PMXE_Plugin::$session->data['pmxe_export']) ? PMXE_Plugin::$session->data['pmxe_export'] : array()) + $default;
		$post = $this->input->post($DefaultOptions);

		$this->data['post'] =& $post;				

		$this->data['meta_keys'] = $keys = new PMXE_Model_List();
		$keys->setTable(PMXE_Plugin::getInstance()->getWPPrefix() . 'postmeta');
		$keys->setColumns('meta_id', 'meta_key')->getBy(NULL, "meta_id", NULL, NULL, "meta_key");
		
		if ($this->input->post('is_submitted')) {

			check_admin_referer('element', '_wpnonce_element');

			if ( ! $this->errors->get_error_codes()) {

				PMXE_Plugin::$session['pmxe_export']['export_to'] = preg_match('%\W(xml)$%i', trim($post['export_to'])) ? 'xml' : 'csv';				

				pmxe_session_commit();				
				
				wp_redirect(add_query_arg('action', 'process', $this->baseUrl)); die();
				
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
		$this->render();		
		
	}

	/**
	 * Step #4: Download
	 */ 
	public function download(){				

		$attch_url = PMXE_Plugin::$session->data['pmxe_export']['export_file'];

		$export_type = PMXE_Plugin::$session->data['pmxe_export']['export_to'];

		// clear import session
		pmxe_session_unset(); // clear session data (prevent from reimporting the same data on page refresh)

		switch ($export_type) {
			case 'xml':
				PMXE_download::xml($attch_url);		
				break;
			case 'csv':
				PMXE_download::csv($attch_url);		
				break;
			
			default:
				# code...
				break;
		}		

	}
	
}