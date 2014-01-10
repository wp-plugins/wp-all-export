<?php 

function pmxe_admin_notices() {
	
	// notify user if history folder is not writable
	$uploads = wp_upload_dir();

	// notify user
	if (!PMXE_Plugin::getInstance()->getOption('dismiss') and strpos($_SERVER['REQUEST_URI'], 'pmxe-admin') !== false) {
		?>
		<div class="updated"><p>
			<?php printf(
					__('Welcome to WP All Export. We hope you like it. Please send all support requests and feedback to <a href="mailto:support@soflyy.com">support@soflyy.com</a>.<br/><br/><a href="javascript:void(0);" id="dismiss">dismiss</a>', 'pmxe_plugin')
			) ?>
		</p></div>
		<?php
	}		

	$input = new PMXE_Input();
	$messages = $input->get('pmxe_nt', array());
	if ($messages) {
		is_array($messages) or $messages = array($messages);
		foreach ($messages as $type => $m) {
			in_array((string)$type, array('updated', 'error')) or $type = 'updated';
			?>
			<div class="<?php echo $type ?>"><p><?php echo $m ?></p></div>
			<?php 
		}
	}
}