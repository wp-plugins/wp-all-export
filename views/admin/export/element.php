<table class="layout pmxe_step_2">
	<tr>
		<td class="left">
			<h2><?php _e('Export to XML/CSV - Step 2: Choose Which data to export', 'pmxe_plugin') ?></h2>			

			<?php if ($this->errors->get_error_codes()): ?>
				<?php $this->error() ?>
			<?php endif; ?>

			<h3><?php printf('Selected custom post types for export: %s', implode(", ", $post['cpt'])); ?></h3>
			
			<fieldset class="optionsset">
				<legend><?php _e('Data to export', 'pmxe_plugin'); ?></legend>
		        <form method="post" class="choose-export-data no-enter-submit" enctype="multipart/form-data" autocomplete="off">
					<input type="hidden" name="is_submitted" value="1" />
					<div class="input">					
						
						<div class="input">
							<input type="hidden" name="is_export_title" value="0" />
							<input type="checkbox" id="is_export_title" name="is_export_title" value="1" <?php echo $post['is_export_title'] ? 'checked="checked"': '' ?> />
							<label for="is_export_title"><?php _e('Post Title', 'pmxe_plugin') ?></label>
						</div>
						<div class="input">
							<input type="hidden" name="is_export_content" value="0" />
							<input type="checkbox" id="is_export_content" name="is_export_content" value="1" <?php echo $post['is_export_content'] ? 'checked="checked"': '' ?> />
							<label for="is_export_content"><?php _e('Post Content', 'pmxe_plugin') ?></label>
						</div>

						<div class="input">			
							<input type="hidden" name="custom_fields_list" value="0" />			
							<input type="hidden" name="is_export_custom_fields" value="0" />
							<input type="checkbox" id="is_export_custom_fields" name="is_export_custom_fields" value="1" <?php echo $post['is_export_custom_fields'] ? 'checked="checked"': '' ?>  class="switcher"/>
							<label for="is_export_custom_fields"><?php _e('Custom Fields', 'pmxe_plugin') ?></label>							
							<div class="switcher-target-is_export_custom_fields" style="padding-left:17px;">
								<?php
								$existing_meta_keys = array();
								$hide_fields = array('_wp_page_template', '_edit_lock', '_edit_last', '_wp_trash_meta_status', '_wp_trash_meta_time');
								if (!empty($meta_keys) and $meta_keys->count()):
									foreach ($meta_keys as $meta_key) { if (in_array($meta_key['meta_key'], $hide_fields) or strpos($meta_key['meta_key'], '_wp') === 0) continue;
										$existing_meta_keys[] = $meta_key['meta_key'];												
									}
								endif;
								?>	
								<div class="input">
									<input type="radio" id="export_custom_fields_logic_only" name="export_custom_fields_logic" value="only" <?php echo ( "only" == $post['export_custom_fields_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
									<label for="export_custom_fields_logic_only"><?php _e('I only need a data from a few custom fields. Export these custom fields.', 'pmxe_plugin') ?></label>
									<div class="switcher-target-export_custom_fields_logic_only pmxe_choosen" style="padding-left:17px;">
											
										<span class="hidden choosen_values"><?php if (!empty($existing_meta_keys)) echo implode(',', $existing_meta_keys);?></span>
										<input class="choosen_input" value="<?php if (!empty($post['custom_fields_list']) and "only" == $post['export_custom_fields_logic']) echo implode(',', $post['custom_fields_list']); ?>" type="hidden" name="custom_fields_list"/>										
									</div>
								</div>
								<div class="input">
									<input type="radio" id="export_custom_fields_logic_full_update" name="export_custom_fields_logic" value="full_export" <?php echo ( "full_export" == $post['export_custom_fields_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
									<label for="export_custom_fields_logic_full_update"><?php _e('I want a gaint messy data file that\'s confusing and hard to read. Export ALL Cutom Fields.', 'pmxe_plugin') ?></label>								
								</div>																
							</div>
						</div>	

						<div class="input">
							<input type="hidden" name="taxonomies_list" value="0" />
							<input type="hidden" name="is_export_categories" value="0" />
							<input type="checkbox" id="is_export_categories" name="is_export_categories" value="1" class="switcher" <?php echo $post['is_export_categories'] ? 'checked="checked"': '' ?> />
							<label for="is_export_categories"><?php _e('Taxonomies (incl. Categories and Tags)', 'pmxe_plugin') ?></label>
							<div class="switcher-target-is_export_categories" style="padding-left:17px;">
								<div class="input" style="margin-bottom:3px;">								
									<input type="radio" id="export_categories_logic_full_update" name="export_categories_logic" value="full_export" <?php echo ( "full_export" == $post['export_categories_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
									<label for="export_categories_logic_full_update" style="position:relative; top:1px;"><?php _e('Export all taxonomies', 'pmxe_plugin') ?></label>
								</div>
								<?php
								global $wp_taxonomies;
								$existing_taxonomies = array();
								
								foreach ($wp_taxonomies as $key => $obj) {									
									$existing_taxonomies[] = $obj->name;									
								}

								?>								
								<div class="input" style="margin-bottom:3px;">								
									<input type="radio" id="export_categories_logic_only" name="export_categories_logic" value="only" <?php echo ( "only" == $post['export_categories_logic'] ) ? 'checked="checked"': '' ?> class="switcher"/>
									<label for="export_categories_logic_only" style="position:relative; top:1px;"><?php _e('Only export these taxonomies.', 'pmxe_plugin') ?></label>
									<div class="switcher-target-export_categories_logic_only pmxe_choosen" style="padding-left:17px;">										
										<span class="hidden choosen_values"><?php if (!empty($existing_taxonomies)) echo implode(',', $existing_taxonomies);?></span>
										<input class="choosen_input" value="<?php if (!empty($post['taxonomies_list']) and "only" == $post['export_categories_logic']) echo implode(',', $post['taxonomies_list']); ?>" type="hidden" name="taxonomies_list"/>										
									</div>
								</div>								
							</div>
						</div>

						<div class="input">
							<input type="hidden" name="is_export_images" value="0" />
							<input type="checkbox" id="is_export_images" name="is_export_images" value="1" <?php echo $post['is_export_images'] ? 'checked="checked"': '' ?> class="switcher" />
							<label for="is_export_images"><?php _e('Media Gallery', 'pmxe_plugin') ?></label>							
							<div class="switcher-target-is_export_images" style="padding-left:17px;">
								<div class="input" style="margin-bottom:3px;">								
									<input type="checkbox" id="export_images_logic_urls" name="export_images_logic[]" value="urls" <?php echo ( in_array("urls", $post['export_images_logic']) ) ? 'checked="checked"': '' ?> />
									<label for="export_images_logic_urls" style="position:relative; top:1px;"><?php _e('Image URLs', 'pmxe_plugin') ?></label>
								</div>
								<div class="input" style="margin-bottom:3px;">								
									<input type="checkbox" id="export_images_logic_meta_data" name="export_images_logic[]" value="meta_data" <?php echo ( in_array("meta_data", $post['export_images_logic']) ) ? 'checked="checked"': '' ?> />
									<label for="export_images_logic_meta_data" style="position:relative; top:1px;"><?php _e('Import Meta Data', 'pmxe_plugin') ?></label>
								</div>
							</div>
						</div>	

						<div class="input">
							<input type="hidden" name="is_export_other" value="0" />
							<input type="checkbox" id="is_export_other" name="is_export_other" value="1" <?php echo $post['is_export_other'] ? 'checked="checked"': '' ?> class="switcher" />
							<label for="is_export_other"><?php _e('Other Stuff', 'pmxe_plugin') ?></label>							
							<div class="switcher-target-is_export_other" style="padding-left:17px;">
								<div class="input">
									<input type="hidden" name="is_export_dates" value="0" />
									<input type="checkbox" id="is_export_dates" name="is_export_dates" value="1" <?php echo $post['is_export_dates'] ? 'checked="checked"': '' ?> />
									<label for="is_export_dates"><?php _e('Dates', 'pmxe_plugin') ?></label>
								</div>
								<div class="input">
									<input type="hidden" name="is_export_parent" value="0" />
									<input type="checkbox" id="is_export_parent" name="is_export_parent" value="1" <?php echo $post['is_export_parent'] ? 'checked="checked"': '' ?> />
									<label for="is_export_parent"><?php _e('Parent', 'pmxe_plugin') ?></label>
								</div>
								<div class="input">
									<input type="hidden" name="is_export_template" value="0" />
									<input type="checkbox" id="is_export_template" name="is_export_template" value="1" <?php echo $post['is_export_template'] ? 'checked="checked"': '' ?> />
									<label for="is_export_template"><?php _e('Template', 'pmxe_plugin') ?></label>
								</div>
								<div class="input">
									<input type="hidden" name="is_export_menu_order" value="0" />
									<input type="checkbox" id="is_export_menu_order" name="is_export_menu_order" value="1" <?php echo $post['is_export_menu_order'] ? 'checked="checked"': '' ?> />
									<label for="is_export_menu_order"><?php _e('Order', 'pmxe_plugin') ?></label>
								</div>
								<div class="input">
									<input type="hidden" name="is_export_status" value="0" />
									<input type="checkbox" id="is_export_status" name="is_export_status" value="1" <?php echo $post['is_export_status'] ? 'checked="checked"': '' ?> />
									<label for="is_export_status"><?php _e('Status', 'pmxe_plugin') ?></label>									
								</div>						
								<div class="input">
									<input type="hidden" name="is_export_format" value="0" />
									<input type="checkbox" id="is_export_format" name="is_export_format" value="1" <?php echo $post['is_export_format'] ? 'checked="checked"': '' ?> />
									<label for="is_export_format"><?php _e('Format', 'pmxe_plugin') ?></label>									
								</div>						
								<div class="input">
									<input type="hidden" name="is_export_author" value="0" />
									<input type="checkbox" id="is_export_author" name="is_export_author" value="1" <?php echo $post['is_export_author'] ? 'checked="checked"': '' ?> />
									<label for="is_export_author"><?php _e('Author', 'pmxe_plugin') ?></label>									
								</div>						
								<div class="input">
									<input type="hidden" name="is_export_slug" value="0" />
									<input type="checkbox" id="is_export_slug" name="is_export_slug" value="1" <?php echo $post['is_export_slug'] ? 'checked="checked"': '' ?> />
									<label for="is_export_slug"><?php _e('Slug', 'pmxe_plugin') ?></label>
								</div>						
								<div class="input">
									<input type="hidden" name="is_export_excerpt" value="0" />
									<input type="checkbox" id="is_export_excerpt" name="is_export_excerpt" value="1" <?php echo $post['is_export_excerpt'] ? 'checked="checked"': '' ?> />
									<label for="is_export_excerpt"><?php _e('Excerpt/Short Description', 'pmxe_plugin') ?></label>
								</div>																				
								<div class="input">
									<input type="hidden" name="is_export_attachments" value="0" />
									<input type="checkbox" id="is_export_attachments" name="is_export_attachments" value="1" <?php echo $post['is_export_attachments'] ? 'checked="checked"': '' ?> />
									<label for="is_export_attachments"><?php _e('Attachment URLs', 'pmxe_plugin') ?></label>
								</div>	

							</div>	
						</div>																											
					</div>	
					<p class="submit-buttons" style="text-align:right;">
						<?php wp_nonce_field('element', '_wpnonce_element') ?>
						<input type="hidden" name="is_submitted" value="1" />					
						
						<a href="<?php echo $this->baseUrl ?>" class="back"><?php _e('Back', 'pmxe_plugin') ?></a>					

						<input type="submit" class="button button-primary button-hero large_button" name="export_to" value="<?php _e('Export XML', 'pmxe_plugin') ?>" />		

						<input type="submit" class="button button-primary button-hero large_button" name="export_to" value="<?php _e('Export CSV', 'pmxe_plugin') ?>" />		

					</p>
				</form>
			</fieldset>
		</td>
		<td class="right">
			&nbsp;
		</td>
	</tr>
</table>
