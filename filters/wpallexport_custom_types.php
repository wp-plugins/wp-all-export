<?php

function pmxe_wpallexport_custom_types($custom_types){
	if ( ! empty($custom_types['product']) and class_exists('WooCommerce')) $custom_types['product']->labels->name = __('WooCommerce Products','wp_all_export_plugin');
	if ( ! empty($custom_types['product_variation'])) unset($custom_types['product_variation']);
	return $custom_types;
}