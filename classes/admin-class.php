<?php
namespace wp_user_sentry;
/**
 * Settings Page
 * wp-admin/options-general.php?page=wp-user-sentry-settings
 * @since 0.4.0
 */
class Admin{
  /**
   * admin init for Settings hook
   * @access public static
   * @since 0.4.0
   */
  static function adminInit(){

    register_setting( 'wp-user-sentry-settings', 'wp_user_sentry_settings' );
    add_settings_section(
      'wp_user_sentry_settings_section',
      __('General Settings'),
      [ __NAMESPACE__ .'\Admin', 'settingsCallback'],
      'wp-user-sentry-settings'
    );
    add_settings_field(
      'geo_api_service',
      __('Look Up Country by IP?'),
      [ __NAMESPACE__ .'\Admin' ,'fieldApiRender'],
      'wp-user-sentry-settings',
      'wp_user_sentry_settings_section'
    );
    add_settings_field(
      'notify_login_roles',
      __('Which Roles Should have Login notifications enabled for?'),
      [ __NAMESPACE__ .'\Admin' ,'fieldRolesRender'],
      'wp-user-sentry-settings',
      'wp_user_sentry_settings_section'
    );
    add_settings_field(
      'notify_login_repeat',
      __('Email on repeat logins from same IP & Useragent?'),
      [ __NAMESPACE__ .'\Admin' ,'fieldRepeatRender'],
      'wp-user-sentry-settings',
      'wp_user_sentry_settings_section'
    );
    add_settings_field(
      'notify_login_email_subject',
      __('Email Message Subject'),
      [ __NAMESPACE__ .'\Admin' ,'fieldEmailSubjectRender'],
      'wp-user-sentry-settings',
      'wp_user_sentry_settings_section'
    );
    add_settings_field(
      'notify_login_email',
      __('Email Message Contents'),
      [ __NAMESPACE__ .'\Admin' ,'fieldEmailRender'],
      'wp-user-sentry-settings',
      'wp_user_sentry_settings_section'
    );

    /*
     * Test Email Hooks
     */
    add_action( 'wp_ajax_wp-user-sentry-test-email', [__NAMESPACE__ .'\Admin', 'sendTestEmailCallback' ]);

    add_action( 'admin_footer', [__NAMESPACE__ .'\Admin', 'jsFooterRender' ]);

  }

  /**
   * Settings Page Render
   * @access public static
   * @since 0.4.0
   */
  static function settingsPage(){
    ?>
    <div class="wrap">
    <h1><?php _e( 'WP User Sentry','wp-user-sentry' ); ?></h1>
    <form action='options.php' method='post'>
      <?php
        settings_fields( 'wp-user-sentry-settings' );
        do_settings_sections( 'wp-user-sentry-settings' );
        submit_button();
       ?>
    </form>
  </div>
    <?php
  }

  /**
   * Settings Section Render
   * @access public static
   * @since 0.4.0
   */
  static function settingsCallback(){
    ?><p><?php _e( 'WP User Sentry provides additional login monitoring, notifying users of a successful login and providing a sessions table for each user in their profile.','wp-user-sentry' ); ?></p><?php
  }

  /**
   * Dropdown Render for Selecting GEOIP lookup
   * @access public static
   * @since 0.4.0
   */
  static function fieldApiRender(){
    $options = get_option( 'wp_user_sentry_settings' );
    ?>
    <select name='wp_user_sentry_settings[geo_api_service]'>
       <option value='1' <?php selected( $options['geo_api_service'], '1' ); ?>>None</option>
       <option value='2' <?php selected( $options['geo_api_service'], '2' ); ?>>ip-api.com</option>
      <?php
        if( self::testMaxMindPresence() ){
          ?>
       <option value='3' <?php selected( $options['geo_api_service'], '3' ); ?>>WooCommerce GeoAPI</option>
          <?php
        }
      ?>
   </select>
   <p class="description"><?php _e( 'If enabled, each login IP will be sent to third party service.','wp-user-sentry' ); ?></p>
    <?php
    if( class_exists( 'woocommerce' ) && !self::testMaxMindPresence() ){
      ?>
      <p class="description"><?php _e( 'To use WooCommerce GEO API configure it first.','wp-user-sentry' ); ?></p>
      <?php
    }
  }
  /**
   * Multi checkbox for selecting which roles should be set.
   * @access public static
   * @since 0.4.0
   */
  static function fieldRolesRender(){
    $options = get_option( 'wp_user_sentry_settings' );
    $roles = get_editable_roles();
    $html = '';
    if (isset($options['notify_login_roles']) && ! empty($options['notify_login_roles'])) {
        $value = $options['notify_login_roles'];
    }

    foreach ($roles as $role => $data){
      $checked = '';
      if(!empty( $value ) && in_array( $role, $value) ){
        $checked = 'checked';
      }

      $html .= '<input type="checkbox" name="wp_user_sentry_settings[notify_login_roles][]" value="'.$role.'" '.$checked.' />'.$data['name'].'</br>';
    }
    echo $html;
  }

