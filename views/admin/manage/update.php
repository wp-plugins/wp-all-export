<h2><?php _e('Re-run Export', 'pmxe_plugin') ?></h2>

<?php if ($this->errors->get_error_codes()): ?>
		<?php $this->error() ?>
<?php endif ?>

<form method="post">
	<p><?php printf(__('Are you sure you want to re-run <strong>%s</strong> export?', 'pmxe_plugin'), $item->friendly_name) ?></p>	
	
	<p class="submit">
		<?php wp_nonce_field('update-export', '_wpnonce_update-export') ?>
		<input type="hidden" name="is_confirmed" value="1" />
		<input type="submit" class="button-primary ajax-update" value="Export Posts" />
	</p>
	
</form>