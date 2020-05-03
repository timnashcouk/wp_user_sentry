<?php
namespace wp_user_sentry;
/**
 * Sessions table class for user profile
 * wp-admin/profile.php
 * @since 0.4.0
 */
class Profile{

  /**
   * Adds a session table to profile.php for a user
   * @access public static
   * @since 0.4.0
   * @param  object $user user object for the users whos profile it is
   * @return null
   */
  static function userProfile( $user ){
    $manager = \WP_Session_Tokens::get_instance( $user->ID );
    $all_sessions = $manager->get_all();
    $user_info = new \wp_user_sentry\User;
    ?>
<div class="wp-user-sentry-session" id="wp-user-sentry-session">
  <h3><?php _e( 'Current Sessions','wp-user-sentry' ); ?></h3>
<table class="wp-list-table widefat fixed striped profile" id="wp-user-sentry-session-table">
  <thead>
    <tr>
      <th scope="col" class="manage-column column-primary" id="wp-user-sentry-session-login"><?php _e( 'Login','wp-user-sentry' ); ?></th>
      <th scope="col" class="manage-column" id="wp-user-sentry-session-ip"><?php _e( 'IP','wp-user-sentry' ); ?></th>
      <th scope="col" class="manage-column" id="wp-user-sentry-session-browser"><?php _e( 'Browser','wp-user-sentry' ); ?></th>
      <th scope="col" class="manage-column" id="wp-user-sentry-session-os"><?php _e( 'OS','wp-user-sentry' ); ?></th>
      <th scope="col" class="manage-column" id="wp-user-sentry-session-expiry"><?php _e( 'Expires','wp-user-sentry' ); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php
    if( !empty( $all_sessions ) && is_array( $all_sessions )){
      foreach( $all_sessions as $session ){
          $device = $user_info->getDevice( $session['ua'] );
          $login = \wp_user_sentry\Profile::tableDateFormat( $session['login'] );
          $expiry = \wp_user_sentry\Profile::tableDateFormat( $session['expiration'] );
          echo '<tr>';
          echo '<td>'.$login.'</td>';
          echo '<td>'.$session['ip'].'</td>';
          echo '<td>'.$device['browser'].'</td>';
          echo '<td>'.$device['os'].'</td>';
          echo '<td>'. $expiry.'</td>';
          echo '</tr>';
      }
    }else{
      echo '
      <tr>
      <td colspan="5" >'.__('No Current Sessions').'</td>
      </tr>
      ';
    }
    ?>
  </tbody>
</table>
</div>
    <?php
  }

  /**
   * provide Timestamp in usable format [date]@[time]
   * @access public static
   * @since 0.4.0
   * @param  string $timestamp
   * @return string
   */
  static function tableDateFormat( $timestamp ){
    return date_i18n( get_option( 'date_format' ), $timestamp ) .' @ '. date_i18n( get_option( 'time_format' ), $timestamp );
  }

}
