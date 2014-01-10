<?php 

function pmxe_plugins_loaded() {
	
	PMXE_Plugin::$session = PMXE_Session::get_instance();
	do_action( 'pmxe_session_start' );

	return PMXE_Plugin::$session->session_started();
}