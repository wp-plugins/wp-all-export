<?php

function pmxe_wp_ajax_export_available_rules(){

	if ( ! check_ajax_referer( 'wp_all_export_secure', 'security', false )){
		exit( json_encode(array('html' => __('Security check', 'wp_all_export_plugin'))) );
	}

	if ( ! current_user_can('manage_options') ){
		exit( json_encode(array('html' => __('Security check', 'wp_all_export_plugin'))) );
	}

	ob_start();

	$input = new PMXE_Input();
	
	$post = $input->post('data', array());

	?>
	<select id="wp_all_export_rule">
		<option value=""><?php _e('Select Rule', 'wp_all_export_plugin'); ?></option>
		<?php
		if (strpos($post['selected'], 'tx_') === 0){
			?>										

				<!-- Taxonomies -->
				<option value="in">IN</option>
				<option value="not_in">NOT IN</option>

				<!-- Custom Fields -->
				<!--option value="between">BETWEEN</option-->
			
			<?php
		}
		else
		{
			?>
			<option value="equals"><?php _e('equals', 'wp_all_export_plugin'); ?></option>
			<option value="not_equals"><?php _e('not equals', 'wp_all_export_plugin'); ?></option>
			<option value="greater"><?php _e('greater than', 'wp_all_export_plugin');?></option>
			<option value="equals_or_greater"><?php _e('equals or greater than', 'wp_all_export_plugin'); ?></option>
			<option value="less"><?php _e('less than', 'wp_all_export_plugin'); ?></option>
			<option value="equals_or_less"><?php _e('equals or less than', 'wp_all_export_plugin'); ?></option>

			<option value="contains"><?php _e('contains', 'wp_all_export_plugin'); ?></option>
			<option value="not_contains"><?php _e('not contains', 'wp_all_export_plugin'); ?></option>
			<option value="is_empty"><?php _e('is empty', 'wp_all_export_plugin'); ?></option>
			<option value="is_not_empty"><?php _e('is not empty', 'wp_all_export_plugin'); ?></option>
			<?php
		}
	?>
	</select>
	<?php

	exit(json_encode(array('html' => ob_get_clean()))); die;

}