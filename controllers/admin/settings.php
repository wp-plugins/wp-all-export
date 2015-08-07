<?php 
/**
 * Admin Statistics page
 * 
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 */
class PMXE_Admin_Settings extends PMXE_Controller_Admin {
	
	public function index() {
		
		$this->data['post'] = $post = $this->input->post(PMXE_Plugin::getInstance()->getOption());

		if ($this->input->post('is_settings_submitted')) { // save settings form	

			check_admin_referer('edit-settings', '_wpnonce_edit-settings');		
			
			if ( ! $this->errors->get_error_codes()) { // no validation errors detected

				PMXE_Plugin::getInstance()->updateOption($post);
				
				wp_redirect(add_query_arg('pmxe_nt', urlencode(__('Settings saved', 'pmxe_plugin')), $this->baseUrl)); die();
			}
		}

		$this->render();

	}
	
	public function dismiss(){

		PMXE_Plugin::getInstance()->updateOption("dismiss", 1);

		exit('OK');
	}	

}