<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Public Class
 * Manage Public Panel Class
 *
 * @package 
 * @since 1.0.0
 */
if( !class_exists( 'Auth_Armor_Public' ) ) {

	class Auth_Armor_Public {

		var $options;

		//class constructor 
		function __construct() {
			$this->options = get_option( 'autharmor_options' );
		}

		/**
		 * Get authentication token from auth armor
		 */
		public function get_authenticate_token() {

			if(isset($_POST['api_key']) && !empty($_POST['api_key'])){
				$api_key = sanitize_text_field($_POST['api_key']);
			}else if(isset($this->options['api_key']) && !empty($this->options['api_key'])){
				$api_key = $this->options['api_key'];
			}
			if(isset($_POST['api_secret']) && !empty($_POST['api_secret'])){
				$api_secret = sanitize_text_field($_POST['api_secret']);
			}else if(isset($this->options['api_secret']) && !empty($this->options['api_secret'])){
				$api_secret = $this->options['api_secret'];
			}
			$output = array();
			$json = isset($_POST['jsondata']) ? sanitize_text_field($_POST['jsondata']) : '0';
			$method = isset($_POST['method']) ? sanitize_text_field($_POST['method']) : '0';
			if(!empty($api_key) && !empty($api_secret)) {
				$bearer_token_credential = $api_key . ':' . $api_secret;
				$credentials = base64_encode($bearer_token_credential);
				 
				$args = array(
					'method' => 'POST',
					'blocking' => true,
					'headers' => array( 
						'Authorization' => 'Basic ' . $credentials,
						'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'
					),
					'body' => array( 'grant_type' => 'client_credentials' )
				);		
				$response = wp_remote_post( AUTH_ARMOR_LOGIN_API_DOMAIN . '/connect/token', $args );
				//echo "<pre>";print_r($response);die;
				if ( is_wp_error($response) ) {
					$access_token = false;
					$output['success'] = 0;
					$output['access_token'] = '';
					$screen = " Setup wizard page ";
					if(empty($method)){
						$screen = " Setting page ";
					}
					add_custom_auth_armor_log('Access token not generated',$screen, AUTH_ARMOR_LOGIN_API_DOMAIN . '/connect/token');
				} else{ 
					if(isset($response['body']) && !empty($response['body'])){
						$body = json_decode($response['body']);
						$access_token =  $body->access_token;
						$output['success'] = '1';
						$output['access_token'] = $access_token;
						$screen = " Setup wizard page ";
						if(empty($method)){
							$screen = " Setting page ";
						}
						add_custom_auth_armor_log('Access token generated',$screen, AUTH_ARMOR_LOGIN_API_DOMAIN . '/connect/token');
						if(!empty($method)){
							if(!empty($this->options)){   /* update autharmor option with default setting */
								$autharmor_options = array(
									'enable' => isset($this->options['enable']) ? $this->options['enable'] : '0',
									'api_key' => $api_key,
									'api_secret' => $api_secret,
									'api_timeout' => isset($this->options['api_timeout']) ? $this->options['api_timeout'] : 60,
									'login_type' => isset($this->options['login_type']) ? $this->options['login_type'] : '1',
									'background_color' => isset($this->options['background_color']) ? $this->options['background_color'] : '#ffffff',
									'foreground_color' => isset($this->options['foreground_color']) ? $this->options['foreground_color'] : '#000000',
								);								
							} else {   /* if any setting not exist then update apikey and secret key from setup */
								$autharmor_options = array(
									'api_key' => $api_key,
									'api_secret' => $api_secret,
								);
							}
							update_option( "autharmor_options", $autharmor_options );
						}
					} else {
						$screen = " Setup wizard page ";
						if(empty($method)){
							$screen = " Setting page ";
						}
						add_custom_auth_armor_log('Access token not generated',$screen, AUTH_ARMOR_LOGIN_API_DOMAIN . '/connect/token');
						$access_token =  false;
						$output['success'] = '0';
						$output['access_token'] = '';
					}
				}
				if(!empty($json)){
					echo json_encode($output);
					die;
				} else {
					return $access_token;
				}
			}
		}

		/**
		 * Return ip address
		 */
		public function get_current_ip(){			
			$ipaddress = '';
			if ($_SERVER['HTTP_CLIENT_IP'])
				$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
			else if($_SERVER['HTTP_X_FORWARDED_FOR'])
				$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
			else if($_SERVER['HTTP_X_FORWARDED'])
				$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
			else if($_SERVER['HTTP_FORWARDED_FOR'])
				$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
			else if($_SERVER['HTTP_FORWARDED'])
				$ipaddress = $_SERVER['HTTP_FORWARDED'];
			else if($_SERVER['REMOTE_ADDR'])
				$ipaddress = $_SERVER['REMOTE_ADDR'];
			return $ipaddress; 			
		}

		/**
		 * Get current ip address
		 */
		public function get_current_lat_long(){
			
			$current_ip = $this->get_current_ip();
			$new_arr = array();
			if(!empty($current_ip)){
				$new_arr = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip='.$current_ip));
			}
			return $new_arr;
		}
		/**
		 * Get Qr code data and request id 
		 */
		public function get_auth_request_data() {

			$access_token = sanitize_text_field($_POST['access_token']);
			$options = get_option( 'autharmor_options' );
			$timeout = isset($options['api_timeout']) ? $options['api_timeout'] : '120';
			$short_msg = "Login pending at ".esc_url(site_url());
			$action_name = "Login";
			if(isset($_GET) && isset($_GET['page']) && ($_GET['page'] == 'autharmor_setup' || $_GET['page'] == 'autharmor_setup_wizard')){
				$short_msg = "Please confirm your device is setup correctly.";
				$action_name = "Confirm Setup";				
			}

			if($access_token ) {
				$latLong = $this->get_current_lat_long();	
				$args = array(
					'method' => 'POST',
					'blocking' => true,
					'headers' => array( 
						'Authorization' => 'Bearer ' . $access_token,
						'Content-Type' => 'application/json; charset=utf-8',
					),
					'body' => json_encode (array( 
						"action_name"=> $action_name,
						"short_msg"=> $short_msg,
						"nonce"=> "login_via_qrcode",  
						"origin_location_data" =>
						array(
							"latitude" => isset($latLong['geoplugin_latitude']) ? $latLong['geoplugin_latitude'] : '',
							"longitude" => isset($latLong['geoplugin_longitude']) ? $latLong['geoplugin_longitude'] : '',
						),
						"timeout_in_seconds"=> $timeout
					))
				);		
				$response = wp_remote_post( AUTH_ARMOR_API_DOMAIN . '/v2/auth/request/async', $args );
				
				if ( is_wp_error($response) ) {
					$msg = "Error in generation of qr code";
					add_custom_auth_armor_log($msg, $action_name, AUTH_ARMOR_API_DOMAIN . '/v2/auth/request/async');
					echo $qr_code_data = json_encode(array('success' => '0'));
					die;
				} else{ 
					if(isset($response['body']) && !empty($response['body'])){
						$body = json_decode($response['body']);		
						add_custom_auth_armor_log($short_msg, $action_name, AUTH_ARMOR_API_DOMAIN . '/v2/auth/request/async');		 
						echo $qr_code_data = json_encode(array('success' => '1','timeout' => $timeout,'qr_code'=>$body->qr_code_data,'auth_request_id'=>$body->auth_request_id,'access_token'=>$access_token));
						die;
					} else if(isset($response['response']['code']) && $response['response']['code'] == '401') {
						$msg = "Error in generation of qr code, Verify API key and secret API key";
						add_custom_auth_armor_log($msg, $action_name, AUTH_ARMOR_API_DOMAIN . '/v2/auth/request/async');
						echo json_encode(array('success'=>'0','msg'=>'Please authorized, Verify API key and secret API key'));
						die;
					}
				}
			}			
		}

		/**
		 * Check login state of auth armor using request id
		 */
		public function get_token_state() {	
			
			if(!isset($_POST['auth_request_id'])) {
				echo json_encode(array('status'=>'fail','msg'=> 'empty auth_request_id'));
				die;				
			}

			$auth_request_id = sanitize_text_field($_POST['auth_request_id']);
			$access_token = sanitize_text_field($_POST['access_token']);
			$is_front = isset($_POST['is_front']) ? sanitize_text_field($_POST['is_front']) : '0';
			$options = get_option( 'autharmor_options' );
			$timeout = isset($options['api_timeout']) ? $options['api_timeout'] : '120';
			if($access_token ) {
				
				$args = array(
					'method' => 'GET',
					'blocking' => true,
					'headers' => array( 
						'Authorization' => 'Bearer ' . $access_token,
						'Content-Type' => 'application/json; charset=utf-8',
					)
				);
	
				$response = wp_remote_post( AUTH_ARMOR_API_DOMAIN.'/v2/auth/request/'.$auth_request_id, $args );
				if ( is_wp_error($response) ) {
					$msg = " Auth request id match failed ";	
					add_custom_auth_armor_log($msg," Token state ", AUTH_ARMOR_API_DOMAIN.'/v2/auth/request/'.$auth_request_id);		
					echo json_encode(array('status'=>'fail','msg'=> '','timeout' => $timeout));
					die;
				} else {
					
					$body = json_decode($response['body']);

					if($body->auth_response->response_code == 2 && $body->auth_response->authorized == 1) {	
						$nickname = $body->auth_response->auth_details->request_details->auth_profile_details->nickname;
						if(!empty($is_front)){
							$msg = " Your Acccount Verify Successfully ";	
							add_custom_auth_armor_log($msg," Setpup wizard ", AUTH_ARMOR_API_DOMAIN.'/v2/auth/request/'.$auth_request_id);		
							update_option( "auth_armor_setup_wizard_confirm", 1 );
							echo json_encode(array('status'=>'success','msg' => 'Your Acccount Verify Successfully'));
							die;
						} else {
							$admin_login = $this->get_login($nickname);
							if($admin_login == 'success') {
								$msg = " Login Successfully ";	
								add_custom_auth_armor_log($msg," login screen ", AUTH_ARMOR_API_DOMAIN.'/v2/auth/request/'.$auth_request_id);		
								echo json_encode(array('status'=>'success','url'=> get_site_url().'/wp-admin'));
								die;
							}
						}
					}
					else if($body->auth_response->response_code == 5) {
						$msg = " Login Request Timeout ";	
						$screen = "login screen";
						if(!empty($is_front)){
							$msg = " Setup wizard Request Timeout ";	
							$screen = "Setup wizard screen";
						}
						add_custom_auth_armor_log($msg, $screen, AUTH_ARMOR_API_DOMAIN.'/v2/auth/request/'.$auth_request_id);
						echo json_encode(array('status'=>'timeout','msg'=> 'Login Request Timeout'));
						die;	
					}
					else if($body->auth_response->response_code == 3) {
						$msg = " Login Request Declined ";	
						$screen = "login screen";
						if(!empty($is_front)){
							$msg = " Setup wizard Request Declined ";	
							$screen = "Setup wizard screen";
						}
						add_custom_auth_armor_log($msg, $screen, AUTH_ARMOR_API_DOMAIN.'/v2/auth/request/'.$auth_request_id);
						echo json_encode(array('status'=>'declined','msg'=> 'Login Request Declined'));
						die;					
					}
				}
			}
			echo json_encode(array('status'=>'fail','msg'=> '','timeout' => $timeout));
			die;
		}

		/**
		 * Login using username
		 */
		public function get_login($username) {

			$user = get_user_by('login', $username );
			
			if ( !is_wp_error( $user ) ) {	
				// wp_set_current_user ( $user->ID,$username ); 
				// wp_set_auth_cookie  ( $user->ID, true, false ); 
				// update_user_meta($user->ID, 'autharmor_status','1');   /* set autharmor status active */
				// do_action('wp_login', $username,$user);	
				wp_clear_auth_cookie();	
				wp_set_auth_cookie( $user->ID);
				update_user_meta($user->ID, 'autharmor_status','1');   /* set autharmor status active */
				do_action('wp_signon', $user->user_login);				
				$message = "success";
			} else {
				$message = "fail";
			}

			return $message;
		}

		/**
		 * Login via username in auth armor
		 */
		public function get_login_via_username() {

			if(!isset($_POST['nickname']) || empty($_POST['nickname'])) {
				echo json_encode(array('status'=>'fail','msg'=>'nickname should not empty'));
				die;				
			}

			if(empty($_POST['access_token'])) {
				echo json_encode(array('status'=>'fail','msg'=>'access_token should not empty'));
				die;				
			}
			$short_msg = "Login pending at ".esc_url(site_url());
			$action_name = "Login";
			$setup = false;
			if(isset($_POST) && isset($_POST['method']) && $_POST['method'] == 'setup'){
				$short_msg = "Please confirm your device is setup correctly.";
				$action_name = "Confirm Setup";
				$setup = true;
			}
			$latLong = $this->get_current_lat_long();
			$access_token = sanitize_text_field($_POST['access_token']);
			$timeout = isset($options['api_timeout']) ? $options['api_timeout'] : '120';
			$args = array(
				'method' => 'POST',
				'blocking' => true,
				'headers' => array( 
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body' => json_encode (array( 
					"action_name"=> $action_name,
					"short_msg"=> $short_msg,
					"nonce"=> "login_via_nickname",  
					"send_push" => true,
					"origin_location_data" =>
					array(
						"latitude" => isset($latLong['geoplugin_latitude']) ? $latLong['geoplugin_latitude'] : '',
						"longitude" => isset($latLong['geoplugin_longitude']) ? $latLong['geoplugin_longitude'] : '',
					),
					"timeout_in_seconds"=> $timeout,
					'nickname' => sanitize_text_field($_POST['nickname'])
				))
			);
			
			$response = wp_remote_post( AUTH_ARMOR_API_DOMAIN . '/v2/auth/request/async', $args );
			if ( is_wp_error($response) ) {
				$screen = "Login screen ";
				if($setup){
					$screen = " Setup wizard page ";					
				}
				$msg = "Username is does not exist.";					
				add_custom_auth_armor_log($msg,$screen, AUTH_ARMOR_API_DOMAIN . '/v2/auth/request/async');
				echo json_encode(array('status'=>'fail','msg'=> 'Please try again, Username is does not exist.','timeout' => $timeout));
				die;
			} else {
				if(isset($response['body']) && !empty($response['body'])){
					$body = json_decode($response['body']);	
					$screen = "Login screen ";
					$msg = "Login pending at ".esc_url(site_url());
					if($setup){
						$screen = " Setup wizard page ";
						$msg = "Please confirm your device is setup correctly";					
					}
					add_custom_auth_armor_log($msg,$screen, AUTH_ARMOR_API_DOMAIN . '/v2/auth/request/async');		
					echo $qr_code_data = json_encode(array('status'=>'success','qr_code'=>$body->qr_code_data,'auth_request_id'=>$body->auth_request_id,'access_token'=>$access_token));
					die;
				} else if(isset($response['response']['code']) && $response['response']['code'] == '401') {
					$screen = "Login screen ";
					if($setup){
						$screen = " Setup wizard page ";						
					}
					$msg = "Please authorized, Verify API key and secret API key";					
					add_custom_auth_armor_log($msg,$screen, AUTH_ARMOR_API_DOMAIN . '/v2/auth/request/async');	
					echo json_encode(array('status'=>'0','msg'=>'Please authorized, Verify API key and secret API key'));
					die;
				}
			}			
		}

		/**
		 * Create user invitation QR code and invitation link
		 */
		public function generate_user_invite_code() {

			if(!isset($_POST['nickname']) || empty($_POST['nickname'])) {
				echo json_encode(array('status'=>'fail','msg'=>'nickname should not empty'));
				die;
			}

			if(empty($_POST['access_token'])) {
				echo json_encode(array('status'=>'fail','msg'=>'access_token should not empty'));
				die;				
			}

			$access_token = sanitize_text_field($_POST['access_token']);

			$reset = $revoke = false;

			if(isset($_POST['type'])) {
				$post_type = sanitize_text_field($_POST['type']);
				$reset = ($post_type == 'reset') ? true : false;
			}

			if(isset($_POST['type'])) {	
				$reset = true;
				$post_type = sanitize_text_field($_POST['type']);
				$revoke = ($post_type == 'revoke') ? true : false;
			}
			$user = get_user_by( 'login', sanitize_text_field($_POST['nickname']) );
			$user_id = $user->ID;
			$send_data = array( 
				'nickname' => sanitize_text_field($_POST['nickname']),
				//"full_reset" => $reset,
				"reset_and_reinvite" => $reset,
				"revoke_previous_invites" => $revoke
			);

			if(!empty($_POST['reference_id'])) {
				$send_data['reference_id'] = sanitize_text_field($_POST['reference_id']);
			}  

			$args = array(
				'method' => 'POST',
				'blocking' => true,
				'headers' => array( 
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body' => json_encode ($send_data)
			);
			
			$response = wp_remote_post( AUTH_ARMOR_API_DOMAIN.'/v2/invite/', $args );
			if ( is_wp_error($response) ) {				
				$msg = "Invite code not generate successfully!";	
				add_custom_auth_armor_log($msg,"Invite code", AUTH_ARMOR_API_DOMAIN.'/v2/invite/');								
				echo json_encode(array('status'=>'fail','msg'=>'Please try again.'));
				die;
			} else {
				if(isset($response['body']) && !empty($response['body'])){
					$body = json_decode($response['body']);
					if(isset($body->errorMessage) && !empty($body->errorMessage)) {	
						$msg = "Please try again!";	
						add_custom_auth_armor_log($msg, 'Invite code', AUTH_ARMOR_API_DOMAIN.'/v2/invite/');						
						echo json_encode(array('status'=>'fail','msg'=>$body->errorMessage));
						die;					
					}
					if(isset($body->invite_code)) {
						$date_expires = date('m-d-Y H:i:s',strtotime($body->date_expires));
						update_user_meta($user_id, 'autharmor_data',$body);
						$msg = "Invite code generate successfully!";	
						add_custom_auth_armor_log($msg,"Invite code", AUTH_ARMOR_API_DOMAIN.'/v2/invite/');			
						echo json_encode(array('status'=>'success','body'=>$body,'date_expires'=>$date_expires));
						die;
					}		
				} else if(isset($response['response']['code']) && $response['response']['code'] == '401') {
					$msg = "Please authorized, Verify API key and secret API key!";	
					add_custom_auth_armor_log($msg, 'Invite code', AUTH_ARMOR_API_DOMAIN.'/v2/invite/');	
					echo json_encode(array('status'=>'fail','msg'=>'Please authorized, Verify API key and secret API key'));
					die;
				}
			}					
		}

		/**
		 * Frontend Page for after email send for auth armor login
		 */
		public function autharmor_register_setup() {
			
			if(isset($_GET['page']) && $_GET['page'] == 'autharmor_setup'){
				$autharmor_data = array();
				$qr_code_link = $date_expires = '';
				if(isset($_GET['token_id']) && !empty($_GET['token_id'])){
					$username = base64_decode(sanitize_text_field($_GET['token_id']));
					$user = get_user_by('login', $username );
					if(!empty($user)){
						$user_id = $user->ID;
						$autharmor_data = get_user_meta( $user->ID,'autharmor_data',true );
					}
				}
				if(!empty($autharmor_data)){
					$date_expires = date('m-d-Y H:i:s',strtotime($autharmor_data->date_expires));
					$qr_code_link = $autharmor_data->qr_code_data;
				}
				?>
				<div class="setup_wizard_form setup_form">
					<div class="step-1 step active">
						<label class='maintitle'><?php esc_html_e("Your account hase been configured to use login without password!","auth-armor");?></label>
						<label class="subtitle"><?php esc_html_e("Please follow the steps below to configure your mobile device.","auth-armor");?></label>
				     	
						<div class="sub-step sub-step-row">
							<div class="sub-step-text-col">
							<div class="sub-step">
								<label class="step-text-new"><?php esc_html_e("Step 1","auth-armor");?></label>
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
							<div class="sub-step">
								<label class="step-text-new"><?php esc_html_e("Step 2","auth-armor");?></label>
								<div class="">
									<p><?php esc_html_e("After you have installed the app, Please create an Auth Armor account. 
									If you already have the app installed, please continue to the next step.","auth-armor");?></p>
								</div>
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
						<label class="maintitle"><?php esc_html_e("Use the Auth Armor Authenticator app to scan your QR Code then enroll your mobile device.","auth-armor");?></label>
						<div class="sub-step sub-step-row">
							<div class="sub-step-text-col qr-step-msg">
								<input type="hidden" name="hiddenusername" value="<?php esc_attr_e($username,"auth-armor");?>" class="hidden_user_login" id="user_login">	
								<div class="invite_code_form" style="display:none">
									<span class='qrlbl'><?php esc_html_e("Scan me!","auth-armor");?></span>
									<div class="align-center">
										<div id="qr-code-scanner"></div>
									</div>
									<div id="qr_loader_verification" class="qr_loader_verification" class="align-center">
										<img alt="" src="<?php echo esc_url(AUTH_ARMOR_INCLUDE_URL.'/images/loader.gif');?>">
									</div>
									<span class='qrdesc'><?php esc_html_e("This QR code is just for you.","auth-armor");?><br/> <?php esc_html_e("Do not share it.","auth-armor");?></span>
									<input type="hidden"  data-type="" value="<?php esc_html_e("Revoke and Re-generate :","auth-armor");?>" class="invite-btn-main revoke" id="revoke">
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
					<div class="step-3 step">
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
						<a href="javascript:void(0);" id="next" class="next" data-page="front-setup"  name="text"><?php esc_html_e("Next","auth-armor");?></a>					
						<a href="<?php echo esc_url(wp_login_url());?>" style="display:none;" id="proceed_login" class="proceed_login" name="proceed_login"><?php esc_html_e("Proceed to login","auth-armor");?></a>					
					</div>
				<?php								
			}
		}
		
		/**
		 * Update reset password message for auth armor user
		 */
		public function update_reset_password_msg($message, $key, $user_login, $user_data ){
			$login_method = get_user_meta( $user_data->ID,'login_method',true );		
				
			if(!empty($login_method)){
				$msg = " Reset password se by ".$user_data->ID;	
				add_custom_auth_armor_log($msg," Reset password ");			
				$locale = get_user_locale( $user_data );

				$switched_locale = switch_to_locale( $locale );

				if ( is_multisite() ) {
					$site_name = get_network()->site_name;
				} else {
					/*
					* The blogname option is escaped with esc_html on the way into the database
					* in sanitize_option. We want to reverse this for the plain text arena of emails.
					*/
					$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
				}
				$message = __( 'Someone has requested a reset for the following account:' ) . "\r\n\r\n";
				/* translators: %s: User login. */
				$message .= sprintf( __( 'Site Name: %s' ), $site_name ) . "\r\n\r\n";
				$message .= sprintf( __( 'Username: %s' ), $user_login ) . "\r\n\r\n";
				$message .= __( 'If this was a mistake, ignore this email and nothing will happen.' ) . "\r\n\r\n";
				$message .= __( 'To reset your account, visit the following address:' ) . "\r\n\r\n";
				$token = base64_encode($user_login);
				$message .= network_site_url( "?page=autharmor_setup&token_id=".$token, 'login' ) . "\r\n\r\n";								
			}
			return $message;
		}

		/**
		 * Update retrieve password subject for auth armor user
		 */
		function autharmor_update_retrieve_password_subject($title, $user_login, $user_data){
			$login_method = get_user_meta( $user_data->ID,'login_method',true );		
				
			if(!empty($login_method)){
				if ( is_multisite() ) {
					$site_name = get_network()->site_name;
				} else {
					/*
					* The blogname option is escaped with esc_html on the way into the database
					* in sanitize_option. We want to reverse this for the plain text arena of emails.
					*/
					$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
				}
				$title = sprintf( __( '[%s] Reset Your Account' ), $site_name );
			}
			return $title;
		}

		/**
		 * Update lost you password text from login page
		 */
		public function autharmor_change_lost_pass_text( $text ) {
			
			if ( 'Lost your password?' === $text ) {
				$text = 'Having trouble accessing your account?';
			}
			if($text === 'Please enter your username or email address. You will receive an email message with instructions on how to reset your password.'){
				$text = 'Please enter your username or email address. You will receive an email message with instructions on how to reset your account.';
			}
			return $text;
		}

		/**
		 * Adding Hooks 
		 *  
		 * @package Auth Armor
		 * @since 1.0.0
		 */
 
		function add_hooks() {
			
			if(isset($_GET['page']) && $_GET['page'] == 'autharmor_setup') {
				add_action( 'wp_head',  array($this,'autharmor_register_setup') );
				// Add class in body
				add_filter( 'body_class', function( $classes ) {
					return array_merge( $classes, array( 'auth-armor-setup' ) );
				} );
			}
			// Request for get request id and qr code link
			add_action('wp_ajax_get_auth_request_data', array($this,'get_auth_request_data'));
			add_action('wp_ajax_nopriv_get_auth_request_data', array($this,'get_auth_request_data'));

			// Check status of user login with auth armor or not
			add_action('wp_ajax_get_token_state', array($this,'get_token_state'));
			add_action('wp_ajax_nopriv_get_token_state', array($this,'get_token_state'));

			// Login with username in auth armor
			add_action('wp_ajax_get_login_via_username', array($this,'get_login_via_username'));
			add_action('wp_ajax_nopriv_get_login_via_username', array($this,'get_login_via_username'));

			// Invite user with qr code and invite link
			add_action('wp_ajax_generate_user_invite_code', array($this,'generate_user_invite_code'));
			add_action('wp_ajax_nopriv_generate_user_invite_code', array($this,'generate_user_invite_code'));

			// Verify detils and get accesstoken 
			add_action('wp_ajax_verify_api_details', array($this,'get_authenticate_token'));
			add_action('wp_ajax_nopriv_verify_api_details', array($this,'get_authenticate_token'));			

			// Reset password email for autharmor user
			add_filter('retrieve_password_message',array($this,'update_reset_password_msg'),10,4);

			// Update retrieve password subject
			add_filter('retrieve_password_title', array($this,'autharmor_update_retrieve_password_subject'), 10, 3);

			// Hook this function up.
			add_action( 'gettext',array($this,'autharmor_change_lost_pass_text'));
		}
	} 
}