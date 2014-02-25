<?php
/**
 * Scribblar Core
 *
 * A set of core functions shared between frontend and the adminstration system
 *
 * @author rtweedie
 * @package nth mailchimp
 * @since 1.0
 * @version 1.0
 */

 // Include the logging functions
include_once( NTHMAILCHIMPPATH . 'classes/nth-mailchimp-logging.php' );

include_once( NTHMAILCHIMPPATH . 'vendor/mailchimp-api-php/src/Mailchimp.php' );

class NthMailChimpCore
{
	
	/**
   * Default options for cURL.
   */
  public static $CURL_OPTS = array(
    CURLOPT_FOLLOWLOCATION => TRUE,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_HEADER         => TRUE,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_USERAGENT      => 'oauth2-draft-v10',
    CURLOPT_HTTPHEADER     => array("Accept: application/json"),
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_SSL_VERIFYPEER => false
  );
  
    var $plugin_file, $plugin_path, $plugin_url;

    static $log_dir_path = '';
	static $log_dir_url  = '';
    static $wpdb;
    static $settings;
	static $api_key;
	static $api_secret;
	
	static $client_id;
	
	static $authorise_url 		= 'https://login.mailchimp.com/oauth2/authorize?response_type=code&client_id=%1$s';
	static $access_token_url 	= 'https://login.mailchimp.com/oauth2/token';
	static $base_url 			= 'https://login.mailchimp.com/oauth2/';
		
    public static $logger;
    public static $debug = true;


    public static $option_name 	= 'nth_mailchimp_settings';
	
    function __construct()
    {
		global $wpdb;
        self::$wpdb         = $wpdb;
        $this->plugin_file  = dirname( dirname( __FILE__ ) ) . '/nth-mailchimp-notifier.php';
        $this->plugin_path  = dirname( dirname( __FILE__ ) ) . '/';

        $this->plugin_url   = plugin_dir_url(dirname( __FILE__ ) );

		register_activation_hook( $this->plugin_file, array( __CLASS__, 'install' ) );
		register_deactivation_hook( $this->plugin_file, array( __CLASS__, 'uninstall' ) );
		
		// Add the Scribblar actions and filtes.
		self::add_actions();
		self::add_filters();
		
		self::set_api_key( '451854714518' );
		self::set_api_secret( 'a48f9185d7368c8e83531d7f1b1edd32' );
		self::set_client_id( '451854714518' );
		
		
		add_action('init', array( __CLASS__, 'early_request_handler'), 0);
    }
	
	
	static function early_request_handler()
	{		
		
		if (isset($_GET['nthmc_action'])) {
			switch ($_GET['nthmc_action']) {
				case 'authorise':
					self::authorise();			
					exit;
					break;
				case 'authorised':
					self::authorised();
					break;
			}
		}
	}



    static function register_post_type()
    {
		include( SCRIBBLARPATH . 'classes/scribblar-room.php' );
		
		ScribblarRoom::init();
    }
	
	static function add_metaboxes()
	{
		// include( SCRIBBLARPATH . 'classes/scribblar-metaboxes.php' );
	}
	
	
	static function add_actions()
	{
		// Stub
	}
	
	
	static function add_filters()
	{
		// stub
	}
	
	static function set_api_key( $key )
	{
		self::$api_key = $key;
	}
	
	static function get_api_key()
	{
		if ( ! self::$api_key ){
			self::$api_key = get_option('nth_mailchimp_api_key');
		}
		
		return self::$api_key;
	}
	
	
	static function set_api_secret( $secret )
	{
		self::$api_secret = $secret;
	}
	
	static function get_api_secret()
	{
		if ( ! self::$api_secret ){
			self::$api_secret = get_option('nth_mailchimp_api_secret');
		}
		
		return self::$api_secret;
	}
	
	
	static function set_client_id( $client_id )
	{
		self::$client_id = $client_id;
	}
	
	static function get_client_id()
	{
		if ( ! self::$client_id ){
			self::$client_id = get_option('nth_mailchimp_client_id');
		}
		
		return self::$client_id;
	}
	
    static function register_styles_and_scripts()
    {
		// Stub
    }
	
    static function enqueue_frontend_styles_and_scripts()
    {
		// Stub
    }

    function get_plugin_file()
    {
        return $this->plugin_file;
    }

    function get_plugin_path()
    {
        return $this->plugin_path;
    }

    function get_plugin_url()
    {
        return $this->plugin_url;
    }

    function init()
    {
    }

    static function install()
    {
		// stub
    }

    static function uninstall()
    {
		// stub
    }
	
	function createLogger(){
        if ( ! isset( self::$logger ) ){
            self::$logger = new NthMailChimpLogging();
        }
        return self::$logger;
    }

    /**
     * Log messages, if in debug mode.
     *
     * @param string $message
     * @param string $type || null
     * @return boolean TRUE || FALSE
     */
    function log ( $message, $type = 'message' ){

        if ( ! self::$debug ){
            return false;
        }

        if ( ! self::$logger ){
            self::createLogger();
        }

        self::$logger->log( $message , $type );
        return true;
    }

