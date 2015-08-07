<?php

if ( ! class_exists('XmlExportEngine') ){

	require_once dirname(__FILE__) . '/XmlExportACF.php';
	require_once dirname(__FILE__) . '/XmlExportWooCommerce.php';
	require_once dirname(__FILE__) . '/XmlExportWooCommerceOrder.php';
	require_once dirname(__FILE__) . '/XmlExportUser.php';

	final class XmlExportEngine
	{		

		private $acf_export;
		private $woo_export;	
		private $woo_order_export;
		private $user_export;

		private $post;	
		private $_existing_meta_keys = array();
		private $_existing_taxonomies = array();

		private $init_fields = array(			
			array(
				'label' => 'id',
				'name'  => 'ID',
				'type'  => 'id'
			),			 			
			array(
				'label' => 'title',
				'name'  => 'title',
				'type'  => 'title'
			),		 			
			array(
				'label' => 'content',
				'name'  => 'content',
				'type'  => 'content'
			)			
		);

		private $default_fields = array( 
			array(
				'label' => 'id', 
				'name'  => 'ID',
				'type'  => 'id'
			),
			array(
				'label' => 'title', 
				'name'  => 'title',
				'type'  => 'title'
			),
			array(
				'label' => 'content', 
				'name'  => 'content',
				'type'  => 'content'
			),
			array(
				'label' => 'excerpt', 
				'name'  => 'excerpt',
				'type'  => 'excerpt'
			),
			array(
				'label' => 'date', 
				'name'  => 'date',
				'type'  => 'date'
			),
			array(
				'label' => 'post_type', 
				'name'  => 'post_type',
				'type'  => 'post_type'
			),
			array(
				'label' => 'media', 
				'name'  => 'media',
				'type'  => 'media'
			),
			array(
				'label' => 'attachments', 
				'name'  => 'attachments',
				'type'  => 'attachments'
			)
		);

		private $other_fields = array( 
			array(
				'label' => 'status', 
				'name'  => 'status',
				'type'  => 'status'
			),
			array(
				'label' => 'author', 
				'name'  => 'author',
				'type'  => 'author'
			),
			array(
				'label' => 'slug', 
				'name'  => 'slug',
				'type'  => 'slug'
			),
			array(
				'label' => 'format', 
				'name'  => 'format',
				'type'  => 'format'
			),
			array(
				'label' => 'template', 
				'name'  => 'template',
				'type'  => 'template'
			),
			array(
				'label' => 'parent', 
				'name'  => 'parent',
				'type'  => 'parent'
			),
			array(
				'label' => 'order', 
				'name'  => 'order',
				'type'  => 'order'
			),
			array(
				'label' => 'permalink', 
				'name'  => 'permalink',
				'type'  => 'permalink'
			),
			array(
				'label' => 'comment_status', 
				'name'  => 'comment_status',
				'type'  => 'comment_status'
			),
			array(
				'label' => 'ping_status', 
				'name'  => 'ping_status',
				'type'  => 'ping_status'
			)
		);	

		private $available_sections = array();		
		private $filter_sections = array();
		
		private $errors;				

		private $available_data = array(
			'acf_groups' => array(),
			'existing_acf_meta_keys' => array(),
			'existing_meta_keys' => array(),
			'init_fields' => array(),
			'default_fields' => array(),
			'other_fields' => array(),
			'woo_data' => array(),
			'existing_attributes' => array(),
			'existing_taxonomies' => array()
		);

		private $filters;

		public static $is_user_export = false;	
		public static $post_types  = array();	
		public static $exportOptions = array();
		public static $exportQuery;

		public function __construct( $post, & $errors ){		

			$this->post   = $post;
			$this->errors = $errors;

			$this->available_sections = array(
				'default' => array(
					'title'   => __("Standard", "wp_all_export_plugin"), 
					'content' => 'default_fields'					
				),
				'cats' => array(
					'title'   => __("Taxonomies", "wp_all_export_plugin"),
					'content' => 'existing_taxonomies'					
				),
				'cf' => array(
					'title'   => __("Custom Fields", "wp_all_export_plugin"), 
					'content' => 'existing_meta_keys'					
				),
				'other' => array(
					'title'   => __("Other", "wp_all_export_plugin"), 
					'content' => 'other_fields'					
				)								
			);

			$this->filter_sections = array(
				'general' => array(
					'title'  => __("General", "wp_all_export_plugin"),
					'fields' => array(
						'ID' => 'ID',
						'post_title' => 'Title',
						'post_content' => 'Content',
						'post_parent' => 'Parent ID',
						'post_date' => 'Date (Y-m-d H:i:s)',
						'post_status' => 'Status',
						'post_author' => 'Author ID'
					)
				)						
			);

			if ( 'specific' == $this->post['export_type']) { 

				self::$post_types = ( ! is_array($this->post['cpt']) ) ? array($this->post['cpt']) : $this->post['cpt'];								

				if ( in_array('product', self::$post_types) and ! in_array('product_variation', self::$post_types)) self::$post_types[] = 'product_variation';	

				if ( in_array('users', self::$post_types) ) self::$is_user_export = true;

			}	
			else
			{
				if ( 'wp_user_query' == $this->post['wp_query_selector'] ){
					self::$is_user_export = true;
				}
			}						

			self::$exportOptions = $post;						

			$this->init();

			$this->acf_export  = XmlExportACF::getInstance();
			$this->woo_export  = XmlExportWooCommerce::getInstance();
			$this->user_export = XmlExportUser::getInstance();
			$this->woo_order_export = XmlExportWooCommerceOrder::getInstance(); 

		}		

		protected function init(){

			PMXE_Plugin::$session->set('is_user_export', self::$is_user_export);	

			if ('advanced' == $this->post['export_type']) { 

				if( "" == $this->post['wp_query'] ){
					$this->errors->add('form-validation', __('WP Query field is required', 'pmxe_plugin'));
				}
				else {

					if ( self::$is_user_export )
					{
						$this->errors->add('form-validation', __('Upgrade to the professional edition of WP All Export to export users.', 'pmxe_plugin'));	
					}
					else
					{
						self::$exportQuery = eval('return new WP_Query(array(' . $this->post['wp_query'] . ', \'orderby\' => \'ID\', \'order\' => \'ASC\', \'offset\' => 0, \'posts_per_page\' => 10));');
						
						if ( empty(self::$exportQuery) ) {
							$this->errors->add('form-validation', __('Invalid query', 'pmxe_plugin'));		
						}
						elseif ( empty(self::$exportQuery->found_posts) ) {
							$this->errors->add('form-validation', __('No matching posts found for WP_Query expression specified', 'pmxe_plugin'));		
						}
						else {						
							PMXE_Plugin::$session->set('wp_query', $this->post['wp_query']);
							PMXE_Plugin::$session->set('found_posts', self::$exportQuery->found_posts);										
						}
					}

				}
			}
			else {												
				
				if ( self::$is_user_export )
				{					
					$this->errors->add('form-validation', __('Upgrade to the professional edition of WP All Export to export users.', 'pmxe_plugin'));				
				}
				else
				{															
					self::$exportQuery = new WP_Query( array( 'post_type' => self::$post_types, 'post_status' => 'any', 'orderby' => 'ID', 'order' => 'ASC', 'posts_per_page' => 10 ));							

					if (empty(self::$exportQuery->found_posts)){
						$this->errors->add('form-validation', __('No matching posts found for selected post types', 'pmxe_plugin'));	
					}
					else{					
						PMXE_Plugin::$session->set('cpt', self::$post_types);	
						PMXE_Plugin::$session->set('found_posts', self::$exportQuery->found_posts);										
					}					
				}							
			}

			PMXE_Plugin::$session->save_data();
			
		}

		public function init_additional_data(){

			$this->woo_order_export->init_additional_data();

		}

		public function init_available_data( ){

			global $wpdb;
			$table_prefix = $wpdb->prefix;

			// Prepare existing taxonomies
			if ( 'specific' == $this->post['export_type'] and ! self::$is_user_export )
			{ 
				$post_taxonomies = array_diff_key(get_taxonomies_by_object_type(array(self::$post_types[0]), 'object'), array_flip(array('post_format')));

				if ( ! empty($post_taxonomies)){
					foreach ($post_taxonomies as $tx) {
						if (strpos($tx->name, "pa_") !== 0)		
							$this->_existing_taxonomies[] = $tx->name;			
					}
				}
				$post_type = self::$post_types[0];
				$meta_keys = $wpdb->get_results("SELECT DISTINCT {$table_prefix}postmeta.meta_key FROM {$table_prefix}postmeta, {$table_prefix}posts WHERE {$table_prefix}postmeta.post_id = {$table_prefix}posts.ID AND {$table_prefix}posts.post_type = '{$post_type}' AND {$table_prefix}postmeta.meta_key NOT LIKE '_edit%' LIMIT 500");
				if ( ! empty($meta_keys)){
					foreach ($meta_keys as $meta_key) {
						$this->_existing_meta_keys[] = $meta_key->meta_key;
					}
				}
			}	
			if ( 'advanced' == $this->post['export_type'] and ! self::$is_user_export) 
			{
				$meta_keys = $wpdb->get_results("SELECT DISTINCT meta_key FROM {$table_prefix}postmeta WHERE {$table_prefix}postmeta.meta_key NOT LIKE '_edit%' LIMIT 500");
				if ( ! empty($meta_keys)){
					foreach ($meta_keys as $meta_key) {
						$this->_existing_meta_keys[] = $meta_key->meta_key;
					}
				}

				global $wp_taxonomies;	

				foreach ($wp_taxonomies as $key => $obj) {	if (in_array($obj->name, array('nav_menu'))) continue;

					if (strpos($obj->name, "pa_") !== 0 and strlen($obj->name) > 3)
						$this->_existing_taxonomies[] = $obj->name;															
				}
			}							

			// Prepare existing ACF groups & fields
			$this->acf_export->init($this->_existing_meta_keys);
			
			// Prepare existing WooCommerce data
			$this->woo_export->init($this->_existing_meta_keys);

			// Prepare existing WooCommerce Order data
			$this->woo_order_export->init($this->_existing_meta_keys);

			// Prepare existing Users data
			$this->user_export->init($this->_existing_meta_keys);			

			return $this->get_available_data();
		}

		public function get_available_data(){			

			$this->available_data['acf_groups'] 			= $this->acf_export->get('_acf_groups');
			$this->available_data['existing_acf_meta_keys'] = $this->acf_export->get('_existing_acf_meta_keys');
			$this->available_data['existing_meta_keys'] 	= $this->_existing_meta_keys;
			$this->available_data['existing_taxonomies']    = $this->_existing_taxonomies;

			$this->available_data['init_fields']    = apply_filters('wp_all_export_init_fields', $this->init_fields);	
			$this->available_data['default_fields'] = apply_filters('wp_all_export_default_fields', $this->default_fields);
			$this->available_data['other_fields']   = apply_filters('wp_all_export_other_fields', $this->other_fields);

			$this->available_data = apply_filters("wp_all_export_available_data", $this->available_data);;

			return $this->available_data;

		}		

		public function render(){
			
			$i = 0;

			ob_start();

			$available_sections = apply_filters("wp_all_export_available_sections", $this->available_sections);

			// Render Available WooCommerce Orders Data
			$this->woo_order_export->render($i);			

			foreach ($available_sections as $slug => $section) {							

				if ( ! empty($this->available_data[$section['content']]) ){
					?>										
					<p class="wpae-available-fields-group"><?php echo $section['title']; ?><span class="wpae-expander">+</span></p>
					<div class="wpae-custom-field">
						<ul>
							<li>
								<div class="default_column" rel="">
									<a href="javascript:void(0);" class="pmxe_remove_column">X</a>
									<label class="wpallexport-element-label"><?php echo __("All", "wp_all_export_plugin") . ' ' . $section['title']; ?></label>															
									<input type="hidden" name="rules[]" value="pmxe_<?php echo $slug; ?>"/>															
								</div>
							</li>
						<?php
						foreach ($this->available_data[$section['content']] as $field) {																											
							?>
							<li class="pmxe_<?php echo $slug; ?> <?php if ( ! empty($field['auto'])) echo 'wp_all_export_auto_generate';?>">
								<div class="custom_column" rel="<?php echo ($i + 1);?>">															
									<label class="wpallexport-xml-element">&lt;<?php echo (is_array($field)) ? $field['name'] : $field; ?>&gt;</label>
									<input type="hidden" name="ids[]" value="1"/>
									<input type="hidden" name="cc_label[]" value="<?php echo (is_array($field)) ? $field['label'] : $field; ?>"/>										
									<input type="hidden" name="cc_php[]" value=""/>										
									<input type="hidden" name="cc_code[]" value=""/>
									<input type="hidden" name="cc_sql[]" value=""/>
									<input type="hidden" name="cc_options[]" value=""/>										
									<input type="hidden" name="cc_type[]"  value="<?php echo (is_array($field)) ? $field['type'] : $slug; ?>"/>										
									<input type="hidden" name="cc_value[]" value="<?php echo (is_array($field)) ? $field['label'] : $field; ?>"/>
									<input type="hidden" name="cc_name[]"  value="<?php echo (is_array($field)) ? $field['name'] : $field;?>"/>
									<!--a href="javascript:void(0);" title="<?php _e('Delete field', 'wp_all_export_plugin'); ?>" class="icon-item remove-field"></a-->
								</div>								
							</li>
							<?php
							$i++;
						}
						?>
						</ul>
					</div>										
					<?php
				}

			}

			if ( ! XmlExportWooCommerceOrder::$is_active )
			{
				// Render Available ACF
				$this->acf_export->render($i);
				
			}			

			return ob_get_clean();

		}

		public function render_filters(){

			$filter_sections = apply_filters('wp_all_export_filters', $this->filter_sections);			

			foreach ($filter_sections as $slug => $section) {
				
				?>

				<optgroup label="<?php echo $section['title']; ?>">	

					<?php foreach ($section['fields'] as $key => $title) : ?>
						
						<option value="<?php echo  $key; ?>"><?php echo $title; ?></option>

					<?php endforeach; ?>

				</optgroup>

				<?php

			}

			$available_sections = apply_filters("wp_all_export_available_filter_sections", $this->available_sections);

			foreach ($available_sections as $slug => $section) {							

				if ( ! empty($this->available_data[$section['content']]) ){
					?>							
					<optgroup label="<?php echo $section['title']; ?>">								
						<?php
						
						foreach ($this->available_data[$section['content']] as $field) {	
							switch ($section['content']) {
								case 'existing_meta_keys':
									?>
									<option value="<?php echo 'cf_' . $field; ?>"><?php echo $field; ?></option>							
									<?php							
									break;
								case 'existing_taxonomies':
									?>
									<option value="<?php echo 'tx_' . $field; ?>"><?php echo $field; ?></option>							
									<?php							
									break;
								
								default:
									# code...
									break;
							}																																	
						}						
						?>
					</optgroup>										
					<?php
				}

			}

			if ( ! XmlExportWooCommerceOrder::$is_active )
			{
				// Render Available ACF
				$this->acf_export->render_filters();
				
			}

		}

		/**
	     * __get function.
	     *
	     * @access public
	     * @param mixed $key
	     * @return mixed
	     */
	    public function __get( $key ) {
	        return $this->get( $key );
	    }	

	    /**
	     * Get a session variable
	     *
	     * @param string $key
	     * @param  mixed $default used if the session variable isn't set
	     * @return mixed value of session variable
	     */
	    public function get( $key, $default = null ) {        
	        return isset( $this->{$key} ) ? $this->{$key} : $default;
	    }
	}
}
