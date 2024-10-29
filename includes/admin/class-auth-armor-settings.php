<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Setting Class
 * 
 * Manage Admin Panel Class
 *
 * @package Auth Armor
 * @since 1.0.0
 */
if( !class_exists( 'Auth_Armor_Settings' ) ) {

	class Auth_Armor_Settings {

		public $options;
		public $wizard_options;

		//class constructor 
		function __construct() {
			$this->options = get_option( 'autharmor_options' );
			$this->wizard_options = get_option( 'autharmor_wizard_options' );						
		}

		/**
		 * Create Auth Armor setting in backend
		 */
		public function create_admin_setting_page(){
			//$setup_options = get_option( 'auth_armor_setup_wizard_confirm' );
			//if(!empty($setup_options)){
				add_menu_page( 
					__( 'Auth Armor Setting', 'auth-armor' ),
					'Auth Armor Settings',
					'manage_options',
					'autharmor_settings',
					array($this,'autharmor_setting_page'),
					'dashicons-admin-users'				
				); 

				add_submenu_page( 
					__( 'Auth Armor Setting', 'auth-armor' ),
					'Auth Armor Settings', 
					'Auth Armor Setting',
					'manage_options', 
					'autharmor_setting'
				);
			//}			
		}

		/**
		 * Auth Armor setting page display from here
		 */
		public function autharmor_setting_page()
		{
			?>
			<div class='wrap'>  
				<h2><?php esc_html_e('Auth Armor Settings','auth-armor');?></h2> 
				<?php settings_errors(); ?>  
				<form method='POST' action='options.php'>
					<?php 
					settings_fields( 'autharmor_options' );
					do_settings_sections( 'autharmor_general_settings' );              
					submit_button();  
					?>
				</form> 
			</div>
			<?php
		}

		/**
		 * Register and add setting from here
		 */
		public function autharmor_register_settings() {

			// General Setting
			register_setting( 'autharmor_options', 'autharmor_options', '' );
			add_settings_section( 'api_settings', '', '', 'autharmor_general_settings' );
			
			add_settings_field( 'auth_plugin_setting_enable', esc_html( 'Enable Auth Armor Functionality', 'auth-armor' ), array($this,'auth_plugin_setting_enable'), 'autharmor_general_settings', 'api_settings' );
			add_settings_field( 'auth_plugin_setting_api_key', "<label for='auth_plugin_setting_api_key'>".esc_html('API Key', 'auth-armor' )."</label>", array($this,'auth_plugin_setting_api_key'), 'autharmor_general_settings', 'api_settings' );
			add_settings_field( 'auth_plugin_setting_api_secret', "<label for='auth_plugin_setting_api_secret'>".esc_html('API Secret', 'auth-armor' )."</label>", array($this,'auth_plugin_setting_api_secret'), 'autharmor_general_settings', 'api_settings' );
			add_settings_field( 'auth_plugin_setting_timeout', "<label for='auth_plugin_setting_api_timeout'>".esc_html('Default Timeout (In Seconds)', 'auth-armor' )."</label>", array($this,'auth_plugin_setting_timeout'), 'autharmor_general_settings', 'api_settings' );
			add_settings_field( 'auth_plugin_setting_results_login_type', "<label for='auth_plugin_setting_results_login_type'>".esc_html('Login Type', 'auth-armor' )."</label>", array($this,'auth_plugin_setting_results_login_type'), 'autharmor_general_settings', 'api_settings' );
			add_settings_field( 'auth_plugin_setting_QR_code', esc_html('QR Code Color Settings', 'auth-armor' ), array($this,'auth_plugin_setting_Qr_code'), 'autharmor_general_settings', 'api_settings' );
		}

		/**
		 * Display and add setting for nickname
		 */
		public function auth_plugin_setting_nickname() {
			?>
			<input id='autharmor_nickname' style='width: 50%;' type='text' value='' />
		<?php 
		}

		/**
		 * Display and add setting for api key
		 */
		public function auth_plugin_setting_api_key() {

			$api_key = '';
			if(isset($this->options['api_key'])){
				$api_key = $this->options['api_key'];
			}
			?>
			<div class='input-with-loader-wrap'>
				<input id='auth_plugin_setting_api_key' style='width: 50%;' name='autharmor_options[api_key]' type='text' value='<?php echo esc_attr( $api_key );?>' />
				<div class='loader-main-wrapper'>
					<input type='button' name='verify_details' data-method='0' class='verify_details' value='<?php echo esc_html('Verify Details','auth-armor');?>'>
					<img class='loader' src='<?php echo esc_url(AUTH_ARMOR_INCLUDE_URL."images/loader.gif")?>' alt='loader'>
					<span class='verify-details-msg'></span>
				</div>
			</div>
		<?php
		}

		/**
		 * Display and add setting for api secret key
		 */
		public function auth_plugin_setting_api_secret() {
			$api_secret = '';
			if(isset($this->options['api_secret'])){
				$api_secret = $this->options['api_secret'];
			}
			?>
			<input id='auth_plugin_setting_api_secret' style='width: 50%;' name='autharmor_options[api_secret]' type='text' value='<?php echo esc_attr( $api_secret ); ?>' />
		<?php 	
		}

		/**
		 * Display and add setting for enable/disable auth armor functionality
		 */
		public function auth_plugin_setting_enable() {
			$checked = '';
			if(isset($this->wizard_options['api_secret']) && isset($this->wizard_options['api_key']) && !empty($this->wizard_options['api_secret']) && !empty($this->wizard_options['api_key'])){
				$checked = 'checked';	
			} else if(isset($this->options['enable'])){
				$checked = 'checked';	
			}
			?>
			<input id='auth_plugin_setting_enable' name='autharmor_options[enable]' type='checkbox' <?php echo esc_attr($checked);?> value='1' />
			<label for='auth_plugin_setting_enable'><?php echo esc_html('Plugin will only work if you enable this option','auth-armor');?></label>
			<?php
		}
				
		/**
		 * Add timeout setting 
		 */
		public function auth_plugin_setting_timeout(){
			$timeout = isset($this->options['api_timeout']) ? $this->options['api_timeout'] : '120';
			?>
			<input id='auth_plugin_setting_api_timeout' style='width: 50%;' name='autharmor_options[api_timeout]' type='text' value='<?php echo esc_attr( $timeout ); ?>' />
			<?php 
		}

		/**
		 * QR code color setting 
		 */
		public function auth_plugin_setting_Qr_code(){
			
			$back_color = isset($this->options['background_color']) ? $this->options['background_color'] : '#ffffff';
			$fore_color = isset($this->options['foreground_color']) ? $this->options['foreground_color'] : '#000000';
			?>
			<div class="qr_code_setting_div">
					
					<div class="back-color">
						<label for="background-color"><?php echo esc_html("Background Color : ","auth-armor");?></label>
						<input type="color" name="autharmor_options[background_color]" id="background_color" value="<?php echo esc_attr($back_color);?>" />
					</div>
					<div class="foreground-color">
						<label for="foreground-color"><?php echo esc_html("Foreground Color : ","auth-armor"); ?></label>
						<input type="color" name="autharmor_options[foreground_color]" id="foreground_color" value="<?php echo esc_attr($fore_color);?>">
					</div>
				  </div>
			<?php	  
		}

		/**
		 * Display and add setting for login page display QR code based on login type 
		 */
		public function auth_plugin_setting_results_login_type() {
			$options = get_option( 'autharmor_options' );
			$login_type = isset($options['login_type']) ? $options['login_type'] : 1;
			echo "<select id='auth_plugin_setting_results_login_type' class='login_type' name='autharmor_options[login_type]'>";
				 for($i=1;$i<=2;$i++)
				 {
					 $select = ($i==$login_type) ? 'selected=selected' : '';
					 ?>
					 <option value="<?php echo esc_attr($i);?>" <?php echo esc_attr($select);?> data-type="login_type_<?php echo esc_attr($i);?>"><?php echo esc_html("Login type ","auth-armor"); ?><?php echo esc_attr($i);?></option>
					 <?php 
				 }

				$type1 = (1==$login_type) ? 'display:block;' : 'display:none;';
				$type2 = (2==$login_type) ? 'display:block;' : 'display:none;';

			echo '</select>';
			echo '<br><br>';
			?>
			<table>
				<tr>
					<td class="login_type_1 login_type_cls" style="<?php esc_attr_e($type1,"auth-armor");?>"><b><?php esc_html_e("Login Type 1","auth-armor");?> </b>: <?php esc_html_e("Username + Password + Login with Auth Armor (Push Message and QR Code) (mixed mode)","auth-armor");?></td>
					<td class="login_type_2 login_type_cls" style="<?php esc_attr_e($type2,"auth-armor");?>"><b><?php esc_html_e("Login Type 2","auth-armor");?> </b>: <?php esc_html_e("Username-less (no username) – Just a “Login with Auth Armor” button, and a QR code. No push message - Auth Armor Only mode","auth-armor");?></td>
				</tr>
				<tr>
					<td class="login_type_1 login_type_cls login_qr_code" style="<?php esc_attr_e($type1,"auth-armor");?>"><img alt="" class="qr-code-img-list"  src="<?php echo esc_url(AUTH_ARMOR_INCLUDE_URL.'images/login-1.jpg');?>"></td>
					<td class="login_type_2 login_type_cls login_qr_code" style="<?php esc_attr_e($type2,"auth-armor");?>"><img alt="" class="qr-code-img-list" src="<?php echo esc_url(AUTH_ARMOR_INCLUDE_URL.'images/type-2.jpg');?>"></td>
				</tr>
			</table>
			<?php
		}

		/**
		 * Adding Hooks 
		 *  
		 * @package Auth Armor
		 * @since 1.0.0
		 */
 
		function add_hooks() {
			
			// Create setting page
			add_action( 'admin_menu', array($this,'create_admin_setting_page') );
			// Register Setting
			add_action( 'admin_init', array($this,'autharmor_register_settings') );
		}
	} 
}