  /**
   * Dropdown Render for Repeat
   * @access public static
   * @since 0.4.0
   */
  static function fieldRepeatRender(){
    $options = get_option( 'wp_user_sentry_settings' );
    ?>
    <select name='wp_user_sentry_settings[notify_login_repeat]'>
       <option value='1' <?php selected( $options['notify_login_repeat'], '1' ); ?>>Enabled</option>
       <option value='2' <?php selected( $options['notify_login_repeat'], '2' ); ?>>Disabled</option>
   </select>
    <?php
  }

  /**
   * Text Area for Email Message Content
   * @access public static
   * @since 0.4.0
   */
  static function fieldEmailRender(){
    $options = get_option( 'wp_user_sentry_settings' );
    if( !isset( $options['notify_login_email'] )){
      $contents =
'Hi, {display_name} [{user_login}],
Your account on {homeurl} was logged into at {time},
from a {os} machine running {browser}.
The IP address was {ip},{country}{flag}.
You are receiving this email to make sure it was you.
To review activity on your account visit {profile_url} or login to your admin on {homeurl} and navigate to your profile.
';
    }else{
      $contents = $options['notify_login_email'];
    }
    ?>
    <textarea name="wp_user_sentry_settings[notify_login_email]" class="large-text code" rows="8" spellcheck="false"><?php echo $contents  ?></textarea>
    <p class="description"><?php _e( 'The following dyanmic parameters may be added:','wp-user-sentry' ); ?> <strong>{displayname}, {user_login}, {ip}, {os}, {browser}, {country}, {flag}, {time}, {profile_url}, {homeurl}</strong></p>
    <button type="button" name="wp-user-sentry-test-email" class="button wp-user-sentry-test-email"><?php _e('Send Test Email','wp-user-sentry'); ?></button>
    <?php
  }
  /**
   * Input Field for subject
   * @access public static
   * @since 1.1.0
   */
  static function fieldEmailSubjectRender(){
    $options = get_option( 'wp_user_sentry_settings' );
    if( !isset( $options['notify_login_email_subject'] )){
      $subject = __('Successful login');
    }else{
      $subject = $options['notify_login_email_subject'];
    }
    ?>
    <input type="text" name="wp_user_sentry_settings[notify_login_email_subject]" value="<?php echo $subject;  ?>">
    <?php
  }
  /**
   * Javascript in Footer
   * @access public static
   * @since 1.1.0
   */
  static function jsFooterRender(){
    $screen = get_current_screen();
    if( $screen->id !== 'settings_page_wp-user-sentry-settings') return;
    $ajax_nonce = wp_create_nonce( "wp-user-sentry-test-email" );
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.wp-user-sentry-test-email').click(function(){
            var subject = $('input[name="wp_user_sentry_settings[notify_login_email_subject]"]').val();
            var contents = $.trim( $('textarea[name="wp_user_sentry_settings[notify_login_email]"]').val() );
            var data = {
                action: 'wp-user-sentry-test-email',
                security: '<?php echo $ajax_nonce; ?>',
                subject: subject,
                message: contents
            };
            $.post(ajaxurl, data, function(response) {
              const res = JSON.parse(response);
              if(res.type == "success"){
                jQuery('#wpbody-content').prepend('<div class="notice notice-success is-dismissible"><p><?php _e( 'Test Email Sent to: ' ); ?>'+ res.email +'</p></div>');
              }
              else{
                jQuery('#wpbody-content').prepend('<div class="notice notice-error is-dismissible"><p><?php _e( 'Test Email Failed to send' ); ?></p></div>');
              }
            });
        });
    });
    </script>
    <?php
  }

  /**
   * Test Email AJAX Callback
   * @access public static
   * @since 1.1.0
   */
  static function sendTestEmailCallback(){
    check_ajax_referer( 'wp-user-sentry-test-email', 'security');
    if( ! current_user_can( 'manage_options' ) ){
      exit();
    }
    $user = wp_get_current_user();
    $email = [];
    if( isset( $_POST['subject'] )){
      $email['subject'] = $_POST['subject'];
    }
    if( isset( $_POST['message'] )){
      $email['message'] = $_POST['message'];
    }
    if( \wp_user_sentry\Notify::sendEmail( $user, $email ) ){

      echo json_encode([
        'type' => 'success',
        'email'  => $user->user_email
      ]);
    }else{
      echo json_code([
        'type' => 'fail'
      ]);
    }
    exit();
  }

  static public function testMaxMindPresence(){
    $return = false;
    $wooCommerce_GeoAPI = get_option( 'woocommerce_maxmind_geolocation_settings' );
    if( class_exists( 'woocommerce' ) && !empty( $wooCommerce_GeoAPI ) && isset($wooCommerce_GeoAPI['license_key'])) $return = true;
    return $return;
  }


}
