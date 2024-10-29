<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Setup wizard Class
 * 
 * Manage Public Panel Class
 *
 * @package Auth Armor
 * @since 1.0.0
 */
if( !class_exists( 'Auth_Armor_Setup_Wizard' ) ) {

	class Auth_Armor_Setup_Wizard {

		public $options;

		//class constructor 
		function __construct() {			
		}

		/**
		 * Create Auth Armor wizard setup in backend
		 */
		public function create_admin_setup_wizard_page(){
			add_menu_page( 
				__( 'Auth Armor Setup Wizard', 'auth-armor' ),
				'Auth Armor Setup',
				'manage_options',
				'autharmor_setup_wizard',
				array($this,'autharmor_setup_wizard_page'),
				'dashicons-admin-users'				
			); 

			add_submenu_page( 
				__( 'Auth Armor Setup Wizard', 'auth-armor' ),
				'Auth Armor Setup', 
				'Auth Armor Setup',
				'manage_options', 
				'autharmor_setup_wizard'
			);
		}

		/**
		 * Auth Armor setting page display from here
		 */
		public function autharmor_setup_wizard_page()
		{
			?>
			<div class='wrap'>  
				<h2><?php esc_html_e('Auth Armor Setup Wizard','auth-armor');?></h2>  
				<span class='skip_setup'><a data-skip="" href="javascript:void(0);" class="setup_skip_wrap"><?php esc_html_e('Skip Setup','auth-armor');?></a></span>	
				<?php settings_errors(); ?>  
				<form method='POST' action='options.php'>
					<?php 
					settings_fields( 'autharmor_wizard_options' );
					do_settings_sections( 'autharmor_setup_wizard' );              				  					
					?>
				</form> 
			</div>
			<?php
		}

		/**
		 * Register and add setting from here
		 */
		public function autharmor_register_setup_wizard() {

			// General Setting
			register_setting( 'autharmor_wizard_options', 'autharmor_wizard_options', '' );
			add_settings_section( 'setup_wizard_settings', '', '', 'autharmor_setup_wizard' );
			
			add_settings_field( 'auth_plugin_setting_enable', esc_html( '', 'auth-armor' ), array($this,'auth_armor_setup_wizard'), 'autharmor_setup_wizard', 'setup_wizard_settings' );
			
			if (isset($_POST['autharmor_wizard_options']['api_key']) && isset($_POST['autharmor_wizard_options']['api_secret'])){
				update_option( "auth_armor_setup_wizard", 0 );	
				wp_redirect( admin_url( 'admin.php?page=autharmor_settings' ) );					
				die;			
			}
		}

		/**
		 * Auth armor setup wizard steps
		 */
		public function auth_armor_setup_wizard() {
			$user_id = get_current_user_id();
			$user_data = get_user_by('id', $user_id );
			$username = '';
			if(!empty($user_data)){
				$username = $user_data->user_login;
			}
            $this->options = get_option( 'autharmor_options' );
            $api_key = $api_secret = '';
            if(isset($this->options['api_key']) && !empty($this->options['api_key'])){
				$api_key = $this->options['api_key'];
			}
			if(isset($this->options['api_secret']) && !empty($this->options['api_secret'])){
				$api_secret = $this->options['api_secret'];
			}
		?>	
			<div class="setup_wizard_form">
				<div class="step-1 step active">
					<label><?php esc_html_e("Download the Auth Armor Authenticator","auth-armor");?></label>
					<div class="sub-step">
						<label><?php esc_html_e("Download the app","auth-armor");?></label>
						<div class="">
							<p><?php esc_html_e("Download the Auth Armor Authenticator App and install on your mobile device. If you already have the app installed, continue to step2. You can load the app using the links below.","auth-armor");?></p>
						</div>
						<div class="step-btn">
							<a href="<?php echo esc_url('https://apps.apple.com/us/app/autharmor-authenticator/id1502837764');?>" target="_blank">
								<img src="<?php echo esc_url(AUTH_ARMOR_INCLUDE_URL.'/images/appstore.svg');?>" alt="<?php esc_html_e('AppStore','auth-armor');?>">
							</a>
							<a href="<?php echo esc_url('https://play.google.com/store/apps/details?id=com.autharmor.authenticator');?>" target="_blank">
								<img src="<?php echo esc_url(AUTH_ARMOR_INCLUDE_URL.'/images/play.svg');?>"  alt="<?php esc_html_e('Playstore','auth-armor');?>">
							</a>
						</div>
					</div>
					<div class="sub-step sub-step-row">
						<div class="sub-step-text-col">
							<label><?php esc_html_e("Create an account","auth-armor");?></label>
							<div class="">
								<p><?php esc_html_e("After you have installed the app, Please create an Auth Armor account. If you already have the app installed, please continue to the next step.","auth-armor");?></p>
							</div>
						</div>
						<div class="sub-step-img-col">
							<video muted autoplay loop>
								<source src="<?php echo esc_url(AUTH_ARMOR_INCLUDE_URL.'/video/create-account-alpha-channel.webm');?>" type="video/webm">
							</video>							
						</div>
					</div>
				</div>
				<div class="step-2 step">
					<label><?php esc_html_e("Setup your Auth Armor developer account","auth-armor");?></label>
					<div class="sub-step">
						<label><?php esc_html_e("Register at Dashboard","auth-armor");?></label>
						<div class="">
							<p><?php esc_html_e("Once app is installed and the account has been created at Auth Armor, head over to ","auth-armor");?><a href="<?php echo esc_url('https://dashboard.autharmor.com');?>" target="_blank"><?php esc_html_e("https://dashboard.autharmor.com","auth-armor");?></a> <?php esc_html_e("and sign in with your newly created account.","auth-armor");?></p>
						</div>								
					</div>
					<div class="sub-step sub-step-row">
						<div class="sub-step-text-col">
							<label><?php esc_html_e("Choose workforce or consumer edition","auth-armor");?></label>
							<div class="">
								<p><?php esc_html_e("During registration, you will be presented with a choice to choose workforce or consumer protection. Once you setup one or the other, you can always configure both at a later step.","auth-armor");?></p>
							</div>
						</div>
						<div class="sub-step-img-col">
							<img src="<?php echo esc_url(AUTH_ARMOR_INCLUDE_URL.'/images/dashboard-step.png');?>">
						</div>
					</div>
					<div class="sub-step sub-step-row">
						<div class="sub-step-text-col">
							<label><?php esc_html_e("Make your first project","auth-armor");?></label>
							<div class="">
								<p><?php esc_html_e("Once you have registered, you will need to create a project. Workforce accounts have a project created by default.","auth-armor");?></p>
							</div>
						</div>
						<div class="sub-step-img-col">
							<img src="<?php echo esc_url(AUTH_ARMOR_INCLUDE_URL.'/images/project-step.png');?>">
						</div>
					</div>
				</div>
				<div class="step-3 step">
					<label><?php esc_html_e("Get API Key","auth-armor");?></label>
					<div class="sub-step sub-step-row">
						<div class="sub-step-text-col">
							<label><?php esc_html_e("Choose workforce or consumer edition","auth-armor");?></label>
							<div class="">
								<p><?php esc_html_e("Generate an API credentials and checkout our api located at","auth-armor");?> <a href="<?php echo esc_url('https://api.authanywhere.autharmor.com');?>" target="_blank"><?php esc_html_e("https://api.authanywhere.autharmor.com","auth-armor");?></a></p>
							</div>
						</div>
						<div class="sub-step-img-col">
							<img src="<?php echo esc_url(AUTH_ARMOR_INCLUDE_URL.'/images/api-step.png');?>">
						</div>
					</div>	
					<div class="sub-step sub-step-form">
						<label><?php esc_html_e("Enter API Key and API Secret key","auth-armor");?></label>
						<p class="verify-details-msg"></p>
						<div class="step-btn-input">
							<label for="auth_plugin_setting_api_key"><?php esc_html_e("API key","auth-armor");?></label>
							<input id="auth_plugin_setting_api_key" name="autharmor_wizard_options[api_key]" type="text" value="<?php esc_html_e($api_key,"auth-armor");?>" />
						</div>
						<div class="step-btn-input">
							<label for="auth_plugin_setting_api_secret"><?php esc_html_e("API Secret key","auth-armor");?></label>
							<input id="auth_plugin_setting_api_secret" name="autharmor_wizard_options[api_secret]" type="text" value="<?php esc_html_e($api_secret,"auth-armor");?>" />
						</div>
						<div class="sub-step">
							<div class="step-btn">
								<input type="button" name="submit" value="Verify API Key" data-method="1" class='verify_details'>
							</div>							
						</div>
					</div>					
				</div>
				<div class="step-4 step" data-user="<?php esc_attr_e($username,"auth-armor");?>">
					<label class="maintitle"><?php esc_html_e("Use the Auth Armor Authenticator app to scan your QR Code then enroll your mobile device.","auth-armor");?></label>
					<div class="sub-step sub-step-row">
						<div class="sub-step-text-col qr-step-msg">
							<input type="hidden" name="hiddenusername" value="<?php esc_attr_e($username,"auth-armor");?>" class="hidden_user_login" id="user_login">	
                            <input type="hidden" name="access_token" value="" class="access_token" id="access_token">	
                            
							<div class="invite_code_form">
								<span class='qrlbl'><?php esc_html_e("Scan me!","auth-armor");?></span>
								<div class="align-center">
									<div id="qr-code-scanner"></div>
								</div>
								<span class='qrdesc'><?php esc_html_e("This QR code is just for you.","auth-armor");?><br/> <?php esc_html_e("Do not share it.","auth-armor");?></span>
								<div id="qr_loader_verification" class="qr_loader_verification" class="align-center">
                                    <img alt="" src="<?php echo esc_url(AUTH_ARMOR_INCLUDE_URL.'/images/loader.gif');?>">
                                </div>
								<div class="resend_link">
									<input type="hidden" data-type="" value="<?php esc_html_e("Revoke and Re-generate :","auth-armor");?>" class="invite-btn-main revoke" id="revoke">
								</div>
							</div>									
						</div>	
						<div class="sub-step-img-col">
							<video muted autoplay loop>
								<source src="<?php echo esc_url(AUTH_ARMOR_INCLUDE_URL.'/video/ScanQRCodeAndEnroll.webm');?>" type="video/webm">
							</video>							
						</div>
						<div class="sub-step-text-col">
							<div class="">
								<p><?php esc_html_e("Follow the instrucations on your device and enroll. Once successfully enrolled, we just need to confirm your devices has been setup correctly.","auth-armor");?></p>
							</div>
						</div>							
					</div>						
				</div>
				<div class="step-5 step">
					<input type="hidden" value="" id="auth_request_id" name="auth_request_id"/>
					<label class="maintitle"><?php esc_html_e("Last step, we need to confirm your device setup.","auth-armor");?></label>
					<div class="qr-error-msg"></div>
					<div class="sub-step sub-step-row"> 
						<div class="sub-step-text-col qr-msg">
							<input type="hidden" name="username" placeholder="<?php esc_html_e("Verify With Username","auth-armor");?>"  value="<?php esc_attr_e($username,"auth-armor");?>" class="user_login">										
							<div class="sub-step">
								<label>
									<?php esc_html_e("We have sent you a push message using Auth Armor app. Once you receive the push message, please approve the request. This will confirm your device setup.","auth-armor");?>
								</label>
							</div>	
							<div class="qr_confirmation_msg"></div>
							<span class='qrdesc commonstep-wrap'>
								<?php esc_html_e("Don't receive the push message? Scan this QR code instead.","auth-armor");?>
							</span>
							<div id="qr_loader" class="align-center commonstep-wrap">
								<img alt="" src="<?php echo esc_url(AUTH_ARMOR_INCLUDE_URL.'/images/loader.gif');?>">
							</div>								
							<div class="invite_code_form commonstep-wrap" style="display:none">									
								<div class="align-center">
									<div id="qr-code"></div>
								</div>										
							</div>
							<div class="sub-step login_url_step" style="display:none;">
								<label>
									<?php esc_html_e("Device Confirmed! You can now login.","auth-armor");?>
								</label>
							</div>				
						</div>		
						<div class="sub-step-img-col">
							<video muted autoplay loop>
								<source src="<?php echo esc_url(AUTH_ARMOR_INCLUDE_URL.'/video/AcceptSetupPush.webm');?>" type="video/webm">
							</video>							
						</div>							
					</div>
				</div>
				<div class="step-btn-bottom-wrap">
					<a href="javascript:void(0);" id="prev" class="prev" name="prev"><?php esc_html_e("Prev","auth-armor");?></a>
					<a href="javascript:void(0);" id="next" class="next" data-page="admin-setup"  name="text"><?php esc_html_e("Next","auth-armor");?></a>
					<p class="submit">
						<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e("Save & Go to Settings","auth-armor");?>">
					</p>
				</div>
		<?php 
		}		

		/**
		 * Adding Hooks 
		 *  
		 * @package Auth Armor
		 * @since 1.0.0
		 */
		function add_hooks() {
			
			// Setup wizard after plugin activate
			add_action( 'admin_menu', array($this,'create_admin_setup_wizard_page') );
			// Register setup wizard
			add_action( 'admin_init', array($this,'autharmor_register_setup_wizard') );
		}
	} 
}