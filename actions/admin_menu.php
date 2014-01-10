<?php
/**
 * Register plugin specific admin menu
 */

function pmxe_admin_menu() {
	global $menu, $submenu;
	
	if (current_user_can('manage_options')) { // admin management options
		
		add_menu_page(__('WP All Export', 'pmxe_plugin'), __('All Export', 'pmxe_plugin'), 'manage_options', 'pmxe-admin-home', array(PMXE_Plugin::getInstance(), 'adminDispatcher'), PMXE_Plugin::ROOT_URL . '/static/img/xmlicon.png');
		// workaround to rename 1st option to `Home`
		$submenu['pmxe-admin-home'] = array();		
		add_submenu_page('pmxe-admin-home', __('Export to XML', 'pmxe_plugin') . ' &lsaquo; ' . __('WP All Export', 'pmxe_plugin'), __('New Export', 'pmxe_plugin'), 'manage_options', 'pmxe-admin-export', array(PMXE_Plugin::getInstance(), 'adminDispatcher'));		
		add_submenu_page('pmxe-admin-home', __('Settings', 'pmxe_plugin') . ' &lsaquo; ' . __('WP All Export', 'pmxe_plugin'), __('Settings', 'pmxe_plugin'), 'manage_options', 'pmxe-admin-settings', array(PMXE_Plugin::getInstance(), 'adminDispatcher'));
		add_submenu_page('pmxe-admin-home', __('Support', 'pmxe_plugin') . ' &lsaquo; ' . __('WP All Export', 'pmxe_plugin'), __('Support', 'pmxe_plugin'), 'manage_options', 'pmxe-admin-help', array(PMXE_Plugin::getInstance(), 'adminDispatcher'));		
		
	}	
}

