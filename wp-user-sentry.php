<?php
namespace wp_user_sentry;
/**
 * Plugin Name:     WP User Sentry
 * Plugin URI:      https://timnash.co.uk/wp-user-sentry
 * Description:     Notify user on successful login & show sessions in profile.php
 * Author:          Tim Nash
 * Author URI:      https://timnash.co.uk
 * Text Domain:     wp-user-sentry
 * Domain Path:     /languages
 * Version:         1.1.0
 *
 */
class wp_user_sentry{

  /**
   * Add default actions
   * @access public
   * @since 0.1.0
   */
  public function __construct(){
    register_activation_hook( __FILE__, array( $this, 'activation' ) );
    spl_autoload_register( function ( $class ) {

    	if ( false === strpos( $class, __NAMESPACE__ ) ) {
    		return;
    	}
      $class = str_replace( __NAMESPACE__ . '\\', '', $class );
      $class = strtolower( $class );
	    $class = str_replace( '_', '-', $class );
      require_once __DIR__ . '/classes/' . $class . '-class.php';
    });
    add_action( 'show_user_profile', [ __NAMESPACE__ .'\\Profile', 'userProfile' ], 0 );
    add_action( 'edit_user_profile', [ __NAMESPACE__ .'\\Profile', 'userProfile' ], 0 );
    add_action( 'wp_login', [ __NAMESPACE__ .'\\Notify' , 'runNotify' ] );
    add_action( 'admin_init', [ __NAMESPACE__ .'\\Admin', 'adminInit' ] );
    add_action( 'admin_menu', function(){
      add_options_page(
        __('WP User Sentry'),
        __('WP User Sentry'),
        'manage_options',
        'wp-user-sentry-settings',
        [ __NAMESPACE__ .'\\Admin','settingsPage']
      );
    });
    add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_action_links'] );
  }

  /**
   * Adds Settings link to Plugins page
   * @access public
   * @since 0.4.0
   * @return array
   */
  public function add_action_links( $links ){
    $additional_links = [
        '<a href="' . admin_url( 'options-general.php?page=wp-user-sentry-settings' ) . '">'.__( 'Settings','wp-user-sentry' ).'</a>',
    ];
    return array_merge( $additional_links, $links );
  }
  /**
   * Setup the Plugin Settings
   * @access public
   * @since 0.4.0
   * @return bool
   */
  public function activation(){
    if( !get_option( 'wp_user_sentry_settings' ) ){
      //Set up the defaults for the settings page.
      $roles = array_keys( get_editable_roles() );
      $settings = [
        'geo_api_service'     => 1,
        'notify_login_roles'  => $roles,
        'notify_login_repeat' => 1
      ];
      return add_option( 'wp_user_sentry_settings', $settings );
    }
    return true;
  }

}
/**
 * Let's get going
 */
new wp_user_sentry();
