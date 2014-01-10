<?php 
/**
 * Admin Statistics page
 * 
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 */
class PMXE_Admin_Settings extends PMXE_Controller_Admin {
	
	public function index() {
		
		$this->data['post'] = $post = $this->input->post(PMXI_Plugin::getInstance()->getOption());

		$this->render();

	}
	
	public function dismiss(){

		PMXE_Plugin::getInstance()->updateOption("dismiss", 1);

		exit('OK');
	}	

	public function download(){

		PMXE_download::csv(PMXE_Plugin::ROOT_DIR.'/logs/'.$_GET['file'].'.txt');
		
	}

}