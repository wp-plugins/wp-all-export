<table class="layout pmxe_step_1">
	<tr>
		<td class="left">
			<h2><?php _e('Export to XML/CSV - Step 1: Choose Which posts to export', 'pmxe_plugin') ?></h2>			

			<?php if ($this->errors->get_error_codes()): ?>
				<?php $this->error() ?>
			<?php endif ?>
			
			<?php do_action('pmxe_choose_file_header'); ?>
	        <form method="post" class="choose-post-type no-enter-submit" enctype="multipart/form-data" autocomplete="off">
				<input type="hidden" name="is_submitted" value="1" />
				<?php wp_nonce_field('type_specific-cpt', '_wpnonce_type_specific-cpt') ?>
				<div class="file-type-container">
					<h3>
						<input type="radio" id="type_specific" name="type" value="specific" checked="checked" />
						<label for="type_specific"><?php _e('Export a specific post type', 'pmxe_plugin') ?></label>
					</h3>
					<div class="file-type-options">
						<?php $custom_types = get_post_types(array('_builtin' => false), 'objects'); ?>
			            <select name="cpt">
			            	<option value="post"><?php _e('Posts','pmxe_plugin');?></option>
			            	<option value="page"><?php _e('Pages','pmxe_plugin');?></option>
			            	<?php if (count($custom_types)): ?>
								<?php foreach ($custom_types as $key => $ct):?>
									<option value="<?php echo $key;?>"><?php echo $ct->labels->name; ?></option>
								<?php endforeach ?>
							<?php endif ?>	
			            </select>
			        </div>
				</div>
				<div class="file-type-container">
					<h3>
						<input type="radio" id="type_multiple" name="type" value="url" />
						<label for="type_multiple"><?php _e('Export multiple post types', 'pmxe_plugin') ?></label>
					</h3>
					<div class="file-type-options">
						<input type="checkbox" name="cpt[]" value="post" id="posts"/> <label for="posts"><?php _e('Posts', 'pmxe_plugin'); ?></label>
						<input type="checkbox" name="cpt[]" value="page" id="pages"/> <label for="pages"><?php _e('Pages', 'pmxe_plugin'); ?></label>
						<?php if (count($custom_types)): ?>
							<?php foreach ($custom_types as $key => $ct):?>
								<input type="checkbox" name="cpt[]" value="<?php echo $key; ?>" id="<?php echo $key; ?>"/> <label for="<?php echo $key; ?>"><?php echo $ct->labels->name ?></label>
							<?php endforeach ?>
						<?php endif ?>	
					</div>
				</div>								
				<div id="url_upload_status"></div>
				<p class="submit-buttons">
					<input type="hidden" name="is_submitted" value="1" />
					<?php wp_nonce_field('choose-cpt', '_wpnonce_choose-cpt') ?>
					<input type="submit" class="button button-primary button-hero large_button" value="<?php _e('Next', 'pmxe_plugin') ?>" id="advanced_upload"/>
				</p>
				<br />
				<table><tr><td class="note"></td></tr></table>
			</form>
		</td>
		<td class="right">
			&nbsp;
		</td>
	</tr>
</table>
