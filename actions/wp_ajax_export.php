<?php

function pmxe_wp_ajax_export(){

	$wp_uploads = wp_upload_dir();	

	$default = PMXE_Plugin::get_default_import_options();
	$exportOptions = (isset(PMXE_Plugin::$session->data['pmxe_export']) ? PMXE_Plugin::$session->data['pmxe_export'] : array()) + $default;		

	wp_reset_postdata();

	$exportQuery = new WP_Query( array( 'post_type' => $exportOptions['cpt'], 'orderby' => 'title', 'order' => 'ASC', 'posts_per_page' => -1 ));			

	ob_start();		

	switch ($exportOptions['export_to']) {
		case 'xml':		
			echo '<?xml version="1.0" encoding="UTF-8"?>';

			?>
			<data>
			<?php
			// The Loop
			while ( $exportQuery->have_posts() ) :				

				$exportQuery->the_post();

				$record = get_post( get_the_ID() );				

				?>						
				<post>
					<post_type><?php echo get_post_type(); ?></post_type>
					<?php if ($exportOptions['is_export_title']): ?><title><?php the_title(); ?></title><?php endif; ?>

					<?php if ($exportOptions['is_export_content']): ?>
					<content><?php echo '<![CDATA['; the_content(); echo ']]>'; ?></content>
					<?php endif; ?>

					<?php if ($exportOptions['is_export_custom_fields']): ?>
					<custom_fields>
						<?php foreach (get_post_custom($record->ID) as $cur_meta_key => $cur_meta_val): ?>							
							<?php if ( $exportOptions['export_custom_fields_logic'] == 'full_export' or ($exportOptions['export_custom_fields_logic'] == 'only' and in_array($cur_meta_key, $exportOptions['custom_fields_list']))): ?>
							<<?php echo $cur_meta_key; ?>><?php echo '<![CDATA[' . ((!empty($cur_meta_val) and is_array($cur_meta_val) and count($cur_meta_val) == 1) ? $cur_meta_val[0] : serialize($cur_meta_val)) . ']]>'; ?></<?php echo $cur_meta_key;?>>
							<?php endif; ?>
						<?php endforeach; ?>
					</custom_fields>
					<?php endif; ?>

					<?php if ($exportOptions['is_export_categories']): ?>
					<taxonomies>
						<?php

						global $wp_taxonomies;

						foreach ($wp_taxonomies as $key => $obj) {									
							
							if ($exportOptions['export_categories_logic'] == 'full_export' or ($exportOptions['export_categories_logic'] == 'only' and in_array($obj->name, $exportOptions['taxonomies_list'])))
							{
								$txes_list = get_the_terms($record->ID, $obj->name);

								if ( ! is_wp_error($txes_list)) {								
									$txes_new = array();
									if (!empty($txes_list)):
										foreach ($txes_list as $t) {
											$txes_new[] = $t->slug;
										}
										?>
										<<?php echo $obj->name; ?>><?php echo implode(', ', $txes_new); ?></<?php echo $obj->name; ?>>
										<?php
									endif;									
								}
							}
						}											
						?>
					</taxonomies>
					<?php endif; ?>

					<?php if ($exportOptions['is_export_images'] and ! empty($exportOptions['export_images_logic'])): ?>
					
					<media_gallery>
						<?php

						$attachment_imgs = get_posts( array(
							'post_type' => 'attachment',
							'posts_per_page' => -1,
							'post_parent' => $record->ID,
						) );

						if ( ! empty($attachment_imgs)):

							foreach ($attachment_imgs as $attach) {
								if ( wp_attachment_is_image( $attach->ID ) ){
									?>
									<image>
										<?php if (in_array('urls', $exportOptions['export_images_logic'])): ?><url><?php echo wp_get_attachment_url( $attach->ID ); ?></url><?php endif;?>

										<?php if (in_array('meta_data', $exportOptions['export_images_logic'])): ?>
										<title><?php echo esc_html($attach->post_title); ?></title>
										<caption><?php echo esc_html($attach->post_excerpt); ?></caption>
										<description><?php echo $attach->post_content;?></description>
										<alt><?php echo get_post_meta($record->ID, '_wp_attachment_image_alt'); ?></alt>
										<?php endif; ?>
									</image>
									<?php
								}
							}

						endif;

						?>
					</media_gallery>

					<?php endif; ?>

					<?php if ($exportOptions['is_export_other']): ?>

					<other_stuff>

						<?php if ($exportOptions['is_export_dates']): ?><date><?php echo get_post_time('U', true); ?></date><?php endif; ?>
						<?php if ($exportOptions['is_export_parent']): ?><parent><?php echo $record->post_parent; ?></parent><?php endif; ?>
						<?php if ($exportOptions['is_export_template']): ?><template><?php echo get_post_meta($record->ID, '_wp_page_template', true); ?></template><?php endif; ?>
						<?php if ($exportOptions['is_export_menu_order']): ?><menu_order><?php echo $record->menu_order; ?></menu_order><?php endif; ?>
						<?php if ($exportOptions['is_export_status']): ?><status><?php echo $record->post_status; ?></status><?php endif; ?>
						<?php if ($exportOptions['is_export_format']): ?><format><?php echo get_post_format($record->ID); ?></format><?php endif; ?>
						<?php if ($exportOptions['is_export_author']): ?><author><?php echo $record->post_author; ?></author><?php endif; ?>
						<?php if ($exportOptions['is_export_slug']): ?><slug><?php echo $record->guid; ?></slug><?php endif; ?>
						<?php if ($exportOptions['is_export_excerpt']): ?><excerpt><?php echo $record->post_excerpt; ?></excerpt><?php endif; ?>
						<?php if ($exportOptions['is_export_attachments']): ?>
						<attachments>
							<?php

							$attachment_imgs = get_posts( array(
								'post_type' => 'attachment',
								'posts_per_page' => -1,
								'post_parent' => $record->ID,
							) );

							if ( ! empty($attachment_imgs)):

								foreach ($attachment_imgs as $attach) {
									if ( ! wp_attachment_is_image( $attach->ID ) ){
										?>
										<attach>
											<url><?php echo wp_get_attachment_url( $attach->ID ); ?></url>											
										</attach>
										<?php
									}
								}

							endif;

							?>
						</attachments>
						<?php endif; ?>

					</other_stuff>

					<?php endif; ?>

					</post>

				<?php 
				endwhile;
				?>

				</data>
				<?php

			break;
		case 'csv':

			// Prepare headers

			$headers = array();

			$stream = fopen("php://output", 'w');

			$max_attach_count = 0;
			$max_images_count = 0;						

			$cf = array();
			$taxes = array();

			$articles = array();

			while ( $exportQuery->have_posts() ) :

				$attach_count = 0;
				$images_count = 0;								

				$exportQuery->the_post();

				$record = get_post( get_the_ID() );

				$article = array();

				$article['post_type'] = $record->post_type;

				if ($exportOptions['is_export_title']) $article['title'] = get_the_title();				
				if ($exportOptions['is_export_content']) $article['content'] = get_the_content();

				if ($exportOptions['is_export_other']):
					if ($exportOptions['is_export_dates']) $article['date'] = get_post_time('U', true); 
					if ($exportOptions['is_export_parent']) $article['parent'] = $record->post_parent; 
					if ($exportOptions['is_export_template']) $article['template'] = get_post_meta($record->ID, '_wp_page_template', true);
					if ($exportOptions['is_export_menu_order']) $article['menu_order'] = $record->menu_order;
					if ($exportOptions['is_export_status']) $article['status'] = $record->post_status; 
					if ($exportOptions['is_export_format']) $article['format'] = get_post_format($record->ID);
					if ($exportOptions['is_export_author']) $article['author'] = $record->post_author;
					if ($exportOptions['is_export_slug']) $article['slug'] = $record->guid; 
					if ($exportOptions['is_export_excerpt']) $article['excerpt'] = $record->post_excerpt;
					if ($exportOptions['is_export_attachments']):
						$attachment_imgs = get_posts( array(
							'post_type' => 'attachment',
							'posts_per_page' => -1,
							'post_parent' => $record->ID,
						) );

						if ( ! empty($attachment_imgs)):

							foreach ($attachment_imgs as $key => $attach) {
								if ( ! wp_attachment_is_image( $attach->ID ) ){
									$article['attach_' . ($key + 1)] = wp_get_attachment_url( $attach->ID );
									$attach_count++;
								}
							}

							if ($attach_count > $max_attach_count) $max_attach_count = $attach_count;

						endif;
					endif;
				endif;

				if ($exportOptions['is_export_custom_fields']):

					foreach (get_post_custom($record->ID) as $cur_meta_key => $cur_meta_val):
						if ( $exportOptions['export_custom_fields_logic'] == 'full_export' or ($exportOptions['export_custom_fields_logic'] == 'only' and in_array($cur_meta_key, $exportOptions['custom_fields_list']))): 
							$article['CF_' . $cur_meta_key] = ((!empty($cur_meta_val) and is_array($cur_meta_val) and count($cur_meta_val) == 1) ? $cur_meta_val[0] : serialize($cur_meta_val));

							if (!in_array('CF_' . $cur_meta_key, $cf)) $cf[] = 'CF_' . $cur_meta_key;
							
						endif; 						
					endforeach;

				endif;

				if ($exportOptions['is_export_categories']):

					global $wp_taxonomies;

					foreach ($wp_taxonomies as $key => $obj) {									
						
						if ($exportOptions['export_categories_logic'] == 'full_export' or ($exportOptions['export_categories_logic'] == 'only' and in_array($obj->name, $exportOptions['taxonomies_list'])))
						{
							$txes_list = get_the_terms($record->ID, $obj->name);

							if ( ! is_wp_error($txes_list)) {								
								$txes_new = array();
								if (!empty($txes_list)):
									foreach ($txes_list as $t) {
										$txes_new[] = $t->slug;										
									}
									$article['TX_' . $obj->name] = implode('|', $txes_new);
									
									if (!in_array('TX_' . $obj->name, $taxes)) $taxes[] = 'TX_' . $obj->name;

								endif;									
							}
						}
					}	

				endif;

				if ($exportOptions['is_export_images'] and ! empty($exportOptions['export_images_logic'])):

					$attachment_imgs = get_posts( array(
						'post_type' => 'attachment',
						'posts_per_page' => -1,
						'post_parent' => $record->ID,
					) );

					if ( ! empty($attachment_imgs)):

						foreach ($attachment_imgs as $key => $attach) {
							if ( wp_attachment_is_image( $attach->ID ) ){
								if (in_array('urls', $exportOptions['export_images_logic'])) $article['image_url_' . ($key + 1)] = wp_get_attachment_url( $attach->ID );

								if (in_array('meta_data', $exportOptions['export_images_logic'])): 
									$article['image_title_' . ($key + 1)] = $attach->post_title;
									$article['image_caption_' . ($key + 1)] = $attach->post_excerpt;
									$article['image_description_' . ($key + 1)] = $attach->post_content;
									$article['image_alt_' . ($key + 1)] = get_post_meta($record->ID, '_wp_attachment_image_alt');
								endif;		

								$images_count++;						
							}
						}

						if ($max_images_count > $images_count) $max_images_count = $images_count;

					endif;

				endif;	

				$articles[] = $article;

				//fputcsv($stream, $article);
				
			endwhile;

			$headers[] = 'post_type';

			if ($exportOptions['is_export_title']) $headers[] = 'title';
			if ($exportOptions['is_export_content']) $headers[] = 'content';

			if ($exportOptions['is_export_other']):
				if ($exportOptions['is_export_dates']) $headers[] = 'date';
				if ($exportOptions['is_export_parent']) $headers[] = 'parent';
				if ($exportOptions['is_export_template']) $headers[] = 'template';
				if ($exportOptions['is_export_menu_order']) $headers[] = 'menu_order';
				if ($exportOptions['is_export_status']) $headers[] = 'status';
				if ($exportOptions['is_export_format']) $headers[] = 'format';
				if ($exportOptions['is_export_author']) $headers[] = 'author';
				if ($exportOptions['is_export_slug']) $headers[] = 'slug';
				if ($exportOptions['is_export_excerpt']) $headers[] = 'excerpt';

				for ($i = 0; $i < $max_attach_count; $i++){
					$headers[] = 'attach_' . ($i + 1);
				}

			endif;

			if (!empty($cf)){
				foreach ($cf as $cf_key) {
					$headers[] = $cf_key;
				}
			}

			if (!empty($taxes)){
				foreach ($taxes as $tx) {
					$headers[] = $tx;
				}
			}

			for ($i = 0; $i < $max_images_count; $i++){
				
				if (in_array('urls', $exportOptions['export_images_logic'])) $headers[] = 'image_url_' . ($i + 1);

				if (in_array('meta_data', $exportOptions['export_images_logic'])):
					$headers[] = 'image_title_' . ($i + 1);
					$headers[] = 'image_caption_' . ($i + 1);
					$headers[] = 'image_description_' . ($i + 1);
					$headers[] = 'image_alt_' . ($i + 1);
				endif;

			}

			fputcsv($stream, $headers);

			foreach ($articles as $article) {
				$line = array();
				foreach ($headers as $header) {
					$line[$header] = (!empty($article[$header])) ? $article[$header] : '';	
				}	
				fputcsv($stream, $line);
			}			

			break;			
			
		default:
			# code...
			break;
	}				

	wp_reset_postdata();
		
	$export_file = $wp_uploads['path'] . '/' . time() . '.' . $exportOptions['export_to'];
	
	if (@file_exists($export_file)) @unlink($export_file);

	file_put_contents($export_file, ob_get_clean());

	if ( file_exists($export_file)){
		$wp_filetype = wp_check_filetype(basename($export_file), null );
		$attachment_data = array(
		    'guid' => $wp_uploads['baseurl'] . '/' . _wp_relative_upload_path( $export_file ), 
		    'post_mime_type' => $wp_filetype['type'],
		    'post_title' => preg_replace('/\.[^.]+$/', '', basename($export_file)),
		    'post_content' => '',
		    'post_status' => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment_data, $export_file );	
	}	

	PMXE_Plugin::$session['pmxe_export']['export_file'] = $export_file;

	pmxe_session_commit(); 	

	exit(json_encode(array('file' => $export_file))); die;

}

?>