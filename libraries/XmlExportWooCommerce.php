<?php

if ( ! class_exists('XmlExportWooCommerce') ){

	final class XmlExportWooCommerce
	{

		/**
		 * Singletone instance
		 * @var XmlExportWooCommerce
		 */
		protected static $instance;

		/**
		 * Return singletone instance
		 * @return XmlExportWooCommerce
		 */
		static public function getInstance() {
			if (self::$instance == NULL) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		private $init_fields = array(			
			array(
				'name'  => 'SKU',
				'type'  => 'woo',				
				'label' => '_sku'
			),
			array(
				'name'  => 'product_type',
				'type'  => 'cats',				
				'label' => 'product_type'
			)		
		);
		
		private $_woo_data = array();
		private $_product_data = array();

		private static $_existing_attributes = array();					

		public static $is_active = true;

		public function __construct(){			

			if ( ! class_exists('WooCommerce') 
				or ( XmlExportEngine::$exportOptions['export_type'] == 'specific' and ! in_array('product', XmlExportEngine::$post_types) ) 
					or ( XmlExportEngine::$exportOptions['export_type'] == 'advanced' and strpos(XmlExportEngine::$exportOptions['wp_query'], 'product') === false ) ) {
				self::$is_active = false;
				return;			
			}

			$this->_woo_data = array(
				'_visibility', '_stock_status', '_downloadable', '_virtual', '_regular_price', '_sale_price', '_purchase_note', '_featured', '_weight', '_length',
				'_width', '_height', '_sku', '_sale_price_dates_from', '_sale_price_dates_to', '_price', '_sold_individually', '_manage_stock', '_stock', '_upsell_ids', '_crosssell_ids',
				'_downloadable_files', '_download_limit', '_download_expiry', '_download_type', '_product_url', '_button_text', '_backorders', '_tax_status', '_tax_class', '_product_image_gallery', '_default_attributes',
				'total_sales', '_product_attributes'
			);		

			$this->_product_data = array('_sku', '_price', '_regular_price','_sale_price', '_stock_status', '_stock', '_visibility', '_product_url', 'total_sales', 'attributes');

			add_filter("wp_all_export_init_fields",    		array( &$this, "filter_init_fields"), 10, 1);
			add_filter("wp_all_export_default_fields", 		array( &$this, "filter_default_fields"), 10, 1);
			add_filter("wp_all_export_other_fields", 		array( &$this, "filter_other_fields"), 10, 1);
			add_filter("wp_all_export_available_sections",  array( &$this, "filter_available_sections"), 10, 1);
			add_filter("wp_all_export_available_data", 		array( &$this, "filter_available_data"), 10, 1);
			add_filter("wp_all_export_filters", 			array( &$this, "filter_export_filters"), 10, 1);

		}

		// [FILTERS]

			/**
			*
			* Filter data for advanced filtering
			*
			*/
			public function filter_export_filters($filters){

				$filters['product_data'] = array(
					'title'  => __('Product Data', 'wp_all_export_plugin'),
					'fields' => array()
				);

				foreach ($this->_product_data as $woo_key) {
						
					$filters['product_data']['fields']['cf_' . $woo_key] = ucwords(str_replace("_", " ", trim($woo_key, "_")));					

				}

				$filters['other'] = array(
					'title'  => __('Advanced', 'wp_all_export_plugin'),
					'fields' => array()
				);	

				if ( ! empty($this->_woo_data))
				{
					foreach ($this->_woo_data as $woo_key) {
						if ( ! in_array($woo_key, $this->_product_data))
						{
							$filters['other']['fields']['cf_' . $woo_key] = $woo_key;							
						}
					}

					if ( ! empty(self::$_existing_attributes) )
					{									
						foreach (self::$_existing_attributes as $key => $tx_name) {							
							$filters['other']['fields']['tx_' . $tx_name] = $tx_name;							
						}
					}
				}

				return $filters;
			}

			/**
			*
			* Filter Init Fields
			*
			*/
			public function filter_init_fields($init_fields){
				foreach ($this->init_fields as $field) {
					$init_fields[] = $field;
				}			
				return array_map(array( &$this, 'fix_titles'), $init_fields);
			}

			/**
			*
			* Filter Default Fields
			*
			*/
			public function filter_default_fields($default_fields){		
				foreach ($default_fields as $key => $field) {
					$default_fields[$key]['auto'] = true;
				}	
				return array_map(array( &$this, 'fix_titles'), $default_fields);	
			}

			/**
			*
			* Filter Other Fields
			*
			*/
			public function filter_other_fields($other_fields){
				
				if ( ! empty($this->_woo_data))
				{
					foreach ($this->_woo_data as $woo_key) {
						
						if ( strpos($woo_key, 'attribute_pa_') === 0 ) continue;		

						if ( ! in_array($woo_key, $this->_product_data) )
						{
							$other_fields[] = array(
								'name'  => $woo_key,
								'label' => $woo_key,
								'type'  => 'woo'								
							);
						}
					}

					// add needed fields to auto generate list
					foreach ($other_fields as $key => $field) 
					{
						if ( strpos($field['label'], '_min_') === 0 || strpos($field['label'], '_max_') === 0 ) 
							continue;
					
						$other_fields[$key]['auto'] = true;					
					}
					
					if ( ! empty(self::$_existing_attributes) )
					{
						foreach (self::$_existing_attributes as $key => $tx_name) {
							$other_fields[] = array(
								'name'  => $tx_name,
								'label' => $tx_name,
								'type'  => 'attr'								
							);							
						}
					}
				}				

				return $other_fields;
			}
				/**
				*
				* Helper method to fix fields title
				*
				*/
				protected function fix_titles($field){
					$field['name'] = ucwords(str_replace("_", " ", $field['name']));				
					return $field;
				}

			/**
			*
			* Filter Available Data
			*
			*/	
			public function filter_available_data($available_data){
				$available_data['woo_data'] = $this->_woo_data;
				$available_data['existing_attributes'] = self::$_existing_attributes;
				$available_data['product_fields'] = array();

				if ( ! empty($this->_product_data) )
				{

					foreach ($this->_product_data as $woo_key) {
						
						$available_data['product_fields'][] = array(
							'name'  => ucwords(str_replace("_", " ", trim($woo_key, "_"))),
							'label' => $woo_key,
							'type'  => 'woo',
							'auto'  => true
						);

					}					
				}				

				return $available_data;
			}

			/**
			*
			* Filter Sections in Available Data
			*
			*/
			public function filter_available_sections($available_sections){

				$available_sections['other']['title'] = __("Advanced", "wp_all_export_plugin");

				$product_data = array(
					'product_data' => array(
						'title'    => __("Product Data", "wp_all_export_plugin"), 
						'content'  => 'product_fields'						
					)
				);					
				return array_merge(array_slice($available_sections, 0, 1), $product_data, array_slice($available_sections, 1));	
			}

		// [\FILTERS]

		public function init( & $existing_meta_keys = array() ){

			if ( ! self::$is_active ) return;

			$hide_fields = array('_edit_lock', '_edit_last');		

			if ( ! empty($existing_meta_keys) )
			{
				foreach ($existing_meta_keys as $key => $record_meta_key) {
					
					if ( in_array($record_meta_key, $this->_woo_data) ) unset($existing_meta_keys[$key]);

					if ( strpos($record_meta_key, 'attribute_pa_') === 0 || strpos($record_meta_key, '_min_') === 0 || strpos($record_meta_key, '_max_') === 0){
						if ( ! in_array($record_meta_key, $this->_woo_data)){
							$this->_woo_data[] = $record_meta_key;
							unset($existing_meta_keys[$key]);
						}
											
					}
				}
			}			

			global $wp_taxonomies;	

			foreach ($wp_taxonomies as $key => $obj) {	if (in_array($obj->name, array('nav_menu'))) continue;

				if (strpos($obj->name, "pa_") === 0 and strlen($obj->name) > 3)
					self::$_existing_attributes[] = $obj->name;															
			}

		}

		protected function prepare_export_data( $record, $options, $elId )
		{
			$data = array();

			if ( ! empty($options['cc_value'][$elId]) )
			{									
				$implode_delimiter = ($options['delimiter'] == ',') ? '|' : ',';			

				$element_name = ( ! empty($options['cc_name'][$elId]) ) ? str_replace(" ", "_", $options['cc_name'][$elId]) : 'untitled_' . $elId;				
				$fieldSnipped = ( ! empty($options['cc_php'][$elId]) and ! empty($options['cc_code'][$elId]) ) ? $options['cc_code'][$elId] : false;

				switch ($options['cc_value'][$elId]) 
				{
					case 'attributes':

						if ( empty(self::$_existing_attributes) )
						{
							global $wp_taxonomies;	

							foreach ($wp_taxonomies as $key => $obj) {	if (in_array($obj->name, array('nav_menu'))) continue;

								if (strpos($obj->name, "pa_") === 0 and strlen($obj->name) > 3 and ! in_array($obj->name, self::$_existing_attributes))
									self::$_existing_attributes[] = $obj->name;															
							}
						}

						if ( ! empty(self::$_existing_attributes))
						{
							foreach (self::$_existing_attributes as $taxonomy_slug) {

								$taxonomy = get_taxonomy($taxonomy_slug);
								
								$data['Attribute Name (' . $taxonomy_slug . ')'] = $taxonomy->labels->name;

								$element_name = 'Attribute Value (' . $taxonomy_slug . ')';

								if ($record->post_parent == 0)
								{									
									$txes_list = get_the_terms($record->ID, $taxonomy_slug);
									if ( ! is_wp_error($txes_list) and ! empty($txes_list)) 
									{								
										$attr_new = array();
										foreach ($txes_list as $t) {
											$attr_new[] = $t->name;
										}										
										$data[$element_name] = apply_filters('pmxe_woo_attribute', pmxe_filter(implode($implode_delimiter, $attr_new), $fieldSnipped), $record->ID);										
									}			
									else
									{
										$data[$element_name] = '';
									}															
								}
								else
								{														
									$data[$element_name] = get_post_meta($record->ID, 'attribute_' . $taxonomy_slug, true);																										
								}	

							}
						}
						break;
					
					default:
						
						$cur_meta_values = get_post_meta($record->ID, $options['cc_value'][$elId]);		

						if ( ! empty($cur_meta_values) and is_array($cur_meta_values) )
						{
							foreach ($cur_meta_values as $key => $cur_meta_value) 
							{
								switch ($options['cc_label'][$elId]) 
								{
									case 'attributes':
										

										break;
									case '_downloadable_files':
										
										$files = maybe_unserialize($cur_meta_value);
										$file_paths = array();
										$file_names = array();

										if ( ! empty($files) ){
											
											foreach ($files as $key => $file) {
												$file_paths[] = $file['file'];
												$file_names[] = $file['name'];
											}

											$data[$element_name . '_paths'] = implode($implode_delimiter, $file_paths);																			

											$data[$element_name . '_names'] = implode($implode_delimiter, $file_names);																			
											
										}

										break;
									case '_crosssell_ids':
									case '_upsell_ids':
										$_upsell_ids = maybe_unserialize($cur_meta_value);
										$_skus = array();
										if (!empty($_upsell_ids)){
											foreach ($_upsell_ids as $_upsell_id) {
												$_skus[] = get_post_meta($_upsell_id, '_sku', true);
											}
											$data[$element_name] = implode($implode_delimiter, $_skus);								
										}													
										break;
									
									default:
										if ( empty($data[$element_name]) )
										{
											$data[$element_name] = apply_filters('pmxe_woo_field', pmxe_filter(maybe_serialize($cur_meta_value), $fieldSnipped), $options['cc_value'][$elId], $record->ID);																			
										}
										else
										{
											$data[$element_name . '_' . $key] = apply_filters('pmxe_woo_field', pmxe_filter(maybe_serialize($cur_meta_value), $fieldSnipped), $options['cc_value'][$elId], $record->ID);																														
										}
										break;
								}		
							}
						}

						if ( empty($cur_meta_values) ) 
						{					
							$data[$element_name] = apply_filters('pmxe_woo_field', pmxe_filter('', $fieldSnipped), $options['cc_value'][$elId], $record->ID);																				
						}

						break;
				}
																																																																						
			}

			return $data;
		}

		public function export_csv( & $article, & $titles, $record, $options, $elId )
		{
			if ( ! self::$is_active ) return;		

			$data_to_export = $this->prepare_export_data( $record, $options, $elId );

			foreach ($data_to_export as $key => $data) 
			{
				$article[$key] = $data;
				if ( ! in_array($key, $titles) ) $titles[] = $key;
			}

		}

		public function get_element_header( & $headers, $options, $element_key )
		{
			switch ($options['cc_value'][$element_key]) 
			{
				case 'attributes':

					if ( ! empty(self::$_existing_attributes))
					{
						foreach (self::$_existing_attributes as $taxonomy_slug) {

							$taxonomy = get_taxonomy($taxonomy_slug);
							
							$headers[] = 'Attribute Name (' . $taxonomy_slug . ')';
							$headers[] = 'Attribute Value (' . $taxonomy_slug . ')';
						}
					}

					break;

				default:

					if ( ! in_array($options['cc_name'][$element_key], $headers)) $headers[] = $options['cc_name'][$element_key];

					break;
			}
		}

		public function export_xml( & $xmlWriter, $record, $options, $elId ){

			if ( ! self::$is_active ) return;	

			$data_to_export = $this->prepare_export_data( $record, $options, $elId );

			foreach ($data_to_export as $key => $data) 
			{				
				$xmlWriter->startElement(str_replace("-", "_", preg_replace('/[^a-z0-9_]/i', '', sanitize_title($key))));
					$xmlWriter->writeCData($data);
				$xmlWriter->endElement();				
			}

		}

		public function render( & $i ){			
			
			if ( self::$is_active and ! empty($this->_woo_data) ){
				?>										
				<p class="wpae-available-fields-group"><?php _e("WooCommerce", "wp_all_export_plugin"); ?><span class="wpae-expander">+</span></p>
				<div class="wpae-custom-field">
					<ul>
						<li>
							<div class="default_column" rel="">								
								<label class="wpallexport-element-label"><?php _e("All WooCommerce Data", "wp_all_export_plugin"); ?></label>															
								<input type="hidden" name="rules[]" value="pmxe_woo"/>															
							</div>
						</li>
						<?php
						foreach ($this->_woo_data as $cur_meta_key) {		
							if ( strpos($cur_meta_key, 'attribute_pa_') === 0 ) continue;																											
							?>
							<li class="pmxe_woo">
								<div class="custom_column" rel="<?php echo ($i + 1);?>">
									<label class="wpallexport-xml-element">&lt;<?php echo $cur_meta_key; ?>&gt;</label>
									<input type="hidden" name="ids[]" value="1"/>
									<input type="hidden" name="cc_label[]" value="<?php echo $cur_meta_key; ?>"/>										
									<input type="hidden" name="cc_php[]" value=""/>										
									<input type="hidden" name="cc_code[]" value=""/>
									<input type="hidden" name="cc_sql[]" value=""/>
									<input type="hidden" name="cc_options[]" value=""/>										
									<input type="hidden" name="cc_type[]" value="woo"/>										
									<input type="hidden" name="cc_value[]" value="<?php echo $cur_meta_key; ?>"/>
									<input type="hidden" name="cc_name[]" value="<?php echo str_replace(" ", "_", $cur_meta_key);?>"/>
								</div>
							</li>
							<?php
							$i++;												
						}		
						if ( ! empty($this->_existing_attributes) ){									
							foreach ($this->_existing_attributes as $key => $tx_name) {
								?>
								<li class="pmxe_woo">
									<div class="custom_column" rel="<?php echo ($i + 1);?>">														
										<label class="wpallexport-xml-element">&lt;<?php echo $tx_name; ?>&gt;</label>
										<input type="hidden" name="ids[]" value="1"/>
										<input type="hidden" name="cc_label[]" value="<?php echo $tx_name; ?>"/>			
										<input type="hidden" name="cc_php[]" value=""/>
										<input type="hidden" name="cc_code[]" value=""/>	
										<input type="hidden" name="cc_sql[]" value=""/>		
										<input type="hidden" name="cc_options[]" value=""/>
										<input type="hidden" name="cc_type[]" value="attr"/>
										<input type="hidden" name="cc_value[]" value="<?php echo $tx_name; ?>"/>
										<input type="hidden" name="cc_name[]" value="<?php echo str_replace(" ", "_", $tx_name);?>"/>
									</div>
								</li>
								<?php
								$i++;
							}
						}
						?>
					</ul>
				</div>									
				<?php
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
