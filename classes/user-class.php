<?php
namespace wp_user_sentry;
class User{
  /**
   * Get Current Users IP
   * @access public
   * @since 0.1.0
   * @return string Single IPv4/6 address
   */
  public function getIp(){
    foreach( ['HTTP_CLIENT_IP', 'REMOTE_ADDR', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED'] as $key ){
      if( true === array_key_exists( $key, $_SERVER ) ) {
        foreach( array_map('trim', explode(',', $_SERVER[$key] ) ) as $ip ){
          if(false !== filter_var($ip, FILTER_VALIDATE_IP ) ){
            return $ip;
          }
        }
      }
    }
  }

/**
 * Get information about Device such as browser or operating system
 * @access public
 * @since 0.1.0
 * @param  string $user_agent full HTTP_USER_AGENT
 * @return array  array containing Browser,OS,IP
 */
 public function getDevice( $user_agent = null){

   if( !$user_agent ) $user_agent = $_SERVER['HTTP_USER_AGENT'];
   //Adding space to avoid it being potentially evaluated as a boolean because PHP ¯\_(ツ)_/¯
   $user_agent = ' '.strtolower($user_agent);
   /*
    * @todo a much better detection method as this is well "basic" but covers 95% of typical browsers
    */
   $browser = 'Unknown Browser';
   $browsers = [
     'opera'     => 'Opera',
     'opr/'      => 'Opera',
     'edge'      => 'Edge',
     'chrome'    => 'Chrome',
     'safari'    => 'Safari',
     'firefox'   => 'Firefox',
     'msie'      => 'Internet Explorer',
     'trident/7' => 'Internet Explorer'
   ];
   foreach ( $browsers as $agent => $name ){
     if( strpos( $user_agent, $agent ) ){
       $browser = $name;
       break;
     }
   }
   $os = 'Unknown Operating System';
   $oss = [
     'android'   => 'Android',
     'windows'   => 'Windows',
     'macintosh' => 'MacOS',
     'iphone'    => 'iPhone',
     'linux'     => 'Linux',
   ];
   foreach ( $oss as $agent => $name ){
     if( strpos( $user_agent, $agent ) ){
       $os = $name;
       break;
     }
   }
   $ip = $this->getIp();
   if(!$ip || !isset( $ip )) $ip = 'unknown IP';

   return [
     'os' => $os,
     'browser' => $browser,
     'ip' => $ip
   ];
 }

/**
 * GetCountry Code from 3rd Party Services
 * @param  string $IP
 * @return string Country Code
 */
public function getCountry( $ip ){
  $settings = get_option( 'wp_user_sentry_settings' );
  if( isset($settings['geo_api_service']) && '2' === $settings['geo_api_service']){
    return $this->getCountryIPAPI( $ip );
  }
  if( isset($settings['geo_api_service']) && '3' === $settings['geo_api_service']){
    return $this->getCountryWooCommerce( $ip );
  }
  return false;
}
 /**
  * Get Country from ip-api.com
  * @access protected
  * @param  string $ip
  * @return string   Country Code
  */
 protected function getCountryIPAPI( $ip ){
   $url = 'http://ip-api.com/json/'.$ip.'?fields=country,countryCode,query';
   $json = wp_remote_get( $url );
   try{
     $return = json_decode( $json['body'] );
   }
   catch ( Exception $e ){
     return false;
   }
   if( !isset($return->query) || $return->query !== $ip ){
     return false;
   }
   if( isset( $return->country ) && isset( $return->countryCode ) ){
     return [
       'country' => filter_var( $return->country, FILTER_SANITIZE_SPECIAL_CHARS ),
       'code'    =>filter_var( $return->countryCode, FILTER_SANITIZE_SPECIAL_CHARS )
     ];
   }
    return false;
 }

 /**
  * Get Country from WooCommerce MaxMind DB
  * @access protected
  * @param  string $ip
  * @return string   Country Code
  * @todo Generate Name from Country Code because Woo Doesn't return it
  */
protected function getCountryWooCommerce( $ip ){
  if( class_exists( 'WC_Geolocation' ) ){
    $country = \WC_Geolocation::geolocate_ip( $ip );
    if(!empty( $country ) && isset( $country['country'] )){
     $code =  $country['country'];
     return [
       'country' => filter_var( $code, FILTER_SANITIZE_SPECIAL_CHARS ),
       'code'    =>filter_var( $code, FILTER_SANITIZE_SPECIAL_CHARS )
     ];
    }
  }
  return false;
}


 public function emojiFlag($code) {

    if (!is_string($code) || strlen($code) < 2) {
      return false;
    }

    $code = strtolower($code);

    $replacement = array(
      'uk' => 'gb',
      'an' => 'nl',
      'ap' => 'un',
    );

    if (array_key_exists($code, $replacement)) {
      $code = $replacement[$code];
    }

    $arr = str_split($code);
    $str = '';
    foreach ($arr as $char) {
      $str .= $this->enclosedUnicode($char);
    }
    return $str;
  }

  protected function enclosedUnicode($char) {
    $arr = array(
      'a' => '1F1E6',
      'b' => '1F1E7',
      'c' => '1F1E8',
      'd' => '1F1E9',
      'e' => '1F1EA',
      'f' => '1F1EB',
      'g' => '1F1EC',
      'h' => '1F1ED',
      'i' => '1F1EE',
      'j' => '1F1EF',
      'k' => '1F1F0',
      'l' => '1F1F1',
      'm' => '1F1F2',
      'n' => '1F1F3',
      'o' => '1F1F4',
      'p' => '1F1F5',
      'q' => '1F1F6',
      'r' => '1F1F7',
      's' => '1F1F8',
      't' => '1F1F9',
      'u' => '1F1FA',
      'v' => '1F1FB',
      'w' => '1F1FC',
      'x' => '1F1FD',
      'y' => '1F1FE',
      'z' => '1F1FF',
    );
    $char = strtolower($char);
    if (array_key_exists($char, $arr)) {
      return mb_convert_encoding('&#x'.$arr[$char].';', 'UTF-8', 'HTML-ENTITIES');
    }
    return false;
  }

}
