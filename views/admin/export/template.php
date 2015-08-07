<h2 class="wpallexport-wp-notices"></h2>

<div class="wpallexport-wrapper">
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
</div>	

<div class="clear"></div>

<div class="wpallexport-content-section wpallexport-console" style="display: block; margin-bottom: 0;">
	<div class="ajax-console">
		<div class="founded_records">			
			<h3><span class="matches_count"><?php echo PMXE_Plugin::$session->found_posts; ?></span> <strong><?php echo wp_all_export_get_cpt_name($post['cpt']); ?></strong> will be exported</h3>
			<h4><?php _e("Choose data to include in the export file."); ?></h4>
		</div>
	</div>	
</div>

<table class="wpallexport-layout wpallexport-export-template">	
	<tr style="height: 500px;">
		<td class="left">
			
			<script type="text/javascript">
				__META_KEYS  = <?php echo json_encode(array_values($existing_meta_keys)); ?>;
				__ACF_KEYS   = <?php echo json_encode($existing_acf_meta_keys); ?>;
				__TAXES_KEYS = <?php echo json_encode($existing_taxonomies); ?>;
				__ATTR_KEYS  = <?php echo json_encode($existing_attributes); ?>;
				__WOO_KEYS   = <?php echo json_encode($woo_data); ?>;						
			</script>	

			<?php do_action('pmxe_template_header', $this->isWizard, $post); ?>

			<?php if ($this->errors->get_error_codes()): ?>
				<?php $this->error(); ?>
			<?php endif ?>			

			<form class="wpallexport-template <?php echo ! $this->isWizard ? 'edit' : '' ?> wpallexport-step-3" method="post">													
				<div class="wpallexport-collapsed wpallexport-section">
					<div class="wpallexport-content-section">
						<div class="wpallexport-collapsed-content">
							<fieldset class="optionsset" style="padding: 20px;">								
								<div id="columns_to_export">								
									<div class="columns-to-export-content" style="padding-right: 8px;">
										<ol id="columns" class="rad4">										
											<?php												
												$i = 0;
												$new_export = false;
												if ( ! empty($post['ids']) ){
													foreach ($post['ids'] as $ID => $value) {
														if (is_numeric($ID)){ if (empty($post['cc_name'][$ID])) continue;
															?>
															<li>
																<div class="custom_column" rel="<?php echo ($i + 1);?>">																
																	<label class="wpallexport-xml-element">&lt;<?php echo (!empty($post['cc_name'][$ID])) ? $post['cc_name'][$ID] : $post['cc_label'][$ID]; ?>&gt;</label>
																	<input type="hidden" name="ids[]" value="1"/>
																	<input type="hidden" name="cc_label[]" value="<?php echo (!empty($post['cc_label'][$ID])) ? $post['cc_label'][$ID] : ''; ?>"/>
																	<input type="hidden" name="cc_php[]" value="<?php echo (!empty($post['cc_php'][$ID])) ? $post['cc_php'][$ID] : 0; ?>"/>								
																	<input type="hidden" name="cc_code[]" value="<?php echo (!empty($post['cc_code'][$ID])) ? $post['cc_code'][$ID] : ''; ?>"/>								
																	<input type="hidden" name="cc_sql[]" value="<?php echo (!empty($post['cc_sql'][$ID])) ? $post['cc_sql'][$ID] : ''; ?>"/>								
																	<input type="hidden" name="cc_type[]" value="<?php echo $post['cc_type'][$ID]; ?>"/>
																	<input type="hidden" name="cc_options[]" value="<?php echo esc_html($post['cc_options'][$ID]); ?>"/>
																	<input type="hidden" name="cc_value[]" value="<?php echo esc_attr($post['cc_value'][$ID]); ?>"/>
																	<input type="hidden" name="cc_name[]" value="<?php echo (!empty($post['cc_name'][$ID])) ? $post['cc_name'][$ID] : str_replace(" ", "_", $post['cc_label'][$ID]); ?>"/>
																	<!--a href="javascript:void(0);" title="<?php _e('Delete field', 'wp_all_export_plugin'); ?>" class="icon-item remove-field"></a-->
																</div>
															</li>
															<?php
															$i++;
														}																					
													}
												}	
												elseif ($this->isWizard)
												{
													$new_export = true;																										
													if ( empty($post['cpt']) and ! XmlExportWooCommerceOrder::$is_active and ! XmlExportUser::$is_active ){
														$init_fields[] = 
															array(
																'label' => 'post_type',
																'name'  => 'post_type',
																'type'  => 'post_type'
															);
													}
													foreach ($init_fields as $k => $field) {
														?>
														<li>
															<div class="custom_column" rel="<?php echo ($i + 1);?>">															
																<label class="wpallexport-xml-element">&lt;<?php echo $field['name']; ?>&gt;</label>
																<input type="hidden" name="ids[]" value="1"/>
																<input type="hidden" name="cc_label[]" value="<?php echo $field['label']; ?>"/>
																<input type="hidden" name="cc_php[]" value=""/>																		
																<input type="hidden" name="cc_code[]" value=""/>
																<input type="hidden" name="cc_sql[]" value=""/>	
																<input type="hidden" name="cc_options[]" value="<?php echo (empty($field['options'])) ? '' : $field['options']; ?>"/>																										
																<input type="hidden" name="cc_type[]" value="<?php echo $field['type']; ?>"/>
																<input type="hidden" name="cc_value[]" value="<?php echo $field['label']; ?>"/>
																<input type="hidden" name="cc_name[]" value="<?php echo $field['name'];?>"/>
																<!--a href="javascript:void(0);" title="<?php _e('Delete field', 'wp_all_export_plugin'); ?>" class="icon-item remove-field"></a-->
															</div>
														</li>
														<?php
														$i++;
													}													

												}
												?>
												<li class="placeholder" <?php if ( ! empty($post['ids']) and count($post['ids']) > 1 or $new_export) echo 'style="display:none;"'; ?>><?php _e("Drop & drop data from \"Available Data\" on the right to include it in the export or click \"Add Field To Export\" below.", "wp_all_export_plugin"); ?></li>
												<?php																														
											?>
										</ol>
									</div>
								</div>							

								<div class="custom_column template">								
									<label class="wpallexport-xml-element"></label>
									<input type="hidden" name="ids[]" value="1"/>
									<input type="hidden" name="cc_label[]" value=""/>
									<input type="hidden" name="cc_php[]" value=""/>
									<input type="hidden" name="cc_code[]" value=""/>
									<input type="hidden" name="cc_sql[]" value=""/>
									<input type="hidden" name="cc_type[]" value=""/>
									<input type="hidden" name="cc_options[]" value=""/>								
									<input type="hidden" name="cc_value[]" value=""/>
									<input type="hidden" name="cc_name[]" value=""/>
									<!--a href="javascript:void(0);" title="<?php _e('Delete field', 'wp_all_export_plugin'); ?>" class="icon-item remove-field"></a-->
								</div>

								<!-- Warning Messages -->
								<?php if ( ! XmlExportWooCommerceOrder::$is_active ) : ?>
								<div class="wp-all-export-warning" <?php if ( empty($post['ids']) or count($post['ids']) > 1 ) echo 'style="display:none;"'; ?>>
									<p><?php _e("Warning: without an ID column, you won't be able to re-import this data using WP All Import.", "wp_all_export_plugin"); ?></p>
								</div>
								<?php endif; ?>

								<?php if ( XmlExportWooCommerce::$is_active ) : ?>
								<div class="wp-all-export-sku-warning" <?php echo 'style="display:none;"'; ?>>
									<p><?php _e("Warning: without _sku and product_type columns, you won't be able to re-import this data using WP All Import.", "wp_all_export_plugin"); ?></p>
								</div>								
								<?php endif; ?>

								<?php if ( empty($post['cpt']) and ! XmlExportWooCommerceOrder::$is_active and ! XmlExportUser::$is_active ) : ?>
								<div class="wp-all-export-advanced-query-warning" <?php echo 'style="display:none;"'; ?>>
									<p><?php _e("Warning: without post_type column, you won't be able to re-import this data using WP All Import.", "wp_all_export_plugin"); ?></p>
								</div>								
								<?php endif; ?>
								
								<!-- Add New Field Button -->
								<div class="input" style="float: left;">
									<input type="button" value="<?php _e('Add Field To Export', 'wp_all_export_plugin');?>" class="button-primary add_column">	
									<?php if ( XmlExportWooCommerceOrder::$is_active ): ?>
									<div class="input switcher-target-export_to_csv" style="margin-top: 10px;">
										<input type="hidden" name="order_item_per_row" value="0"/>
										<input type="checkbox" id="order_item_per_row" name="order_item_per_row" value="1" <?php if ($post['order_item_per_row']):?>checked="checked"<?php endif; ?>/>
										<label for="order_item_per_row"><?php _e("Display each product in its own row"); ?></label>
										<a href="#help" class="wpallexport-help" style="position: relative; top: 0px;" title="<?php _e('If an order contains multiple products, each product have its own row.', 'wp_all_export_plugin'); ?>">?</a>
									</div>
									<?php endif; ?>
								</div>	

								<!-- Preview a Row Button -->
								<div class="input" style="float: right;">																	
									<input type="button" value="<?php _e('Preview A Row', 'wp_all_export_plugin');?>" class="button-primary preview_a_row">	
								</div>						

								<!-- Export File Format -->
								<div class="input wp-all-export-format">																	
									<div class="input" style="float: left; padding-bottom: 5px;">
										<label><?php _e("Export File Format:", "wp_all_export_plugin"); ?></label>
									</div>
									<div class="clear"></div>
									<div class="input">
										<input type="radio" id="export_to_xml" class="switcher" name="export_to" value="xml" <?php echo 'csv' != $post['export_to'] ? 'checked="checked"': '' ?>/>
										<label for="export_to_xml"><?php _e('XML', 'wp_all_export_plugin' )?></label><br>
									</div>
									<div class="input">
										<input type="radio" id="export_to_csv" class="switcher" name="export_to" value="csv" <?php echo 'csv' == $post['export_to'] ? 'checked="checked"': '' ?>/>
										<label for="export_to_csv"><?php _e('CSV', 'wp_all_export_plugin' )?></label>
										<div class="switcher-target-export_to_csv wpallexport-csv-delimiter">
											<div class="input" style="padding: 5px;">
												<label style="width: 80px;"><?php _e('Delimiter:','wp_all_export_plugin');?></label> <input type="text" name="delimiter" value="<?php echo esc_attr($post['delimiter']) ?>" />
											</div>
										</div>
									</div>	
								</div>									
							</fieldset>															
						</div>
					</div>
				</div>
				
				<hr>

				<div class="wpallexport-submit-buttons">
					
					<div style="text-align:center; width:100%;">
						<?php wp_nonce_field('template', '_wpnonce_template'); ?>
						<input type="hidden" name="is_submitted" value="1" />									

						<?php if ( ! $this->isWizard ): ?>
							<a href="<?php echo remove_query_arg('id', remove_query_arg('action', $this->baseUrl)); ?>" class="back rad3" style="float:none;"><?php _e('Back to Manage Exports', 'wp_all_export_plugin') ?></a>
						<?php endif; ?>					
						<input type="submit" class="button button-primary button-hero wpallexport-large-button" value="<?php _e( ($this->isWizard) ? 'Continue to Step 3' : 'Update Template', 'wp_all_export_plugin') ?>" />
					</div>

				</div>

				<a href="http://soflyy.com/" target="_blank" class="wpallexport-created-by"><?php _e('Created by', 'wp_all_export_plugin'); ?> <span></span></a>

			</form>			
			
		</td>
		
		<td class="right template-sidebar">										

			<fieldset id="available_data" class="optionsset rad4">

				<div class="title"><?php _e('Available Data', 'wp_all_export_plugin'); ?></div>				

				<div class="wpallexport-xml resetable"> 

					<?php if ( XmlExportWooCommerce::$is_active ) : ?>

					<a href="javascript:void(0);" id="wp_all_export_auto_generate_data" class="rad4"><?php _e('Auto Generate', 'wp_all_export_plugin'); ?></a>

					<?php endif; ?>

					<ul>

						<?php echo $available_data_view; ?>

					</ul>		
										
				</div>					

			</fieldset>	
		</td>	
	</tr>
</table>	

<fieldset class="optionsset column rad4 wp-all-export-edit-column">
				
	<div class="title"><span class="wpallexport-add-row-title"><?php _e('Add Field To Export','wp_all_export_plugin');?></span><span class="wpallexport-edit-row-title"><?php _e('Edit Export Field','wp_all_export_plugin');?></span></div>

	<?php 
		
		if ( XmlExportEngine::$is_user_export )
		{
			include_once 'template/new_field_user.php';
		}
		else
		{
			if ( in_array('shop_order', XmlExportEngine::$post_types))
			{
				include_once 'template/new_field_shop_order.php';
			}
			else
			{
				include_once 'template/new_field_cpt.php';
			}
		}		

	?>

</fieldset>

<div class="wpallexport-overlay"></div>
