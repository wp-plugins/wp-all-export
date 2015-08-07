<?php

function pmxe_wp_ajax_export_filtering(){

	if ( ! check_ajax_referer( 'wp_all_export_secure', 'security', false )){
		exit( json_encode(array('html' => __('Security check', 'wp_all_export_plugin'))) );
	}

	if ( ! current_user_can('manage_options') ){
		exit( json_encode(array('html' => __('Security check', 'wp_all_export_plugin'))) );
	}

	ob_start();

	$errors = new WP_Error();	

	$input = new PMXE_Input();
	
	$post = $input->post('data', array());

	if ( ! empty($post['cpt'])):

		$engine = new XmlExportEngine($post, $errors);	

		$engine->init_available_data();	

		?>
		<div class="wpallexport-content-section">
			<div class="wpallexport-collapsed-header">
				<h3><?php _e('Add Filtering Options', 'wp_all_export_plugin'); ?></h3>	
			</div>		
			<div class="wpallexport-collapsed-content">			
				<div class="wpallexport-free-edition-notice" style="padding: 20px; margin-bottom: 10px;">
					<a class="upgrade_link" target="_blank" href="http://www.wpallimport.com/upgrade-to-wp-all-export-pro/?utm_source=wordpress.org&amp;utm_medium=filter-rules&amp;utm_campaign=free+wp+all+export+plugin"><?php _e(' Filtering Options Upgrade to the professional edition of WP All Export to add filtering rules.','wp_all_export_plugin');?></a>
				</div>
				<div class="wp_all_export_rule_inputs">
					<table>
						<tr>
							<th><?php _e('Element', 'wp_all_export_plugin'); ?></th>
							<th><?php _e('Rule', 'wp_all_export_plugin'); ?></th>
							<th><?php _e('Value', 'wp_all_export_plugin'); ?></th>
							<th>&nbsp;</th>
						</tr>
						<tr>
							<td style="width: 25%;">
								<select id="wp_all_export_xml_element">
									<option value=""><?php _e('Select Element', 'wp_all_export_plugin'); ?></option>																
									<?php echo $engine->render_filters(); ?>						
								</select>
							</td>
							<td style="width: 25%;" id="wp_all_export_available_rules">
								<select id="wp_all_export_rule">
									<option value=""><?php _e('Select Rule', 'wp_all_export_plugin'); ?></option>							
								</select>
							</td>
							<td style="width: 25%;">
								<input id="wp_all_export_value" type="text" placeholder="value" value="" disabled="disabled"/>
							</td>
							<td style="width: 15%;">
								<a id="wp_all_export_add_rule" href="javascript:void(0);"><?php _e('Add Rule', 'wp_all_export_plugin');?></a>
							</td>
						</tr>
					</table>						
				</div>	
				<div id="wpallexport-filters" style="padding:0;">								
					<div class="wpallexport-content-section" style="padding:0; border: none;">					
						<fieldset id="wp_all_export_filtering_rules">					
							<?php
							$filter_rules = PMXE_Plugin::$session->get('filter_rules_hierarhy');
							$filter_rules_hierarhy = json_decode($filter_rules);
							?>
							<p style="margin:20px 0 5px; text-align:center; <?php if (!empty($filter_rules_hierarhy)):?> display:none;<?php endif; ?>"><?php _e('No filtering options. Add filtering options to only export records matching some specified criteria.', 'wp_all_export_plugin');?></p>					
							<ol class="wp_all_export_filtering_rules">
								<?php							
									if ( ! empty($filter_rules_hierarhy) and is_array($filter_rules_hierarhy) ): 
										$rulenumber = 0;
										foreach ($filter_rules_hierarhy as $rule) { 
											
											if ( is_null($rule->parent_id) )
											{
												$rulenumber++;
												?>
												<li id="item_<?php echo $rulenumber;?>" class="dragging">
													<div class="drag-element">
			    										<input type="hidden" value="<?php echo $rule->element; ?>" class="wp_all_export_xml_element" name="wp_all_export_xml_element[<?php echo $rulenumber; ?>]"/>
			    										<input type="hidden" value="<?php echo $rule->title; ?>" class="wp_all_export_xml_element_title" name="wp_all_export_xml_element_title[<?php echo $rulenumber; ?>]"/>
											    		<input type="hidden" value="<?php echo $rule->condition; ?>" class="wp_all_export_rule" name="wp_all_export_rule[<?php echo $rulenumber; ?>]"/>
			    										<input type="hidden" value="<?php echo $rule->value; ?>" class="wp_all_export_value" name="wp_all_export_value[<?php echo $rulenumber; ?>]"/>
			    										<span class="rule_element"><?php echo $rule->title; ?></span> 
			    										<span class="rule_as_is"><?php echo $rule->condition; ?></span> 
			    										<span class="rule_condition_value"><?php echo $rule->value; ?></span>	    										
			    										<span class="condition" <?php if ($rulenumber == count($filter_rules_hierarhy)):?>style="display:none;"<?php endif; ?>> 
			    											<label for="rule_and_<?php echo $rulenumber; ?>">AND</label>
			    											<input id="rule_and_<?php echo $rulenumber; ?>" type="radio" value="and" name="rule[<?php echo $rulenumber; ?>]" <?php if ($rule->clause == 'AND'): ?>checked="checked"<?php endif; ?> class="rule_condition"/>
			    											<label for="rule_or_<?php echo $rulenumber; ?>">OR</label>
			    											<input id="rule_or_<?php echo $rulenumber; ?>" type="radio" value="or" name="rule[<?php echo $rulenumber; ?>]" <?php if ($rule->clause == 'OR'): ?>checked="checked"<?php endif; ?> class="rule_condition"/> 
			    										</span>
			    									</div>
			    									<a href="javascript:void(0);" class="icon-item remove-ico"></a>
			    									<?php echo wp_all_export_reverse_rules_html($filter_rules_hierarhy, $rule, $rulenumber); ?>
			    								</li>
			    								<?php
											}
										}
									endif;
								?>
							</ol>	
							<div class="clear"></div>								
							<div class="ajax-console" id="filtering_result">

							</div>
							<!--a href="javascript:void(0);" id="wp_all_export_apply_filters" <?php if (empty($filter_rules_hierarhy)):?>style="display:none;"<?php endif; ?>><?php _e('Apply Filters To Export Data', 'wp_all_export_plugin');?></a-->
							<div class="wp_all_export_filter_preloader"></div>			
						</fieldset>						

						<?php if ( "product" == $post["cpt"] and class_exists('WooCommerce')) : ?>

						<div class="input wp_all_export_product_matching_mode" <?php if (empty($filter_rules_hierarhy)): ?>style="display:none;"<?php endif; ?>>
							<?php $product_matching_mode = PMXE_Plugin::$session->get('product_matching_mode'); ?>
							<label><?php _e("Variable product matching rules: ", "wp_all_export_plugin"); ?></label>
							<select name="product_matching_mode">
								<option value="strict" <?php echo ( $product_matching_mode == 'strict' ) ? 'selected="selected"' : ''; ?>><?php _e("Strict", "wp_all_export_plugin"); ?></option>
								<option value="permissive" <?php echo ( $product_matching_mode == 'permissive' ) ? 'selected="selected"' : ''; ?>><?php _e("Permissive", "wp_all_export_plugin"); ?></option>
							</select>
							<a href="#help" class="wpallexport-help" style="position: relative; top: 0px;" title="<?php _e('Strict matching requires all variations to pass in order for the product to be exported. Permissive matching allows the product to be exported if any of the variations pass.', 'wp_all_export_plugin'); ?>">?</a>							
						</div>

						<?php endif; ?>

							

					</div>	
				</div>
			</div>	
		</div>

	<?php

	endif;
	
	exit(json_encode(array('html' => ob_get_clean()))); die;

}