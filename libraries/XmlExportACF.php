<?php

if ( ! class_exists('XmlExportACF') ){

	final class XmlExportACF
	{

		/**
		 * Singletone instance
		 * @var XmlExportACF
		 */
		protected static $instance;

		/**
		 * Return singletone instance
		 * @return XmlExportACF
		 */
		static public function getInstance() {
			if (self::$instance == NULL) {
				self::$instance = new self();
			}
			return self::$instance;
		}
		
		private $_existing_acf_meta_keys = array();

		private $_acf_groups = array();			

		public function __construct() {			
							
		}

		public function init( & $existing_meta_keys = array() ){

			if ( ! class_exists( 'acf' ) ) return;
			
			global $acf;			

			if ($acf and version_compare($acf->settings['version'], '5.0.0') >= 0){

				$saved_acfs = get_posts(array('posts_per_page' => -1, 'post_type' => 'acf-field-group'));																				

			}
			else{

				$this->_acf_groups = apply_filters('acf/get_field_groups', array());	

			}			

			if ( ! empty($saved_acfs) ){
				foreach ($saved_acfs as $key => $obj) {
					$this->_acf_groups[] = array(
						'ID' => $obj->ID,
						'title' => $obj->post_title
					);
				}
			}								

			if ( ! empty($this->_acf_groups) ){

				// get all ACF fields
				if ($acf->settings['version'] and version_compare($acf->settings['version'], '5.0.0') >= 0)
				{		

					foreach ($this->_acf_groups as $key => $acf_obj) {

						if ( is_numeric($acf_obj['ID'])){

							$acf_fields = get_posts(array('posts_per_page' => -1, 'post_type' => 'acf-field', 'post_parent' => $acf_obj['ID'], 'post_status' => 'publish', 'orderby' => 'menu_order', 'order' => 'ASC'));				

							if ( ! empty($acf_fields) ){

								foreach ($acf_fields as $field) {				

									$fieldData = (!empty($field->post_content)) ? unserialize($field->post_content) : array();			
									
									$fieldData['ID']    = $field->ID;
									$fieldData['id']    = $field->ID;
									$fieldData['label'] = $field->post_title;
									$fieldData['key']   = $field->post_name;					

									if (empty($fieldData['name'])) $fieldData['name'] = $field->post_excerpt;

									if ( ! empty($fieldData['name'])){ 
										$this->_existing_acf_meta_keys[] = $fieldData['name'];										
									}
									
									$this->_acf_groups[$key]['fields'][] = $fieldData;
									
								}
							}
						}
					}
				}
				else
				{

					foreach ($this->_acf_groups as $key => $acf_obj) {

						if (is_numeric($acf_obj['id'])){

							$fields = array();

							foreach (get_post_meta($acf_obj['id'], '') as $cur_meta_key => $cur_meta_val)
							{	
								if (strpos($cur_meta_key, 'field_') !== 0) continue;

								$fields[] = (!empty($cur_meta_val[0])) ? unserialize($cur_meta_val[0]) : array();			
														
							}

							if (count($fields)){

								$sortArray = array();

								foreach($fields as $field){
								    foreach($field as $key2=>$value){
								        if(!isset($sortArray[$key2])){
								            $sortArray[$key2] = array();
								        }
								        $sortArray[$key2][] = $value;
								    }
								}

								$orderby = "order_no"; 

								array_multisort($sortArray[$orderby],SORT_ASC, $fields); 

								foreach ($fields as $field){ 
									$this->_acf_groups[$key]['fields'][] = $field;									
									if ( ! empty($field['name'])) $this->_existing_acf_meta_keys[] = $field['name'];
								}								
							}
						}
					}					
				}

				if ( ! empty($existing_meta_keys)){					
					foreach ($existing_meta_keys as $key => $meta_key) {
						foreach ($this->_existing_acf_meta_keys as $acf_key => $acf_value) {
							if (in_array($meta_key, array($acf_value, "_" . $acf_value)) or strpos($meta_key, $acf_value) === 0 or strpos($meta_key, "_" . $acf_value) === 0){
								unset($existing_meta_keys[$key]);
							}
						}						
					}
				}

			}	
			
		}

		public function render( & $i ){

			if ( ! empty($this->_acf_groups) ){
				?>										
				<p class="wpae-available-fields-group"><?php _e("ACF", "wp_all_export_plugin"); ?><span class="wpae-expander">+</span></p>
				<div class="wp-all-export-acf-wrapper wpae-custom-field">
				<?php
				foreach ($this->_acf_groups as $key => $group) {
					?>										
					<div class="wpae-acf-field">
						<ul>
							<li>
								<div class="default_column" rel="">									
									<label class="wpallexport-element-label"><?php echo $group['title']; ?></label>															
									<input type="hidden" name="rules[]" value="pmxe_acf_<?php echo (!empty($group['ID'])) ? $group['ID'] : $group['id'];?>"/>
								</div>
							</li>
							<?php
							if ( ! empty($group['fields'])){
								foreach ($group['fields'] as $field) {																											
									?>
									<li class="pmxe_acf_<?php echo (!empty($group['ID'])) ? $group['ID'] : $group['id'];?>">
										<div class="custom_column" rel="<?php echo ($i + 1);?>">															
											<label class="wpallexport-xml-element">&lt;<?php echo $field['label']; ?>&gt;</label>
											<input type="hidden" name="ids[]" value="1"/>
											<input type="hidden" name="cc_label[]" value="<?php echo $field['name']; ?>"/>										
											<input type="hidden" name="cc_php[]" value=""/>										
											<input type="hidden" name="cc_code[]" value=""/>
											<input type="hidden" name="cc_sql[]" value=""/>
											<input type="hidden" name="cc_options[]" value="<?php echo esc_html(serialize(array_merge($field, array('group_id' => ((!empty($group['ID'])) ? $group['ID'] : $group['id']) ))));?>"/>
											<input type="hidden" name="cc_type[]" value="acf"/>										
											<input type="hidden" name="cc_value[]" value="<?php echo $field['name']; ?>"/>
											<input type="hidden" name="cc_name[]" value="<?php echo str_replace(" ", "_", $field['label']);?>"/>
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
				?>
				</div>
				<?php
			}
		}

		public function render_filters(){

			if ( ! empty($this->_acf_groups) ){
				?>										
				<optgroup label="<?php _e("ACF", "wp_all_export_plugin"); ?>">				
				<?php
				foreach ($this->_acf_groups as $key => $group) {					
					if ( ! empty($group['fields'])){
						foreach ($group['fields'] as $field) {																											
							?>
							<option value="<?php echo 'cf_' . $field['name']; ?>"><?php echo $field['label']; ?></option>							
							<?php							
						}	
					}																		
				}
				?>
				</optgroup>
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
