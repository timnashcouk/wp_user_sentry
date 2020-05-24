<?php
namespace wp_user_sentry;
/**
 * Login Notification Class
 * Triggers on wp_login action
 * @since 0.4.0
 */
class Notify{

  /**
   * Confirms email should be sent
   * @access public static
   * @since 0.4.0
   * @param  string  $user_login
   * @param  object $user
   * @return bool   triggers sendEmail
   */
  static function runNotify( $user_login, $user=false ){
    if( !$user || empty( $user )){
      $user = get_user_by( 'login', $user_login );
    }
    $settings = get_option( 'wp_user_sentry_settings' );
    $send = true;
    if( isset( $settings['notify_login_roles'] ) && is_array( $settings['notify_login_roles'] ) ) {
      if( !array_intersect( $user->roles, $settings['notify_login_roles'] ) ){
        $send = false;
      }
    }

    if( isset( $settings['notify_login_repeat'] ) && '2' === $settings['notify_login_repeat'] ){
      if( true === \wp_user_sentry\Notify::compareSessions( $user->ID ) ){
        $send = false;
      }
    }
    $send = apply_filters( 'wp_user_sentry_notify', $send, $user->ID );
    if( true !== $send ) return true;
    return \wp_user_sentry\Notify::sendEmail( $user );
  }
  /**
   * Send Email to user that is logging in
   * @access public static
   * @since 0.4.0
   * @param  object $user
   * @return bool
   */
  static function sendEmail( $user, $email=false ){
    $settings = get_option( 'wp_user_sentry_settings' );
    if( !empty( $email ) && isset( $email['message'] )){
      $message = $email['message'];
    }elseif( isset( $settings['notify_login_email'] ) ){
      $message = $settings['notify_login_email'];
    }else{
      $message = __(
'Hi, {display_name} [{user_login}],
Your account on {homeurl} was logged into at {time},
from a {os} machine running {browser}.
The IP address was {ip},{country}{flag}.
You are receiving this email to make sure it was you.
To review activity on your account visit {profile_url} or login to your admin on {homeurl} and navigate to your profile.
','wp-user-sentry');
    }
    $message = apply_filters( 'wp_user_sentry_email_message', $message );
    if( !empty( $email ) && isset( $email['subject'] )){
      $subject = $email['subject'];
    }elseif( isset( $settings['notify_login_email_subject'] ) ){
      $subject = $settings['notify_login_email_subject'];
    }else{
      $subject = __('Successful login');
    }
    $user_info = new \wp_user_sentry\User;
    $device = $user_info->getDevice();
    $profile_url = admin_url( 'profile.php#wp-user-sentry-session' );

    $country = '';
    $flag = '';
    if( isset( $settings['geo_api_service'] ) ){
      $geo = $user_info->getCountry( $device['ip'] );
      if( !empty( $geo ) ){
        $country = $geo['country'];
        $flag = $user_info->emojiFlag($geo['code']);
      }
    }
    $message = str_replace([
      '{user_login}',
      '{display_name}',
      '{homeurl}',
      '{time}',
      '{ip}',
      '{browser}',
      '{os}',
      '{profile_url}',
      '{country}',
      '{flag}'
     ],[
      $user->user_login,
      $user->display_name,
      get_home_url(),
      current_time( 'mysql' ),
      $device['ip'],
      $device['browser'],
      $device['os'],
      $profile_url,
      $country,
      $flag
     ],
     $message
    );
    $blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
    $email = [
      'to' => $user->user_email,
      'subject' => __( '['. $blogname .'] '.$subject ),
      'message' => $message,
      'headers' => ''
    ];
    $email = apply_filters( 'wp_user_sentry_login_email', $email );
    try{
      wp_mail(
        $email['to'],
        $email['subject'],
        $email['message'],
        $email['headers']
        );
    } catch( Exception $e ){
      error_log('WP User Sentry - Failed sending Email');
    }
    return true;
  }

  /**
   * Compares the current login with previous logins
   * @access public static
   * @since 0.4.0
   * @param  int  id
   * @return bool
   */
  static function compareSessions( $id )
  {
    $manager = \WP_Session_Tokens::get_instance( $id );
    $all_sessions = $manager->get_all();
    $user_info = new \wp_user_sentry\User;
    $ip = $user_info->getIp();
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    foreach( $all_sessions as $session ){
      if( $ip === $session['ip'] && $user_agent === $session['ua'] ){
        return true;
      }
    }
    return false;
  }
}
