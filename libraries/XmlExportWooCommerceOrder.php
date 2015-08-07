<?php

if ( ! class_exists('XmlExportWooCommerceOrder') ){

	final class XmlExportWooCommerceOrder
	{
		/**
		 * Singletone instance
		 * @var XmlExportWooCommerceOrder
		 */
		protected static $instance;

		/**
		 * Return singletone instance
		 * @return XmlExportWooCommerceOrder
		 */
		static public function getInstance() {
			if (self::$instance == NULL) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		public static $is_active = true;

		public static $order_sections = array();		
		public static $order_items_per_line = false;
		public static $orders_data = null;
		public static $exportQuery = null;

		private $init_fields = array(			
			array(
				'name'    => 'Order ID',
				'type'    => 'woo_order',
				'options' => 'order',
				'label'   => 'ID'
			),
			array(
				'name'    => 'Order Key',
				'type'    => 'woo_order',
				'options' => 'order',
				'label'   => '_order_key'
			),			
			array(
				'name'    => 'Title',
				'type'    => 'woo_order',
				'options' => 'order',
				'label'   => 'post_title'
			)			
		);

		private $filter_sections = array();		

		public function __construct()
		{			

			if ( ! class_exists('WooCommerce') 
					or ( XmlExportEngine::$exportOptions['export_type'] == 'specific' and ! in_array('shop_order', XmlExportEngine::$post_types) ) 
						or ( XmlExportEngine::$exportOptions['export_type'] == 'advanced' and strpos(XmlExportEngine::$exportOptions['wp_query'], 'shop_order') === false ) ) {
				self::$is_active = false;
				return;			
			}						
			$this->filter_sections = array(
				'general' => array(
					'title'  => __("Order", "wp_all_export_plugin"),
					'fields' => array(
						'ID' 						=> __('Order ID', 'wp_all_export_plugin'),
						'cf__order_key'				=> __('Order Key', 'wp_all_export_plugin'),				
						'post_date' 				=> __('Order Date', 'wp_all_export_plugin'),
						'cf__completed_date' 		=> __('Completed Date', 'wp_all_export_plugin'),
						'post_title' 				=> __('Title', 'wp_all_export_plugin'),
						'post_status' 				=> __('Order Status', 'wp_all_export_plugin'),
						'cf__order_currency' 		=> __('Order Currency', 'wp_all_export_plugin'),						
						'cf__payment_method_title' 	=> __('Payment Method', 'wp_all_export_plugin'),
						'cf__order_total' 			=> __('Order Total', 'wp_all_export_plugin')
					)
				),
				'customer' => array(
					'title'  => __("Customer", "wp_all_export_plugin"),
					'fields' => array()											
				)				
			);

			foreach ($this->available_customer_data() as $key => $value) {
				$this->filter_sections['customer']['fields'][($key == 'post_excerpt') ? $key : 'cf_' . $key] = $value;
			}

			if ( empty(PMXE_Plugin::$session) ) // if cron execution
			{
				$id = $_GET['export_id'];
				$export = new PMXE_Export_Record();
				$export->getById($id);	
				if ( ! $export->isEmpty() and $export->options['export_to'] == 'csv'){	
					$this->init_additional_data();
				}
			} 
			else
			{
				self::$orders_data = PMXE_Plugin::$session->get('orders_data');								
			}

			add_filter("wp_all_export_available_sections", 			array( &$this, "filter_available_sections" ), 10, 1);
			add_filter("wp_all_export_available_filter_sections", 	array( &$this, "filter_available_filter_sections" ), 10, 1);			
			add_filter("wp_all_export_init_fields", 				array( &$this, "filter_init_fields"), 10, 1);
			add_filter("wp_all_export_filters", 					array( &$this, "filter_export_filters"), 10, 1);

			self::$order_sections = $this->available_sections();

		}

		// [FILTERS]

			/**
			*
			* Filter data for advanced filtering
			*
			*/
			public function filter_export_filters($filters){
				return $this->filter_sections;
			}

			/**
			*
			* Filter sections for advanced filtering
			*
			*/
			public function filter_available_filter_sections($sections){
				unset($sections['cats']);
				$sections['cf']['title'] = __('Advanced', 'wp_all_export_plugin');			
				return $sections;
			}	

			/**
			*
			* Filter Init Fields
			*
			*/
			public function filter_init_fields($init_fields){
				return $this->init_fields;
			}

			/**
			*
			* Filter Sections in Available Data
			*
			*/
			public function filter_available_sections($sections){						
				return array();
			}								

		// [\FILTERS]

		public function init( & $existing_meta_keys = array() ){

			if ( ! self::$is_active ) return;	

			if ( ! empty($existing_meta_keys) )
			{
				foreach (self::$order_sections as $slug => $section) :
					
					foreach ($section['meta'] as $cur_meta_key => $cur_meta_label) 
					{	
						foreach ($existing_meta_keys as $key => $record_meta_key) 
						{
							if ( $record_meta_key == $cur_meta_key )
							{
								unset($existing_meta_keys[$key]);
								break;
							}
						}									
					}

				endforeach;		

				foreach ($existing_meta_keys as $key => $record_meta_key) 
				{							
					self::$order_sections['cf']['meta'][$record_meta_key] = array(
						'name' => $record_meta_key,
						'label' => $record_meta_key,
						'options' => '',
						'type' => 'cf'
					);
				}
			}				

			global $wpdb;
			$table_prefix = $wpdb->prefix;

			$product_data = $this->available_order_default_product_data();

			$meta_keys = $wpdb->get_results("SELECT DISTINCT {$table_prefix}woocommerce_order_itemmeta.meta_key FROM {$table_prefix}woocommerce_order_itemmeta");
			if ( ! empty($meta_keys)){
				foreach ($meta_keys as $meta_key) {
					if (strpos($meta_key->meta_key, "pa_") !== 0 and empty(self::$order_sections['cf']['meta'][$meta_key->meta_key]) and empty($product_data[$meta_key->meta_key])) 
						self::$order_sections['cf']['meta'][$meta_key->meta_key] = array(
							'name' => $meta_key->meta_key,
							'label' => $meta_key->meta_key,
							'options' => 'items',
							'type' => 'woo_order'
						);
				}
			}	

		}

		public function init_additional_data(){

			if ( ! self::$is_active ) return;											

		}

		private $order_items  		= null;
		private $order_taxes  		= null;
		private $order_shipping 	= null;
		private $order_coupons 		= null;
		private $order_surcharge 	= null;
		private $__total_fee_amount = null;	
		private $__coupons_used     = null;
		private $order_id 			= null;

		protected function prepare_export_data( $record, $options, $elId ){						

			// an array with data to export
			$data = array();

			global $wpdb;
			$table_prefix = $wpdb->prefix;									

			if ( ! empty($options['cc_value'][$elId]) ){					

				$fieldSnipped = ( ! empty($options['cc_php'][$elId]) and ! empty($options['cc_code'][$elId]) ) ? $options['cc_code'][$elId] : false;

				switch ($options['cc_options'][$elId]) {
					
					case 'order':
					case 'customer':					
						
						$data[$options['cc_name'][$elId]] = ( strpos($options['cc_value'][$elId], "_") === 0 ) ? get_post_meta($record->ID, $options['cc_value'][$elId], true) : $record->$options['cc_value'][$elId];						

						if ($options['cc_value'][$elId] == "post_title")
						{							
							$data[$options['cc_name'][$elId]] = str_replace("&ndash;", '-', $data[$options['cc_name'][$elId]]);
						}

						$data[$options['cc_name'][$elId]] = pmxe_filter( $data[$options['cc_name'][$elId]], $fieldSnipped);	

						break;					
				}

			}

			return $data;
		}

		private $additional_articles = array();

		public function export_csv( & $article, & $titles, $record, $options, $elId ){		

			if ( ! self::$is_active ) return;		

			$data_to_export = $this->prepare_export_data( $record, $options, $elId );

			foreach ($data_to_export as $key => $data) {
				
				if ( ! in_array($key, array('items', 'taxes', 'shipping', 'coupons', 'surcharge')) )
				{										
					$article[$key] = $data;
					if ( ! in_array($key, $titles) ) $titles[] = $key;
				}
			}
		}		

		public function get_element_header( & $headers, $options, $element_key ){

			if ( ! in_array($options['cc_name'][$element_key], $headers)) $headers[] = $options['cc_name'][$element_key];

		}

		public function get_rate_friendly_name( $order_item_id ){

			global $wpdb;			
			$table_prefix = $wpdb->prefix;		

			$rate_details = null;
			$meta_data = $wpdb->get_results("SELECT * FROM {$table_prefix}woocommerce_order_itemmeta WHERE order_item_id = {$order_item_id}", ARRAY_A);			
			foreach ($meta_data as $meta) {
				if ($meta['meta_key'] == 'rate_id'){
					$rate_id = $meta['meta_value'];														
					$rate_details = $wpdb->get_row("SELECT * FROM {$table_prefix}woocommerce_tax_rates WHERE tax_rate_id = {$rate_id}");																																	
					break;
				}	
			}

			return $rate_details ? $rate_details->tax_rate_name : '';

		}

		public function export_xml( & $xmlWriter, $record, $options, $elId ){

			if ( ! self::$is_active ) return;	

			$data_to_export = $this->prepare_export_data( $record, $options, $elId );

			foreach ($data_to_export as $key => $data) {
				
				if ( ! in_array($key, array('items', 'taxes', 'shipping', 'coupons', 'surcharge')) )				
				{			
					$xmlWriter->writeElement(str_replace("-", "_", sanitize_title($key)), $data);							
				}				
			}				
		}

		public function render( & $i ){			
				
			if ( ! self::$is_active ) return;

			foreach (self::$order_sections as $slug => $section) :
				?>										
				<p class="wpae-available-fields-group"><?php echo $section['title']; ?><span class="wpae-expander">+</span></p>
				<div class="wpae-custom-field">
					<?php if ( ! in_array($slug, array('order', 'customer', 'cf'))) : ?>
					<div class="wpallexport-free-edition-notice">									
						<a class="upgrade_link" target="_blank" href="http://www.wpallimport.com/upgrade-to-wp-all-export-pro/?utm_source=wordpress.org&amp;utm_medium=wooco+orders&amp;utm_campaign=free+wp+all+export+plugin"><?php _e('Upgrade to the professional edition of WP All Export to export custom order data.','wp_all_export_plugin');?></a>
					</div>
					<?php endif; ?>
					<ul>
						<li <?php if ( ! in_array($slug, array('order', 'customer', 'cf'))) : ?>class="wpallexport_disabled"<?php endif; ?>>
							<div class="default_column" rel="">								
								<label class="wpallexport-element-label"><?php echo __("All", "wp_all_export_plugin") . ' ' . $section['title'] . ' ' . __("Data", "wp_all_export_plugin"); ?></label>
								<input type="hidden" name="rules[]" value="pmxe_<?php echo $slug;?>"/>
							</div>
						</li>
						<?php
						foreach ($section['meta'] as $cur_meta_key => $field) {									
							?>
							<li class="pmxe_<?php echo $slug; ?> <?php if ( ! in_array($slug, array('order', 'customer', 'cf'))) : ?>wpallexport_disabled<?php endif;?>">
								<div class="custom_column" rel="<?php echo ($i + 1);?>">
									<label class="wpallexport-xml-element">&lt;<?php echo (is_array($field)) ? $field['name'] : $field; ?>&gt;</label>
									<input type="hidden" name="ids[]" value="1"/>
									<input type="hidden" name="cc_label[]" value="<?php echo (is_array($field)) ? $field['label'] : $cur_meta_key; ?>"/>										
									<input type="hidden" name="cc_php[]" value=""/>										
									<input type="hidden" name="cc_code[]" value=""/>
									<input type="hidden" name="cc_sql[]" value=""/>
									<input type="hidden" name="cc_options[]" value="<?php echo (is_array($field)) ? $field['options'] : $slug;?>"/>										
									<input type="hidden" name="cc_type[]" value="<?php echo (is_array($field)) ? $field['type'] : 'woo_order'; ?>"/>
									<input type="hidden" name="cc_value[]" value="<?php echo (is_array($field)) ? $field['label'] : $cur_meta_key; ?>"/>
									<input type="hidden" name="cc_name[]" value="<?php echo (is_array($field)) ? $field['name'] : $field;?>"/>
								</div>
							</li>
							<?php
							$i++;												
						}																		
						?>
					</ul>
				</div>									
				<?php
			endforeach;
		}

		public function render_filters(){
			
		}

		public function available_sections(){

			$sections = array(
				'order'    => array(
					'title' => __('Order', 'wp_all_export_plugin'),
					'meta'  => $this->available_order_data()
				),
				'customer' => array(
					'title' => __('Customer', 'wp_all_export_plugin'),
					'meta'  => $this->available_customer_data()
				),
				'items'    => array(
					'title' => __('Items', 'wp_all_export_plugin'),
					'meta'  => $this->available_order_default_product_data()
				),
				'taxes'    => array(
					'title' => __('Taxes & Shipping', 'wp_all_export_plugin'),
					'meta'  => $this->available_order_taxes_data()
				),
				'fees'     => array(
					'title' => __('Fees & Discounts', 'wp_all_export_plugin'),
					'meta'  => $this->available_order_fees_data()
				),
				'cf'       => array(
					'title' => __('Advanced', 'wp_all_export_plugin'),
					'meta'  => array()
				),
			);

			return apply_filters('wp_all_export_available_order_sections_filter', $sections);

		}

		/*
		 * Define the keys for orders informations to export
		 */
		public function available_order_data()
		{
			$data = array(			   
				'ID' 					=> __('Order ID', 'wp_all_export_plugin'),
				'_order_key' 			=> __('Order Key', 'wp_all_export_plugin'),				
				'post_date' 			=> __('Order Date', 'wp_all_export_plugin'),
				'_completed_date' 		=> __('Completed Date', 'wp_all_export_plugin'),
				'post_title' 			=> __('Title', 'wp_all_export_plugin'),
				'post_status' 			=> __('Order Status', 'wp_all_export_plugin'),
				'_order_currency' 		=> __('Order Currency', 'wp_all_export_plugin'),				
				'_payment_method_title' => __('Payment Method', 'wp_all_export_plugin'),
				'_order_total' 			=> __('Order Total', 'wp_all_export_plugin')
			);
				
			return apply_filters('wp_all_export_available_order_data_filter', $data);
		}

		/*
		 * Define the keys for general product informations to export
		 */
		public function available_order_default_product_data()
		{			

			$data = array(
				'_product_id'  			=> __('Product ID', 'wp_all_export_plugin'),
				'__product_sku' 		=> __('SKU', 'wp_all_export_plugin'),
				'__product_title' 		=> __('Product Name', 'wp_all_export_plugin'),
				'__product_variation' 	=> __('Product Variation Details', 'wp_all_export_plugin'),
				'_qty' 					=> __('Quantity', 'wp_all_export_plugin'),
				'_line_subtotal' 		=> __('Item Cost', 'wp_all_export_plugin'),
				'_line_total' 			=> __('Item Total', 'wp_all_export_plugin')
			);			

			return apply_filters('wp_all_export_available_order_default_product_data_filter', $data);
		}

		public function available_order_taxes_data(){
			
			$data = array(
				'tax_order_item_name'  		=> __('Rate Code (per tax)', 'wp_all_export_plugin'),
				'tax_rate' 					=> __('Rate Percentage (per tax)', 'wp_all_export_plugin'),
				'tax_amount' 				=> __('Amount (per tax)', 'wp_all_export_plugin'),
				'_order_tax' 				=> __('Total Tax Amount', 'wp_all_export_plugin'),
				'shipping_order_item_name' 	=> __('Shipping Method', 'wp_all_export_plugin'),
				'_order_shipping' 			=> __('Shipping Cost', 'wp_all_export_plugin')
			);

			return apply_filters('wp_all_export_available_order_default_taxes_data_filter', $data);
		}

		public function available_order_fees_data(){

			$data = array(
				'discount_amount'  		=> __('Discount Amount (per coupon)', 'wp_all_export_plugin'),
				'__coupons_used' 		=> __('Coupons Used', 'wp_all_export_plugin'),
				'_cart_discount' 		=> __('Total Discount Amount', 'wp_all_export_plugin'),
				'fee_line_total' 		=> __('Fee Amount (per surcharge)', 'wp_all_export_plugin'),
				'__total_fee_amount' 	=> __('Total Fee Amount', 'wp_all_export_plugin')				
			);

			return apply_filters('wp_all_export_available_order_fees_data_filter', $data);
		}

		public function available_customer_data()
		{
			
			$main_fields = array(
				'_customer_user' => __('Customer User ID', 'wp_all_export_plugin'),
				'post_excerpt'   => __('Customer Note', 'wp_all_export_plugin')				
			);

			$data = array_merge($main_fields, $this->available_billing_information_data(), $this->available_shipping_information_data());

			return apply_filters('wp_all_export_available_user_data_filter', $data);
		
		}

		public function available_billing_information_data()
		{
			
			$keys = array(
				'_billing_first_name',  '_billing_last_name', '_billing_company',
				'_billing_address_1', '_billing_address_2', '_billing_city',
				'_billing_postcode', '_billing_country', '_billing_state', 
				'_billing_email', '_billing_phone'
			);

			$data = $this->generate_friendly_titles($keys, 'billing');

			return apply_filters('wp_all_export_available_billing_information_data_filter', $data);
		
		}

		public function available_shipping_information_data()
		{
			
			$keys = array(
				'_shipping_first_name', '_shipping_last_name', '_shipping_company', 
				'_shipping_address_1', '_shipping_address_2', '_shipping_city', 
				'_shipping_postcode', '_shipping_country', '_shipping_state'
			);

			$data = $this->generate_friendly_titles($keys, 'shipping');

			return apply_filters('wp_all_export_available_shipping_information_data_filter', $data);
		
		}

		public function generate_friendly_titles($keys, $keyword = ''){
			$data = array();
			foreach ($keys as $key) {
									
				$key1 = ucwords(str_replace('_', ' ', $key));
						$key2 = '';

						if(strpos($key1, $keyword)!== false)
						{
							$key1 = str_replace($keyword, '', $key1);
							$key2 = ' ('.__($keyword, 'wp_all_export_plugin').')';
						}
				
				$data[$key] = __(trim($key1), 'woocommerce').$key2;	
										
			}
			return $data;
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