    /**
	 * Log errors to a file
	 *
	 * @since 0.2
	 **/
	private static function log_errors( $errors ) {
		if ( empty( $errors ) )
			return;

		$log = @fopen( self::$log_dir_path . 'nth_mailchimp_errors.log', 'a' );
		@fwrite( $log, sprintf( __( 'BEGIN %s' , 'ivcpd'), date( 'Y-m-d H:i:s', time() ) ) . "\n" );

		foreach ( $errors as $key => $error ) {
			$line = $key + 1;
			$message = $error->get_error_message();
			@fwrite( $log, sprintf( __( '[Line %1$s] %2$s' , 'nthmailchimp'), $line, $message ) . "\n" );
		}

		@fclose( $log );
	}

	
    /*
     * Get the plugin settings
     *
     * @param void
     *
     * @return array
     */
    static function get_settings( $option_name = null, $force_reload = false )
    {
        $settings = NthMailChimpCore::$settings;
				
		if ( $force_reload == true ){ $settings = array(); }
		
        if ( ( isset( $settings ) && !empty ( $settings ) && count( $settings ) > 0 ) ){
            return $settings;
        } else {
			if ( ! $option_name ){
				$option_name = NthMailChimpCore::$option_name;
			}
            NthMailChimpCore::$settings = get_option( $option_name );
            return NthMailChimpCore::$settings;
        }
    }

    /*
     * Save the plugin settings
     *
     * @param array $settings_to_save
     *
     * @return array
     */
    static function save_settings( $settings_to_save ){

        update_option( NthMailChimpCore::$option_name, $settings_to_save );

        NthMailChimpCore::$settings = $settings_to_save;

        return NthMailChimpCore::$settings;
    }
	
	
	static function include_view( $view, $data_for_view = null )
	{
		if ( $data_for_view )
		{
			extract($data_for_view);
		}
		
		$filepath = NTHMAILCHIMPPATH . 'views/'.$view.'.php';
		if ( file_exists( $filepath ) ){
			include( $filepath );
		} else{
			include( NTHMAILCHIMPPATH . 'views/index.php' );
		}
	}
	
	
	static function auth_nonce_salt() {
		return md5(microtime().$_SERVER['SERVER_ADDR']);
	}
	
	
	static function auth_nonce_key($salt = null) {
		if (is_null($salt)) {
			$salt = self::auth_nonce_salt();
		}
		return md5('social_authentication'.AUTH_KEY.$salt);
	}
	
	
	/**
	 * Creates a new MailChimp object
	 *
	 * @return MailChimp || false
	 */
	static function get_api($force = false) {
		$public_key = self::get_api_key();
		
		if ($public_key || $force) {
			return new Mailchimp( $public_key );
		}
		return false;
	}
	
	
	static function authorise()
	{
		
		$url = home_url('index.php');
		
		$api_url = self::$authorise_url;
		
		$api_url = sprintf( $api_url, self::$client_id );
	
		$api_url = apply_filters('nth_mailchimp_authorize_url', $api_url );
		
		
		//$salt = self::auth_nonce_salt();
		//$id = wp_create_nonce( self::auth_nonce_key($salt) );
		
		$args = array(
			'nthmc_action' => 'authorised',
		);
	
		$api_url = add_query_arg(array(
			'redirect_url' => urlencode( self::get_redirect_url() )
		), $api_url);
		
		$api_url = apply_filters('nth_mailchimp_proxy_url', $api_url);
		
		wp_redirect($api_url);
		exit;
	}
	

	static function authorised() {
				
		$code = isset($_GET['code']) && !empty( $_GET['code'] )? $_GET['code'] : null;
		
		if ( ! $code ){
			return false;
		}
		
		
		$pars = array(
                  'grant_type' => 'authorization_code',
                  'client_id' => self::get_client_id(),
                  'client_secret' => self::get_api_secret(),
                  'code' => $code,
                  'redirect_uri' => self::get_redirect_url()
                );
		
		$request = new WP_Http();
		
		$result = $request->post( self::$access_token_url, array( 'body' => $pars ) );
		
		$body = json_decode( $result['body']);
		
		if ( isset( $body->error ) ){
			
			self::authorise();
			
			die( 'the token has expired: ' . $body->error );
		}
		
		if ( isset( $body->access_token ) ){
			
			$settings = self::get_settings();
			
			// We can save the API token and then use it as the API key
			$settings['api_token'] = esc_attr($body->access_token);
			
			// Now we need to get the meta data.
			$metadata_url = self::$base_url.'metadata';
			$header = array( 'Authorization' => 'OAuth '.$settings['api_token'] );
			$result = $request->get( $metadata_url, array( 'headers' => $header ) );
			
			$meta = json_decode( $result['body'] );
			
			$settings['api_dc']  			= $meta->dc;
			$settings['api_role'] 			= $meta->role;
			$settings['api_accountname'] 	= $meta->accountname;
			$settings['api_login_url'] 		= $meta->login_url;
			$settings['api_endpoint'] 		= $meta->api_endpoint;
			
			self::save_settings( $settings );
			
			wp_redirect( '/wp-admin/options-general.php?page=nth-mailchimp-settings' );	
		}
		
		exit( );
		
	}
	
	
	static function get_redirect_url()
	{
		
		$url = home_url('index.php');
		$args = array( 'nthmc_action' => 'authorised' );
		return add_query_arg($args, $url);
		
	}

}