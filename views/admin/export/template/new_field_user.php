<form>	
	<div class="wp-all-export-field-options">
		<div class="input" style="margin-bottom:10px;">
			<label for="column_value_default" style="padding:4px; display: block;"><?php _e('What field would you like to export?', 'wp_all_export_plugin' )?></label>
			<div class="clear"></div>
			<select class="wp-all-export-chosen-select" name="column_value_type" style="width:350px;">
				<optgroup label="Standard">
					<option value="id"><?php _e("ID", "wp_all_export_plugin"); ?></option>						
					<option value="user_login"><?php _e("Login", "wp_all_export_plugin"); ?></option>					
					<option value="user_email"><?php _e("Email", "wp_all_export_plugin"); ?></option>
					<option value="first_name"><?php _e("First Name", "wp_all_export_plugin"); ?></option>
					<option value="last_name"><?php _e("Last Name", "wp_all_export_plugin"); ?></option>					
					<option value="user_registered"><?php _e("Registered Date", "wp_all_export_plugin"); ?></option>
					<option value="user_nicename"><?php _e("Nicename", "wp_all_export_plugin"); ?></option>					
					<option value="user_url"><?php _e("URL", "wp_all_export_plugin"); ?></option>		
					<option value="display_name"><?php _e("Display Name", "wp_all_export_plugin"); ?></option>																																																																	
					<option value="nickname"><?php _e("Nickname", "wp_all_export_plugin"); ?></option>					
					<option value="description"><?php _e("Description", "wp_all_export_plugin"); ?></option>										
				</optgroup>
				<optgroup label="Advanced">
					<option value="user_pass"><?php _e("Password", "wp_all_export_plugin"); ?></option>					
					<option value="user_activation_key"><?php _e("Activation Key", "wp_all_export_plugin"); ?></option>
					<option value="user_status"><?php _e("Status", "wp_all_export_plugin"); ?></option>					
					<option value="wp_capabilities"><?php _e("Roles", "wp_all_export_plugin"); ?></option>
					<option value="cf"><?php _e("Custom Field / User Meta", "wp_all_export_plugin"); ?></option>
				</optgroup>
				<?php if ( class_exists( 'acf' )) : ?>
				<optgroup label="Integrations">					
					<?php if ( class_exists( 'acf' ) and ! empty($acf_groups) ) : ?>								
						<option value="acf"><?php _e("Advanced Custom Fields", "wp_all_export_plugin"); ?></option>
					<?php endif; ?>						
				</optgroup>
				<?php endif; ?>
				<optgroup label="Advanced">
					<option value="sql"><?php _e("SQL Query", "wp_all_export_plugin"); ?></option>					
				</optgroup>										
			</select>																													
		</div>			

		<input type="hidden" name="export_data_type" value="user"/>

		<div class="input cc_field cf_field_type" style="margin-left: 20px; margin-bottom: 10px;">
			<label style="padding:4px; display: block;"><?php _e('Field Name', 'wp_all_export_plugin'); ?></label>
			<input type="text" class="cf_direct_value autocomplete" value="" style="width:50%;"/>
		</div>
		<div class="input cc_field acf_field_type" style="margin-left: 20px; margin-bottom: 10px;">
			<label style="padding:4px; display: block;"><?php _e('Field Name', 'wp_all_export_plugin'); ?></label>
			<input type="text" class="acf_direct_value autocomplete" value="" style="width:50%;"/>
		</div>
		
		<div class="input">
			<label style="padding:4px; display: block;"><?php _e('What would you like to name the column/element in your exported file?','wp_all_export_plugin');?></label>
			<div class="clear"></div>
			<input type="text" class="column_name" value="" style="width:50%"/>
		</div>
		
		<a href="javascript:void(0);" class="wp-all-export-advanced-field-options"><span>+</span> <?php _e("Advanced", 'wp_all_export_plugin'); ?></a>

		<div class="wp-all-export-advanced-field-options-content">
			<div class="input cc_field sql_field_type">
				<a href="#help" rel="sql" class="help" style="display:none;" title="<?php _e('%%ID%% will be replaced with the ID of the post being exported, example: SELECT meta_value FROM wp_postmeta WHERE post_id=%%ID%% AND meta_key=\'your_meta_key\';', 'wp_all_export_plugin'); ?>">?</a>								
				<textarea style="width:100%;" rows="5" class="column_value"></textarea>										
			</div>			
			<div class="input cc_field date_field_type">
				<select class="date_field_export_data" style="width: 100%; height: 30px;">
					<option value="unix"><?php _e("UNIX timestamp - PHP time()", "wp_all_export_plugin");?></option>
					<option value="php"><?php _e("Natural Language PHP date()", "wp_all_export_plugin");?></option>									
				</select>
				<div class="input pmxe_date_format_wrapper">
					<label><?php _e("date() Format", "wp_all_export_plugin"); ?></label>
					<br>
					<input type="text" class="pmxe_date_format" value="" placeholder="Y-m-d H:i:s" style="width: 100%;"/>
				</div>
			</div>		
			<div class="input php_snipped" style="margin-top:10px;">
				<input type="checkbox" id="coperate_php" name="coperate_php" value="1" class="switcher" style="float: left; margin: 2px;"/>
				<label for="coperate_php"><?php _e("Export the value returned by a PHP function", "wp_all_export_plugin"); ?></label>								
				<a href="#help" class="wpallexport-help" title="<?php _e('The value of the field chosen for export will be passed to the PHP function.', 'wp_all_export_plugin'); ?>" style="top: 0;">?</a>								
				<div class="switcher-target-coperate_php" style="margin-top:5px;">
					<div class="wpallexport-free-edition-notice" style="margin: 15px 0;">
						<a class="upgrade_link" target="_blank" href="http://www.wpallimport.com/upgrade-to-wp-all-export-pro/?utm_source=wordpress.org&amp;utm_medium=custom-php&amp;utm_campaign=free+wp+all+export+plugin"><?php _e('Upgrade to the professional edition of WP All Export to enable custom PHP functions.','wp_all_export_plugin');?></a>
					</div>
					<?php echo "&lt;?php ";?>
					<input type="text" class="php_code" value="" style="width:50%;" placeholder='your_function_name'/> 
					<?php echo "(\$value); ?&gt;"; ?>
				</div>								
			</div>	
		</div>
	</div>																	
	<br>
	<div class="input wp-all-export-edit-column-buttons">			
		<input type="button" class="delete_action" value="<?php _e("Delete", "wp_all_export_plugin"); ?>" style="border: none;"/>									
		<input type="button" class="save_action" value="<?php _e("Done", "wp_all_export_plugin"); ?>" style="border: none;"/>	
		<input type="button" class="close_action" value="<?php _e("Close", "wp_all_export_plugin"); ?>" style="border: none;"/>
	</div>

</form>