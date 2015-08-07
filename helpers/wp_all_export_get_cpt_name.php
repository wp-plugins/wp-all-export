<?php

function wp_all_export_get_cpt_name($cpt = array(), $count = 2)
{
	$cptName = '';
	if ( ! empty($cpt)){
		if ( ! in_array('users', $cpt)){
			if (in_array('product_variation', $cpt)){
				$cptName = ucfirst( ( ! empty($cpt) ) ? str_replace("product_variation", __("Product Variations"), implode(", ", $cpt)) : 'record'); 
			}
			else
			{
				$post_type_details = get_post_type_object( $cpt[0] );				
				$cptName = ($count > 1) ? $post_type_details->labels->name : $post_type_details->labels->singular_name;
			}
		}					
		else
		{
			$cptName = ($count > 1) ? __('Users', 'wp_all_export_plugin') : __('User', 'wp_all_export_plugin');
		}
	}
	else{
		$cptName = ($count > 1) ? __('Records', 'wp_all_export_plugin') : __('Record', 'wp_all_export_plugin');
	}

	return $cptName;
}