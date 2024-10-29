<?php
/**
 * Plugin Name: Auth Armor – Passwordless Login
 * Description: Login using your phone without passwords! More secure, faster and best of all, nothing to remember or type in!
 * Version: 1.0.3
 * Text Domain: auth-armor
 * Domain Path: languages 
 * Author: Auth Armor Technologies, Inc
 * Author URI: https://www.autharmor.com
 * 
 * @package 
 * @category Core 
 * @author 
 */

// Exit if accessed directly 
if( !defined( 'ABSPATH' ) ) exit; 

/**
 * Basic plugin definitions 
 * 
 * @package 
 * @since 1.0.0
 */
if( !defined( 'AUTH_ARMOR_VERSION' ) ) {
	define( 'AUTH_ARMOR_VERSION', '1.0.3' ); // version of plugin
}
if( !defined( 'AUTH_ARMOR_DIR' ) ) {
	define( 'AUTH_ARMOR_DIR', dirname(__FILE__) ); // plugin dir
}
if( !defined( 'AUTH_ARMOR_PLUGIN_BASENAME' ) ) {
	define( 'AUTH_ARMOR_PLUGIN_BASENAME', basename( AUTH_ARMOR_DIR ) ); //Plugin base name
}
if( !defined( 'AUTH_ARMOR_URL' ) ) {
	define( 'AUTH_ARMOR_URL', plugin_dir_url(__FILE__) ); // plugin url
}
if( !defined( 'AUTH_ARMOR_INCLUDE_DIR' ) ) {
	define( 'AUTH_ARMOR_INCLUDE_DIR', AUTH_ARMOR_DIR . '/includes/' ); // plugin admin dir
}
if( !defined( 'AUTH_ARMOR_INCLUDE_URL' ) ) {
	define( 'AUTH_ARMOR_INCLUDE_URL', AUTH_ARMOR_URL . 'includes/' ); // plugin include url
}
if( !defined( 'AUTH_ARMOR_ADMIN_DIR' ) ) {
	define( 'AUTH_ARMOR_ADMIN_DIR', AUTH_ARMOR_DIR . '/includes/admin' ); // plugin admin dir 
}
if( !defined( 'AUTH_ARMOR_LOGIN_API_DOMAIN' ) ) {
	define( 'AUTH_ARMOR_LOGIN_API_DOMAIN', 'https://login.autharmor.com' ); // Auth armor login api path
}
if( !defined( 'AUTH_ARMOR_API_DOMAIN' ) ) {
	define( 'AUTH_ARMOR_API_DOMAIN', 'https://api.authanywhere.autharmor.com' ); // Auth armor api path
}

/**
 * On plugin activation redirect to setup wizard
 */
function auth_armor_activation_hook( $plugin ) {
	
    if( $plugin == plugin_basename( __FILE__ ) ) {
		update_option( "auth_armor_setup_wizard", 1 );
		update_option( "auth_armor_setup_wizard_confirm", 0 );
        exit( wp_redirect( admin_url( 'admin.php?page=autharmor_setup_wizard' ) ) );
    }
}
add_action( 'activated_plugin', 'auth_armor_activation_hook' );

/**
 * Load Text Domain
 * 
 * This gets the plugin ready for translation.
 */
function auth_armor_load_textdomain() {
	
	// Set filter for plugin's languages directory
	$lang_dir	= dirname( plugin_basename( __FILE__ ) ) . '/languages/';
	$lang_dir	= apply_filters( 'auth_armor_languages_directory', $lang_dir );
	
	// Traditional WordPress plugin locale filter
	$locale	= apply_filters( 'plugin_locale',  get_locale(), 'auth-armor' );
	$mofile	= sprintf( '%1$s-%2$s.mo', 'auth-armor', $locale );
	
	// Setup paths to current locale file
	$mofile_local	= $lang_dir . $mofile;
	$mofile_global	= WP_LANG_DIR . '/' . AUTH_ARMOR_PLUGIN_BASENAME . '/' . $mofile;
	
	if ( file_exists( $mofile_global ) ) { // Look in global /wp-content/languages/auth-armor folder
		load_textdomain( 'auth-armor', $mofile_global );
	} elseif ( file_exists( $mofile_local ) ) { // Look in local /wp-content/plugins/auth-armor/languages/ folder
		load_textdomain( 'auth-armor', $mofile_local );
	} else { // Load the default language files
		load_plugin_textdomain( 'auth-armor', false, $lang_dir );
	}	
}

/**
 * Load Plugin
 */
function auth_armor_plugin_loaded() {
 
	// load first plugin text domain
	auth_armor_load_textdomain();
}

//add action to load plugin
add_action( 'plugins_loaded', 'auth_armor_plugin_loaded' );


/**
 * Remove setup wizard from main menu sidebar
 */
add_action( 'admin_init', 'auth_armor_remove_menu_pages' );
function auth_armor_remove_menu_pages() {
	remove_menu_page( 'autharmor_setup_wizard' );    
	
}

/**
 * Redirect to setup wizard if setup wizard not completed 
 */
// add_action( 'init', 'auth_armor_redirect' );
// function auth_armor_redirect() {
// 	$setup_wizard_options = get_option( 'auth_armor_setup_wizard_confirm' );
// 	if(isset($_GET['page']) && $_GET['page'] == 'autharmor_settings' && empty($setup_wizard_options)){
// 		exit( wp_redirect( admin_url( 'admin.php?page=autharmor_setup_wizard' ) ) );
// 	}
// }

/**
 * Declaration of global variable
 */ 
global $auth_armor_admin, $auth_armor_public, $auth_armor_script , $auth_armor_settings , $auth_armor_setup_wizard;

include_once( AUTH_ARMOR_INCLUDE_DIR .'/auth-armor-misc-functions.php' );

// Public file
include_once( AUTH_ARMOR_INCLUDE_DIR . '/class-auth-armor-public.php' );
$auth_armor_public = new Auth_Armor_Public();
$auth_armor_public->add_hooks();

// Script file for CSS and Js 
include_once( AUTH_ARMOR_INCLUDE_DIR . '/class-auth-armor-script.php' );
$auth_armor_script = new Auth_Armor_Script();
$auth_armor_script->add_hooks();

if( is_admin() ) {
	// Admin class handles most of admin panel functionalities of plugin
	include_once( AUTH_ARMOR_ADMIN_DIR . '/class-auth-armor-admin.php' );
	$auth_armor_admin = new Auth_Armor_Admin();
	$auth_armor_admin->add_hooks();

	// Setting
	include_once( AUTH_ARMOR_ADMIN_DIR . '/class-auth-armor-settings.php' );
	$auth_armor_settings = new Auth_Armor_Settings();
	$auth_armor_settings->add_hooks();

	// Setup wizard setting
	include_once( AUTH_ARMOR_ADMIN_DIR . '/class-auth-armor-wizard.php' );
	$auth_armor_setup_wizard = new Auth_Armor_Setup_Wizard();
	$auth_armor_setup_wizard->add_hooks();
}