<table class="wpallexport-layout wpallexport-step-1">
	<tr>
		<td class="left">
			<div class="wpallexport-wrapper">	
				<h2 class="wpallexport-wp-notices"></h2>
				<div class="wpallexport-header">
					<div class="wpallexport-logo"></div>
					<div class="wpallexport-title">
						<p><?php _e('WP All Export', 'wp_all_export_plugin'); ?></p>
						<h2><?php _e('Export to XML / CSV', 'wp_all_export_plugin'); ?></h2>					
					</div>
					<div class="wpallexport-links">
						<a href="http://www.wpallimport.com/support/" target="_blank"><?php _e('Support', 'wp_all_export_plugin'); ?></a> | <a href="http://www.wpallimport.com/documentation/" target="_blank"><?php _e('Documentation', 'wp_all_export_plugin'); ?></a>
					</div>
				</div>			

				<div class="clear"></div>				
				
				<?php if ($this->errors->get_error_codes()): ?>
					<?php $this->error() ?>
				<?php endif ?>						

		        <form method="post" class="wpallexport-choose-file" enctype="multipart/form-data" autocomplete="off">
		        	
		        	<div class="wpallexport-upload-resource-step-one rad4">						
						
						<div class="clear"></div>											
						
						<div class="wpallexport-import-types">
							<h2><?php _e('First, choose what to export.', 'wp_all_export_plugin'); ?></h2>							
							<a class="wpallexport-import-from wpallexport-url-type <?php echo 'specific' == $post['export_type'] ? 'selected' : '' ?>" rel="specific_type" href="javascript:void(0);">
								<span class="wpallexport-icon"></span>
								<span class="wpallexport-icon-label"><?php _e('Specific Post Type', 'wp_all_export_plugin'); ?></span>
							</a>
							<a class="wpallexport-import-from wpallexport-file-type <?php echo 'advanced' == $post['export_type'] ? 'selected' : '' ?>" rel="advanced_type" href="javascript:void(0);">
								<span class="wpallexport-icon"></span>
								<span class="wpallexport-icon-label"><?php _e('WP_Query Results', 'wp_all_export_plugin'); ?></span>
							</a>
						</div>
						
						<input type="hidden" value="<?php echo $post['export_type']; ?>" name="export_type"/>
						
						<div class="wpallexport-upload-type-container" rel="specific_type">			
							
							<div class="wpallexport-file-type-options">
								
								<?php
									$custom_types = get_post_types(array('_builtin' => true), 'objects') + get_post_types(array('_builtin' => false, 'show_ui' => true), 'objects'); 
									foreach ($custom_types as $key => $ct) {
										if (in_array($key, array('attachment', 'revision', 'nav_menu_item', 'import_users'))) unset($custom_types[$key]);										
									}									
									$custom_types = apply_filters( 'wpallexport_custom_types', $custom_types );
								?>								

								<select id="file_selector">
									<option value=""><?php _e('Choose a post type...', 'wp_all_export_plugin'); ?></option>									
					            	<?php if (count($custom_types)): ?>
										<?php foreach ($custom_types as $key => $ct):?>
											<?php 
												$image_src = 'dashicon-cpt';
												if (  in_array($key, array('post', 'page', 'product', 'import_users') ) )
													$image_src = 'dashicon-' . $key;										
											?>
											<option value="<?php echo $key;?>" data-imagesrc="dashicon <?php echo $image_src; ?>" <?php if ($key == $post['cpt']) echo 'selected="selected"'; ?>><?php echo $ct->labels->name; ?></option>
										<?php endforeach ?>
									<?php endif ?>	
									<option value="users" data-imagesrc="dashicon dashicon-import_users" <?php if ('users' == $post['cpt']) echo 'selected="selected"'; ?>><?php _e("Users", "wp_all_export_plugin"); ?></option>
								</select>
								
								<input type="hidden" name="cpt" value="<?php echo $post['cpt']; ?>"/>									
								
							</div>

							<div class="wpallexport-free-edition-notice wpallexport-user-export-notice">
								<a class="upgrade_link" target="_blank" href="http://www.wpallimport.com/upgrade-to-wp-all-export-pro/?utm_source=wordpress.org&amp;utm_medium=export-users&amp;utm_campaign=free+wp+all+export+plugin"><?php _e('Upgrade to the professional edition of WP All Export to export users.','wp_all_export_plugin');?></a>
							</div>

						</div>	

						<div class="wpallexport-upload-type-container" rel="advanced_type">						
							<div class="wpallexport-file-type-options">
								
								<select id="wp_query_selector">
									<option value="wp_query" <?php if ('wp_query' == $post['wp_query_selector']) echo 'selected="selected"'; ?>><?php _e('Post Type Query', 'wp_all_export_plugin'); ?></option>
									<option value="wp_user_query" <?php if ('wp_user_query' == $post['wp_query_selector']) echo 'selected="selected"'; ?>><?php _e('User Query', 'wp_all_export_plugin'); ?></option>
								</select>
								
								<div class="wpallexport-free-edition-notice wpallexport-user-export-notice" style="margin-bottom: 20px;">
									<a class="upgrade_link" target="_blank" href="http://www.wpallimport.com/upgrade-to-wp-all-export-pro/?utm_source=wordpress.org&amp;utm_medium=export-users&amp;utm_campaign=free+wp+all+export+plugin"><?php _e('Upgrade to the professional edition of WP All Export to export users.','wp_all_export_plugin');?></a>
								</div>

								<input type="hidden" name="wp_query_selector" value="<?php echo $post['wp_query_selector'];?>">
								<textarea class="wp_query" rows="10" cols="80" name="wp_query" placeholder="'post_type' => 'post', 'post_status' => array( 'pending', 'draft', 'future' )" style="width: 600px;"><?php echo esc_html($post['wp_query']); ?></textarea>						
								
							</div>							
							
						</div>																			

						<div class="wp_all_export_preloader"></div>							

						<input type="hidden" class="hierarhy-output" name="filter_rules_hierarhy" value="<?php echo esc_html($post['filter_rules_hierarhy']);?>"/>

					</div>									

					<div class="wpallexport-upload-resource-step-two rad4 wpallexport-collapsed closed">
							
					</div>

					<p class="wpallexport-submit-buttons" <?php if ('advanced' == $post['export_type']) echo 'style="display:block;"';?>>
						<input type="hidden" name="custom_type" value="">
						<input type="hidden" name="is_submitted" value="1" />
						<?php wp_nonce_field('choose-cpt', '_wpnonce_choose-cpt'); ?>					
						<input type="submit" class="button button-primary button-hero wpallexport-large-button" value="<?php _e('Continue to Step 2', 'wp_all_export_plugin') ?>" id="advanced_upload"/>
					</p>
					
					<table><tr><td class="wpallexport-note"></td></tr></table>
				</form>
				
				<a href="http://soflyy.com/" target="_blank" class="wpallexport-created-by"><?php _e('Created by', 'wp_all_export_plugin'); ?> <span></span></a>
				
			</div>
		</td>		
	</tr>
</table>
