<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Scripting Class
 * Manage Script Panel Class
 * 
 * @package Auth Armor
 * @since 1.0.0
 */
if( !class_exists( 'Auth_Armor_Script' ) ) {

	class Auth_Armor_Script {
		
		var $public;

		//class constructor
		function __construct() {
			global $auth_armor_public;
			$this->public = $auth_armor_public;
		}

		/**
		 * Display js and css in frontend site login page
		 * @package Auth Armor
		 * @since 1.0
		 */
		public function frontend_enqueue_script() {

			$token = $this->public->get_authenticate_token();

			$options = get_option( 'autharmor_options' );
			$timeout = isset($options['api_timeout']) ? $options['api_timeout'] : '120';
			$back_color = isset($options['background_color']) ? $options['background_color'] : '#ffffff';
			$fore_color = isset($options['foreground_color']) ? $options['foreground_color'] : '#000000';
			$localize = array(
				'ajax_url' 		=> admin_url( 'admin-ajax.php', 'relative' ),
				'ajax_nonce' 	=> wp_create_nonce( 'auth-armor-ajax-nonce' ),
				'loader_src' 	=> esc_url( AUTH_ARMOR_INCLUDE_URL . 'images/loader.gif' ),
				'autharmor_logo'=> esc_url( AUTH_ARMOR_INCLUDE_URL . 'images/autharmor.png' ),
				'login_type' 	=> $options['login_type'],
				'token'			=> $token,
				'back_color' 	=> $back_color,
				'fore_color' 	=> $fore_color,
				'timeout' 		=> $timeout,
				'refresh_token'	=> esc_url( AUTH_ARMOR_INCLUDE_URL . 'images/sync-alt-solid.svg' )
			);
			
			wp_register_style( 'auth-armor-login-form-css', AUTH_ARMOR_INCLUDE_URL . 'css/auth-armor-login-form.css', array(), AUTH_ARMOR_VERSION );
			wp_enqueue_style( 'auth-armor-login-form-css' );

			wp_register_script( 'auth-armor-easy-qrcode-min', AUTH_ARMOR_INCLUDE_URL . 'js/easy.qrcode.min.js', array('jquery'), AUTH_ARMOR_VERSION, true );
			wp_enqueue_script( 'auth-armor-easy-qrcode-min');
			
			wp_register_script( 'auth-armor-login-form-js', AUTH_ARMOR_INCLUDE_URL . 'js/auth-armor-login-form.js', array ('jquery'), AUTH_ARMOR_VERSION, true );			
			wp_enqueue_script( 'auth-armor-login-form-js' );
			wp_localize_script( 'auth-armor-login-form-js', 'scanner', $localize);
		}

		/**
		 * Display frontend css and js for invitation setup page
		 * @package Auth Armor
		 * @since 1.0
		 */
		public function frontend_setup_enqueue_script(){
			
			if(isset($_GET['page']) && $_GET['page'] == 'autharmor_setup'){
				$token = $this->public->get_authenticate_token();

				$options = get_option( 'autharmor_options' );
				$timeout = isset($options['api_timeout']) ? $options['api_timeout'] : '120';
				$back_color = isset($options['background_color']) ? $options['background_color'] : '#ffffff';
				$fore_color = isset($options['foreground_color']) ? $options['foreground_color'] : '#000000';
				if(isset($_GET['token_id']) && !empty($_GET['token_id'])){
					$token_id = sanitize_text_field($_GET['token_id']);
					$username = base64_decode($token_id);
					$user = get_user_by('login', $username );
					if(!empty($user)){
						$user_id = $user->ID;
						$autharmor_data = get_user_meta( $user->ID,'autharmor_data',true );
					}
				}
				$qr_code_link = '';
				if(!empty($autharmor_data)){
					$date_expires = date('m-d-Y H:i:s',strtotime($autharmor_data->date_expires));
					$qr_code_link = $autharmor_data->qr_code_data;
				}
				$localize = array(
					'ajax_url' => admin_url('admin-ajax.php', 'relative'),
					'ajax_nonce' => wp_create_nonce('auth-armor-ajax-nonce'),
					'loader_src' => esc_url(AUTH_ARMOR_INCLUDE_URL . 'images/loader.gif'),
					'autharmor_logo' => esc_url(AUTH_ARMOR_INCLUDE_URL . 'images/autharmor.png'),
					'login_type' => $options['login_type'],
					'token'=> $token,
					'back_color' => $back_color,
					'fore_color' => $fore_color,
					'timeout' => $timeout,
					'qr_code_url' => $qr_code_link,
					'refresh_token'=> esc_url(AUTH_ARMOR_INCLUDE_URL . 'images/sync-alt-solid.svg') 
				);
				
				wp_register_style( 'auth-armor-frontend-css', AUTH_ARMOR_INCLUDE_URL . 'css/auth-armor-frontend.css',array(), AUTH_ARMOR_VERSION );		
				wp_enqueue_style( 'auth-armor-frontend-css' );

				wp_register_script( 'auth-armor-easy-qrcode-min', AUTH_ARMOR_INCLUDE_URL . 'js/easy.qrcode.min.js', array('jquery'), AUTH_ARMOR_VERSION, true);
				wp_enqueue_script( 'auth-armor-easy-qrcode-min' );

				wp_register_script( 'auth-armor-common-js', AUTH_ARMOR_INCLUDE_URL . 'js/auth-armor-common.js', array ('jquery'), AUTH_ARMOR_VERSION, true );
				wp_enqueue_script( 'auth-armor-common-js' );
				wp_localize_script( 'auth-armor-common-js', 'scanner', $localize);
			}
		}
		 
		/**
		 * Display js and css in admin backend side
		 * @package Auth Armor
		 * @since 1.0
		 */
		function autharmor_enqueue_admin_script( $hook ) {
			
			$check_page_arr = array('user-new.php','toplevel_page_autharmor_setup_wizard','autharmor-setting_page_autharmor-invite-user','user-edit.php','toplevel_page_autharmor_settings','profile.php');

			if ( !in_array($hook,$check_page_arr)) {
				return;
			}
			$back_color = isset($options['background_color']) ? $options['background_color'] : '#ffffff';
			$fore_color = isset($options['foreground_color']) ? $options['foreground_color'] : '#000000';
			
			wp_enqueue_script( 'auth-armor-admin-script', AUTH_ARMOR_INCLUDE_URL . 'js/autharmor-admin.js', array ('jquery'), AUTH_ARMOR_VERSION, true );
			wp_register_style( 'auth-armor-admin-style', AUTH_ARMOR_INCLUDE_URL . 'css/autharmor-admin.css', array(), AUTH_ARMOR_VERSION );
			wp_enqueue_style( 'auth-armor-admin-style' );
			
			if($hook != 'user-new.php'){

				$qr_code_link = '';
				
				if(isset($_GET['user_id']) && $_GET['user_id'] != ''){
					$user_id = sanitize_text_field($_GET['user_id']);
					$autharmor_data = get_the_author_meta( 'autharmor_data', $user_id );
					if(!empty($autharmor_data) && isset($autharmor_data->qr_code_data)){
						$qr_code_link = $autharmor_data->qr_code_data;
					}
				} else {
					$user_id = get_current_user_id();
					$user_id = sanitize_text_field($user_id);
					$autharmor_data = get_the_author_meta( 'autharmor_data', $user_id );
					if(!empty($autharmor_data) && isset($autharmor_data->qr_code_data)){
						$qr_code_link = $autharmor_data->qr_code_data;
					}
				}
				$token = $this->public->get_authenticate_token();
				$setting_url = esc_url(admin_url( 'admin.php?page=autharmor_settings' ));
				$localize = array(
					'ajax_url' => admin_url('admin-ajax.php', 'relative'),
					'ajax_nonce' => wp_create_nonce('auth-armor-ajax-nonce'),
					'autharmor_logo' => esc_url(AUTH_ARMOR_INCLUDE_URL.'images/autharmor.png'),
					'loader_src' => esc_url(AUTH_ARMOR_INCLUDE_URL.'images/loader.gif'),
					'back_color' => $back_color,
					'fore_color' => $fore_color,
					'qr_code_url' => $qr_code_link,
					'setting_link' => $setting_url,
					'token'=>$token 
				);

				wp_register_script( 'auth-armor-admin-easy-qrcode-min', AUTH_ARMOR_INCLUDE_URL . 'js/easy.qrcode.min.js', array('jquery'), AUTH_ARMOR_VERSION, true);
				wp_enqueue_script('auth-armor-admin-easy-qrcode-min');

				wp_register_script( 'auth-armor-common-js', AUTH_ARMOR_INCLUDE_URL . 'js/auth-armor-common.js', array ('jquery'), AUTH_ARMOR_VERSION, true);
				wp_enqueue_script('auth-armor-common-js');
				wp_localize_script('auth-armor-common-js', 'scanner', $localize);								
				
				wp_localize_script('auth-armor-admin-script', 'adminobj', $localize);
			}
		} 

		/**  
		 * Adding Hooks 
		 * 
		 * @package Auth Armor
		 * @since 1.0
		 */
		function add_hooks() {	
			$options = get_option( 'autharmor_options' );
			if(isset($options['enable'])) {
				// For Frontend Login scripts	
				add_action( 'login_enqueue_scripts', array($this,'frontend_enqueue_script') );
				// For Frontend invitation setup scripts	
				add_action( 'init', array($this,'frontend_setup_enqueue_script') );
			}
			// For Admin scripts
			add_action( 'admin_enqueue_scripts', array($this,'autharmor_enqueue_admin_script') );
		}
	}
}