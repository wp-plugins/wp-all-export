<?php

if ( ! class_exists('XmlExportUser') ){

	final class XmlExportUser
	{	

		/**
		 * Singletone instance
		 * @var XmlExportUser
		 */
		protected static $instance;

		/**
		 * Return singletone instance
		 * @return XmlExportUser
		 */
		static public function getInstance() {
			if (self::$instance == NULL) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		private $init_fields = array(			
			array(
				'label' => 'id',
				'name'  => 'ID',
				'type'  => 'id'
			),						
			array(
				'label' => 'user_email',
				'name'  => 'User Email',
				'type'  => 'user_email'
			),			 			
			array(
				'label' => 'user_login',
				'name'  => 'User Login',
				'type'  => 'user_login'
			)			
		);

		private $default_fields = array(
			array(
				'label' => 'id',
				'name'  => 'ID',
				'type'  => 'id'
			),
			array(
				'label' => 'user_login',
				'name'  => 'User Login',
				'type'  => 'user_login'
			),			
			array(
				'label' => 'user_email',
				'name'  => 'User Email',
				'type'  => 'user_email'
			),
			array(
				'label' => 'first_name',
				'name'  => 'First Name',
				'type'  => 'first_name'
			),
			array(
				'label' => 'last_name',
				'name'  => 'Last Name',
				'type'  => 'last_name'
			),
			array(
				'label' => 'user_registered',
				'name'  => 'User Registered',
				'type'  => 'user_registered'
			),
			array(
				'label' => 'user_nicename',
				'name'  => 'User Nicename',
				'type'  => 'user_nicename'
			),
			array(
				'label' => 'user_url',
				'name'  => 'User URL',
				'type'  => 'user_url'
			),
			array(
				'label' => 'display_name',
				'name'  => 'Display Name',
				'type'  => 'display_name'
			),
			array(
				'label' => 'nickname',
				'name'  => 'Nickname',
				'type'  => 'nickname'
			),
			array(
				'label' => 'description',
				'name'  => 'Description',
				'type'  => 'description'
			)
		);		

		private $advanced_fields = array(					
			array(
				'label' => 'rich_editing',
				'name'  => 'rich_editing',
				'type'  => 'cf'
			),
			array(
				'label' => 'comment_shortcuts',
				'name'  => 'comment_shortcuts',
				'type'  => 'cf'
			),
			array(
				'label' => 'admin_color',
				'name'  => 'admin_color',
				'type'  => 'cf'
			),
			array(
				'label' => 'use_ssl',
				'name'  => 'use_ssl',
				'type'  => 'cf'
			),
			array(
				'label' => 'show_admin_bar_front',
				'name'  => 'show_admin_bar_front',
				'type'  => 'cf'
			),
			array(
				'label' => 'wp_capabilities',
				'name'  => 'wp_capabilities',
				'type'  => 'wp_capabilities'
			),
			array(
				'label' => 'wp_user_level',
				'name'  => 'wp_user_level',
				'type'  => 'cf'
			),
			array(
				'label' => 'show_welcome_panel',
				'name'  => 'show_welcome_panel',
				'type'  => 'cf'
			),
			array(
				'label' => 'user_pass',
				'name'  => 'user_pass',
				'type'  => 'user_pass'
			),			
			array(
				'label' => 'dismissed_wp_pointers',
				'name'  => 'dismissed_wp_pointers',
				'type'  => 'cf'
			),										
			array(
				'label' => 'session_tokens',
				'name'  => 'session_tokens',
				'type'  => 'cf'
			),
			array(
				'label' => 'wp_user-settings',
				'name'  => 'wp_user-settings',
				'type'  => 'cf'
			),
			array(
				'label' => 'wp_user-settings-time',
				'name'  => 'wp_user-settings-time',
				'type'  => 'cf'
			),
			array(
				'label' => 'wp_dashboard_quick_press_last_post_id',
				'name'  => 'wp_dashboard_quick_press_last_post_id',
				'type'  => 'cf'
			),			
			array(
				'label' => 'user_activation_key',
				'name'  => 'user_activation_key',
				'type'  => 'user_activation_key'
			),			
			array(
				'label' => 'user_status',
				'name'  => 'user_status',
				'type'  => 'user_status'
			)
		);

		private $filter_sections = array();		

		public static $is_active = true;

		public function __construct()
		{			

			if ( ( XmlExportEngine::$exportOptions['export_type'] == 'specific' and ! in_array('users', XmlExportEngine::$post_types) ) 
					or ( XmlExportEngine::$exportOptions['export_type'] == 'advanced' and XmlExportEngine::$exportOptions['wp_query_selector'] != 'wp_user_query' ) ){ 
				self::$is_active = false;
				return;
			}

			$this->filter_sections = array(
				'general' => array(
					'title'  => __("General", "wp_all_export_plugin"),
					'fields' => array(
						'ID' => 'ID',
						'user_login' 		=> 'User Login',
						'user_email' 		=> 'User Email',
						'cf_first_name' 	=> 'First Name',
						'cf_last_name' 		=> 'Last Name',
						'user_role' 		=> 'User Role',						
						'user_nicename' 	=> 'User Nicename',						
						'user_url' 			=> 'User URL',
						'user_registered' 	=> 'Registered Date (Y-m-d H:i:s)',
						'user_status' 		=> 'User Status',
						'display_name' 		=> 'Display Name',
						'cf_nickname'		=> 'Nickname',
						'cf_description'    => 'Description',
						'user_status'		=> 'User Status'
					)
				),
				'advanced' => array(
					'title'  => __("Advanced", "wp_all_export_plugin"),
					'fields' => array()
				)				
			);

			foreach ($this->advanced_fields as $key => $field) {
				if ($field['type'] == 'cf'){
					$this->filter_sections['advanced']['fields']['cf_' . $field['name']] = $field['name'];
				}
			}

			if (is_multisite()){
				$this->filter_sections['network'] = array(
					'title' => __("Network", "wp_all_export_plugin"),
					'fields' => array(
						'blog_id' => 'Blog ID'
					)
				);
			}
			
			add_filter("wp_all_export_available_sections", 	array( &$this, "filter_available_sections" ), 10, 1);
			add_filter("wp_all_export_init_fields", 		array( &$this, "filter_init_fields"), 10, 1);
			add_filter("wp_all_export_default_fields", 		array( &$this, "filter_default_fields"), 10, 1);
			add_filter("wp_all_export_other_fields", 		array( &$this, "filter_other_fields"), 10, 1);
			add_filter("wp_all_export_filters", 			array( &$this, "filter_export_filters"), 10, 1);
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
			* Filter Init Fields
			*
			*/
			public function filter_init_fields($init_fields){
				return $this->init_fields;
			}

			/**
			*
			* Filter Default Fields
			*
			*/
			public function filter_default_fields($default_fields){
				return $this->default_fields;
			}

			/**
			*
			* Filter Other Fields
			*
			*/
			public function filter_other_fields($other_fields){
				return $this->advanced_fields;
			}	

			/**
			*
			* Filter Sections in Available Data
			*
			*/
			public function filter_available_sections($sections){	
										
				unset($sections['cats']);		

				$sections['other']['title'] = __("Advanced", "wp_all_export_plugin");			

				return $sections;
			}					

		// [\FILTERS]

		public function init( & $existing_meta_keys = array() )
		{
			if ( ! self::$is_active ) return;

			if ( ! empty( XmlExportEngine::$exportQuery->results ) ) {
				foreach ( XmlExportEngine::$exportQuery->results as $user ) {
					$record_meta = get_user_meta($user->ID, '');
					if ( ! empty($record_meta)){
						foreach ($record_meta as $record_meta_key => $record_meta_value) {
							if ( ! in_array($record_meta_key, $existing_meta_keys) ){
								$to_add = true;
								foreach ($this->default_fields as $default_value) {
									if ( $record_meta_key == $default_value['name'] || $record_meta_key == $default_value['type'] ){
										$to_add = false;
										break;
									}
								}
								if ( $to_add ){
									foreach ($this->advanced_fields as $advanced_value) {
										if ( $record_meta_key == $advanced_value['name'] || $record_meta_key == $advanced_value['type']){
											$to_add = false;
											break;
										}
									}
								}
								if ( $to_add ) $existing_meta_keys[] = $record_meta_key;
							}
						}
					}		
				}
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