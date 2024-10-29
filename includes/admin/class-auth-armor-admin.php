<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Admin Class
 * 
 * Manage Admin Panel Class 
 *
 * @package Auth Armor
 * @since 1.0.0
 */
if( !class_exists( 'Auth_Armor_Admin' ) ) {

	class Auth_Armor_Admin {
		
		var $public;

		//class constructor 
		function __construct() {
	    	global $auth_armor_public;
			$this->public = $auth_armor_public;
		}

		/**
		 * Update user profile custom field
		 */
		public function invite_user_fields( $profileuser ) {
			
			$checked0 = '';
			$checked1 = 'checked';
			$login_method = '0';
			if(!empty($profileuser) && isset($profileuser->ID)){
				$user_id = $profileuser->ID;
				$invite_user = get_the_author_meta( 'invite_user', $user_id );
				$login_method = get_user_meta( $user_id,'login_method',true );		
				
				if ($login_method == '0'){
					$checked0 = 'checked';					
					$checked1 = '';
				}
			}
			?>
			<table class="form-table form-table-insert">
				<tr class='login_method login_method_display'>
					<th>
						<label for="Login Type"><?php esc_html_e( 'Login Type', 'auth-armor' ); ?></label>
					</th>
					<td>
						<input type="radio" class='default_pass_login' name="login_method" id="default_login" value="0" <?php esc_attr_e($checked0);?> class="regular-text" />
							<label for="default_login" ><?php esc_html_e( 'Default - Using Password','auth-armor' ); ?> </label>
							<span class='tooltip' title="<?php esc_html_e( 'Default WordPress user create type','auth-armor' ); ?>">?</span>&nbsp;
						<input type="radio" class='default_pass_login' name="login_method" id="autharmor_login" value="1" <?php esc_attr_e($checked1);?> class="regular-text" />
							<label for="autharmor_login"><?php esc_html_e( 'Auth Armor - No Password','auth-armor' ); ?> </label>
							<span class='tooltip' title="<?php esc_html_e( 'It will send invitation to use on email. User can link Auth Armor account from invitation link','auth-armor' ); ?> ">?</span>
					</td>
				</tr>
				<?php if(!empty($profileuser) && isset($profileuser->ID)){?>
				<tr class="autharmor_login" style="<?php echo ($login_method == '1') ? '': 'display:none;';?>">
					<?php
					$autharmor_data = get_the_author_meta( 'autharmor_data', $profileuser->ID );
					if(!empty($autharmor_data)){
					?>
						<th></th>
						<td>
							<?php
								/** If autharmor data exist in edit profile  */							
								$link = 'https://invite.autharmor.com/i='.$autharmor_data->invite_code.'&aa_sig='.$autharmor_data->aa_sig;
								$date_expires = date('m-d-Y H:i:s',strtotime($autharmor_data->date_expires));
								$this->get_autharmor_data($autharmor_data->qr_code_data,$link,$date_expires);   /* Display Qrcode and link in edit profile */
							?>
						</td>
					<?php }?>
				</tr>
				<?php } ?>					
			</table>
			<?php			
		}

		/**
		 * Display qr code and invite link in edit profile
		 */	
		public function get_autharmor_data($invite_code,$link,$date_expires) {
		?>        
			<div class="invite_code_form" style="display:none">
				<div class="invite-msg" style="display:none">
					<div class="check-circle">
						<i class="fa fa-check-circle" aria-hidden="true"></i>
					</div>
					<h2><?php esc_html_e("Invite Created successfully!","auth-armor");?></h2>
					<p>
						<?php esc_html_e("Click to show the QR code. You scan save/send this image. It can then be scanned in the app to invite users.","auth-armor");?>
					</p>
				</div>
				<div class="align-center">
					<div id="qr-code-scanner"></div>
				</div>
				<div class="date_expire">
					<i class="fas fa-clock"></i><?php esc_html_e("Date Expires:","auth-armor");?><b class="expire-date text-justify"><?php esc_html_e($date_expires);?></b>
				</div>
				<div class="invite_link_sec">
					<label><?php esc_html_e("Invite Link :","auth-armor");?></label>
					<input type="text" value="<?php echo esc_url($link);?>" id="invite_link" readonly>
					<button class="copy_invite_link"><?php esc_html_e("Copy","auth-armor");?></button>
				</div>
				<div class="resend_link">
					<label><?php esc_html_e("Revoke and Re-generate :","auth-armor");?></label>
					<input type="button" value="Revoke and Re-generate" data-type="" class="invite-btn-main revoke" id="revoke">
				</div>
			</div>
			<?php
		}
		
		/**
		 * When invite user checked then call auth armor api for invite user
		 */
		public function create_user_invite_code($user_id)
		{
			$access_token = $this->public->get_authenticate_token();

			$reset = $revoke = true;

			$user_info = get_userdata($user_id);

			$send_data = array( 
				'nickname' => $user_info->user_login,
				"full_reset" => $reset,
				"revoke_previous_invites" => $revoke
			);

			$args = array(
				'method' => 'POST',
				'blocking' => true,
				'headers' => array( 
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body' => json_encode ($send_data)
			);
			
			$response = wp_remote_post(  AUTH_ARMOR_API_DOMAIN.'/v2/invite/', $args );
			//echo "<pre>";print_r($response);
			if ( is_wp_error($response) ) {
				$screen = "Admin user profile edit screen";
				add_custom_auth_armor_log("Invite code not generate", $screen, AUTH_ARMOR_API_DOMAIN. '/v2/invite/');
				update_user_meta($user_id, 'autharmor_data_missmatch','1');				
			} else{ 
				$body = json_decode($response['body']);				
				if(isset($body->invite_code)) {
					$screen = "Admin user profile edit screen";
					add_custom_auth_armor_log("Invite code generated successfully", $screen, AUTH_ARMOR_API_DOMAIN. '/v2/invite/');
					$date_expires = date('m-d-Y H:i:s',strtotime($body->date_expires));
					update_user_meta($user_id, 'autharmor_data',$body);
					update_user_meta($user_id, 'autharmor_data_missmatch','0');
				}
			}
		}

		/**
		 * Update user profile for invite user custom fields
		 */
		public function update_invite_user_profile_fields($user_id) {
			
			$login_method = isset($_POST['login_method']) ? sanitize_text_field($_POST['login_method']) : '0';
			if ( current_user_can('edit_user',$user_id) && isset($_POST['login_method']) && $_POST['login_method'] == '1')
			{
				$autharmor_data = get_the_author_meta( 'autharmor_data', $user_id );
				if((isset($_POST['action']) && $_POST['action'] == 'createuser') || empty($autharmor_data)){   /* Invite user only add user action or update user who have no autharmor data */					
					$this->create_user_invite_code($user_id);
				}	
				/** Send notification only in add user  */
				if(isset($_POST['action']) && $_POST['action'] == 'createuser' || empty($autharmor_data)){
					$this->sendInvitationEmail($user_id);					
				}
				update_user_meta($user_id, 'invite_user','1');
			}
			update_user_meta($user_id, 'login_method',$login_method);

		}
		   
		/**
		 * Add invite status and auth armor status label as custom column in user atble
		 */
		public function add_invite_status_column( $column ) {
			$column['autharmor_status'] = esc_html("Auth Armor Status","auth-armor");
			return $column;
		}

		/**
		 * Display custom column value in user table
		 */
		public function display_invite_status_in_table( $val, $column_name, $user_id ) {
			?>
			<style>
				.active , .invite_status{
					color:green;
				}
				.inactive{
					color:red;
				}
			</style>
			<?php
			switch ($column_name) {
				case 'autharmor_status' :
					$autharmor_status = '--';
					$autharmor_data = get_user_meta( $user_id,'autharmor_data',true );
					if(!empty($autharmor_data) && isset($autharmor_data->invite_code) && !empty($autharmor_data->invite_code)){
						$autharmor_status = get_user_meta( $user_id,'autharmor_status',true );
						if($autharmor_status == '1')
							$autharmor_status = '<span class="active">'.esc_html("Active","auth-armor").'</span>';
						else
							$autharmor_status = '<span class="inactive">'.esc_html("InActive","auth-armor").'</span><br/><span class="invite_status">'.esc_html("Invitation Sent","auth-armor").'</span>';
					}
					return $autharmor_status;	
				default:
			}
			return $val;
		}

		/**
		 * this function used for where user is accepted request or not 
		 */
		public function check_user_autharmor_status($user_id){
			
			$access_token = $this->public->get_authenticate_token();
			$options = get_option( 'autharmor_options' );
			$timeout = isset($options['api_timeout']) ? $options['api_timeout'] : '120';
			if($access_token ) {
				$nicknameArr =  get_userdata( $user_id );
				$nickname = isset($nicknameArr->user_login) ? $nicknameArr->user_login : '';
				$args = array(
					'method' => 'POST',
					'blocking' => true,
					'headers' => array( 
						'Authorization' => 'Bearer ' . $access_token,
						'Content-Type' => 'application/json; charset=utf-8',
					),
					'body' => json_encode (array( 
						"action_name"=> "Login",
						'nickname' => $nickname,
						"short_msg"=> "Please authorize",
						"nonce"=> "login_via_nickname",  
						"timeout_in_seconds"=> $timeout
					))
				);	
				$response = wp_remote_post( AUTH_ARMOR_API_DOMAIN.'/v2/auth/request/async/', $args );
					
				if(isset($response['body'])){			
					$body = json_decode($response['body']);					
					if(!empty($body)){
						if(isset($body->errorMessage) && $body->errorCode == '400'){
							return array('success' => '0','msg' => $body->errorMessage);
						} else if(isset($body->response_code) && $body->response_code == '200') {
							return array('success' => '1','qr_code'=>$body->qr_code_data,'auth_request_id'=>$body->auth_request_id,'access_token'=>$access_token);
						}
					}
				}
				return false;
			}						
		}

		/**
		 * Disable default registration notification if login type is auth armor
		 */
		public function auth_armor_disable_user_notifications(){
			if ( isset($_POST['login_method']) && $_POST['login_method'] == '1') {
				remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
				remove_action( 'edit_user_created_user', 'wp_send_new_user_notifications', 10, 2 );	
			}
		}

		/**
		 * New invitation email for auth armor user
		 */
		public function sendInvitationEmail($user_id){
			
			$user = get_userdata( $user_id );
	
			$screen = "Admin invitation email ";
			add_custom_auth_armor_log("Send invitation email to user id : ".$user_id, $screen);

			// The blogname option is escaped with esc_html() on the way into the database in sanitize_option().
			// We want to reverse this for the plain text arena of emails.
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$message = sprintf( __( 'Thank you for registration to autharmor' )) . "\r\n\r\n";
			/* translators: %s: User login. */
			$message .= sprintf( __( 'Username: %s' ), $user->user_login ) . "\r\n\r\n";
			/* translators: %s: User email address. */
			$message .= sprintf( __( 'Email: %s' ), $user->user_email ) . "\r\n\r\n";

			$message .= __( 'To Login, follow below steps:' ) . "\r\n\r\n";

			$token = base64_encode($user->user_login);
			$message .= network_site_url( "?page=autharmor_setup&token_id=".$token, 'login' ) . "\r\n\r\n";
	
			$wp_new_user_notification_email = array(
				'to'      => $user->user_email,
				/* translators: Login details notification email subject. %s: Site title. */
				'subject' => __( '[%s] Invitation Details' ),
				'message' => $message,
				'headers' => '',
			);
	
			wp_mail(
				$wp_new_user_notification_email['to'],
				wp_specialchars_decode( sprintf( $wp_new_user_notification_email['subject'], $blogname ) ),
				$wp_new_user_notification_email['message'],
				$wp_new_user_notification_email['headers']
			);	
		}

		/**
		 * Adding Hooks 
		 *  
		 * @package Auth Armor
		 * @since 1.0.0
		 */
		function add_hooks() {
			
			// Disable user notification email if user register with auth armor
			add_action( 'init',array($this, 'auth_armor_disable_user_notifications' ));
			// Add custom auth armor column
			add_filter( 'manage_users_columns', array($this,'add_invite_status_column') );
			// Display data for custom column
			add_filter( 'manage_users_custom_column', array($this,'display_invite_status_in_table'), 10, 3 );

			// Add/Edit user profile show login type with data
			add_action( 'show_user_profile', array($this,'invite_user_fields') );
			add_action( 'edit_user_profile', array($this,'invite_user_fields') );
			add_action( "user_new_form", array($this,"invite_user_fields" ));  // Add user show invite status

			// Update new added column data on register and update user
			add_action( 'user_register', array($this,'update_invite_user_profile_fields'));  // Add user Save invite status fields
			add_action( 'edit_user_profile_update', array($this,'update_invite_user_profile_fields'));
			add_action( 'profile_update',array($this, 'update_invite_user_profile_fields'), 10, 2 );   /* login user profile update */

		}
	}
} 