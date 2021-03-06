<h2>
	<?php _e('Cron Scheduling', 'wp_all_export_plugin') ?>
</h2>

<div class="wpallexport-free-edition-notice" style="padding: 20px; margin-left: 0px;">
	<a class="upgrade_link" target="_blank" href="http://www.wpallimport.com/upgrade-to-wp-all-export-pro/?utm_source=wordpress.org&amp;utm_medium=cron&amp;utm_campaign=free+wp+all+export+plugin"><?php _e('Upgrade to the professional edition of WP All Export to enable automated exports.', 'wp_all_export_plugin');?></a>
</div>

<p>
	<?php _e('To schedule an import, you must create two cron jobs in your web hosting control panel. One cron job will be used to run the Trigger script, the other to run the Execution script.', 'wp_all_export_plugin'); ?>
</p>

<p>
	<?php _e('Trigger Script URL', 'wp_all_export_plugin');?><br />
	<small><?php _e('Run the trigger script when you want to update your import. Once per 24 hours is recommended.', 'wp_all_export_plugin'); ?></small><br />
	<input style='width: 700px;' type='text' value='<?php echo home_url() . '/wp-cron.php?export_key=' . $cron_job_key . '&export_id=' . $id . '&action=trigger'; ?>' disabled="disabled"/>
	<br /><br />
	<?php _e('Execution Script URL', 'wp_all_export_plugin');?><br />
	<small><?php _e('Run the execution script frequently. Once per two minutes is recommended.','wp_all_export_plugin');?></small><br />
	<input style='width: 700px;' type='text' value='<?php echo home_url() . '/wp-cron.php?export_key=' . $cron_job_key . '&export_id=' . $id . '&action=processing'; ?>' disabled="disabled"/><br /><br />
	<?php _e('Export File URL', 'wp_all_export_plugin'); ?><br />	
	<input style='width: 700px;' type='text' value='<?php echo $file_path; ?>'  disabled="disabled"/><br /><br />
</p>

<p><strong><?php _e('Trigger Script', 'wp_all_export_plugin'); ?></strong></p>

<p><?php _e('Every time you want to schedule the import, run the trigger script.', 'wp_all_export_plugin'); ?></p>

<p><?php _e('To schedule the import to run once every 24 hours, run the trigger script every 24 hours. Most hosts require you to use “wget” to access a URL. Ask your host for details.', 'wp_all_export_plugin'); ?></p>

<p><i><?php _e('Example:', 'wp_all_export_plugin'); ?></i></p>

<p>wget -q -O /dev/null "<?php echo home_url() . '/wp-cron.php?export_key=' . $cron_job_key . '&export_id=' . $id . '&action=trigger'; ?>"</p>
 
<p><strong><?php _e('Execution Script', 'wp_all_export_plugin'); ?></strong></p>

<p><?php _e('The Execution script actually executes the import, once it has been triggered with the Trigger script.', 'wp_all_export_plugin'); ?></p>

<p><?php _e('It processes in iteration (only importing a few records each time it runs) to optimize server load. It is recommended you run the execution script every 2 minutes.', 'wp_all_export_plugin'); ?></p>

<p><?php _e('It also operates this way in case of unexpected crashes by your web host. If it crashes before the import is finished, the next run of the cron job two minutes later will continue it where it left off, ensuring reliability.', 'wp_all_export_plugin'); ?></p>

<p><i><?php _e('Example:', 'wp_all_export_plugin'); ?></i></p>

<p>wget -q -O /dev/null "<?php echo home_url() . '/wp-cron.php?export_key=' . $cron_job_key . '&export_id=' . $id . '&action=processing'; ?>"</p>

<p><strong><?php _e('Notes', 'wp_all_export_plugin'); ?></strong></p>
 
<p>
	<?php _e('Your web host may require you to use a command other than wget, although wget is most common. In this case, you must asking your web hosting provider for help.', 'wp_all_export_plugin'); ?>
</p>

<p>
	See the <a href='http://www.wpallimport.com/documentation/recurring/cron/'>documentation</a> for more details.
</p>

<a href="http://soflyy.com/" target="_blank" class="wpallexport-created-by"><?php _e('Created by', 'wp_all_export_plugin'); ?> <span></span></a>