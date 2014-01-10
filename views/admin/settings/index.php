<form class="settings" method="post" action="<?php echo $this->baseUrl ?>" enctype="multipart/form-data">

<h2><?php _e('WP All Export Settings', 'pmxe_plugin') ?></h2>
<hr />
<?php if ($this->errors->get_error_codes()): ?>
	<?php $this->error() ?>
<?php endif ?>

</form>
<br />

<form name="settings" method="post" action="<?php echo $this->baseUrl ?>">
<h3><?php _e('Export Settings', 'pmxe_plugin') ?></h3>
<p>
	<?php printf(__('%s <label for="session_mode_default">Session Mode (default)</label>', 'pmxe_plugin'), '<input type="radio" name="session_mode" id="session_mode_default" value="default"  style="position:relative; top:-2px;" '. (($post['session_mode'] == 'default') ? 'checked="checked"' : '') .'/>') ?> <br>
	<?php printf(__('%s <label for="session_mode_files">Session Mode (files)</label>', 'pmxe_plugin'), '<input type="radio" name="session_mode" id="session_mode_files" value="files"  style="position:relative; top:-2px;" '. (($post['session_mode'] == 'files') ? 'checked="checked"' : '') .'/>') ?> <br>
	<?php printf(__('%s <label for="session_mode_database">Session Mode (database)</label>', 'pmxe_plugin'), '<input type="radio" name="session_mode" id="session_mode_database" value="database"  style="position:relative; top:-2px;" '. (($post['session_mode'] == 'database') ? 'checked="checked"' : '') .'/>') ?>		
</p>
<p class="submit-buttons">
	<?php wp_nonce_field('edit-settings', '_wpnonce_edit-settings') ?>
	<input type="hidden" name="is_settings_submitted" value="1" />
	<input type="submit" class="button-primary" value="Save Settings" />
</p>

</form>