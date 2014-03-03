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

include_once( NTHMAILCHIMPPATH . 'vendor/mailchimp-api/src/Mailchimp.php' );

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
		add_action( 'publish_post', array( __CLASS__, 'new_post_notification' ) );
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
    static function save_settings( $settings_to_save, $option_name = null ){
		
		if ( ! $option_name ){
			$option_name = NthMailChimpCore::$option_name;
		}
		
        update_option( $option_name, $settings_to_save );

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
	
	
	static function new_post_notification( $post_id = null)
	{
		
		if ( ! $post_id ){
			$post_id = isset( $_GET['post_id'] ) && !empty( $_GET['post_id'] )? (int)$_GET['post_id'] : 0;
		}
		
		$content_sections = array();
		
		$the_post = get_post( $post_id );
		
		// We don't want to bombard the users with notifications.
		$notification_sent = get_post_meta( $post_id, 'notification_sent', true );
		
		if( $notification_sent ){
			return false;
		}
		
		
		$api_settings = self::get_settings('nthmc_api_key', true );
		$settings = self::get_settings(null, true );
		
		$enabled = isset( $settings['enabled'] ) && 1 == $settings['enabled']? true : false;
		
		if ( ! $enabled )
		{
			return false;
		}
	
		if ( isset( $settings['api_token'] ) ){
			$api_token = $settings['api_token'].'-'.$settings['api_dc'];
		}
		
		if ( isset( $api_settings['api_key'] ) ){
			$api_token = $api_settings['api_key'];
		}
		
		$mailchimp = new Mailchimp( $api_token );
		
		//$campaigns = $mailchimp->campaigns->getList();
		
		$template = $mailchimp->templates->info( $settings['template_id'] );
		$list = $mailchimp->lists->getList( array( 'list_id' => $settings['list_id'] ) );
		
		$segments = $mailchimp->lists->segments( $settings['list_id'] );
		
		if ( isset( $template['sections'] ) && !empty( $template['sections'] ) ){
			$content_sections = $template['sections'];
		}		
		
		$subject = self::process_tags( $settings['email_subject'], $the_post );
		
		$html_content = self::process_tags( wpautop( $settings['email_content'] ), $the_post );
		
		$text_content = self::process_tags( $settings['email_text'], $the_post );
		
		$from_name = $list['data'][0]['default_from_name'];
		$from_email = $list['data'][0]['default_from_email'];

		$options = array(
				'subject' => $subject,
				'template_id' => $settings['template_id'],
				'list_id' => $settings['list_id'],
				'from_name' => $from_name,
				'from_email' => $from_email,
			);
		
		// Override the main section of the template with the HTML content.
		$content_sections['main'] = $html_content;
		$content_sections['std_content'] = $html_content;
		
		$content = array(
			'html' => $html_content,
			'text' => $text_content,
			'sections' => $content_sections,
			);
		
		$segment_opts = array(
				'saved_segment_id' => $settings['segment_id'],
			);
		
		// Let's create the campaign in MailChimp
		$result = $mailchimp->campaigns->create( 'regular', $options, $content, $segment_opts );
		
		$campaign_id = $result['id'];
		
		// Now we have created a campaign, should we send it?
		if ( isset( $settings['test_mode'] ) && 1 == $settings['test_mode'] ){
			
			$email_addresses = explode(', ', $settings['test_emails'] );
			
			foreach( $email_addresses AS $key => $address ){
				$email_addresses[$key] = trim( $address);
			}

			$test_result = $mailchimp->campaigns->sendTest( $campaign_id, $email_addresses );
			
			// update_post_meta( $post_id, 'notification_sent', true );
			
			return true;
		
		} else {
			$mailchimp->campaigns->send( $campaign_id );
			
			update_post_meta( $post_id, 'notification_sent', true );
			
			return true;
		}
		
	}
	
	
	public static function process_tags( $text, WP_Post $the_post )
	{
		
		$tags = $matches = array();
		
		$text = htmlspecialchars_decode( stripcslashes( $text ) );
		
		$text_to_return = $text;
		
		// $regex  = '#\*\|([A-Z-]*)\|\*#i';
		$regex= '#\*\|([A-Z-_]*|[A-Z-_]*\:[a-z\/]*)\|\*#i';
		
		preg_match_all( $regex, $text, $matches );
		
		if ( !isset( $matches[1] ) || count( $matches[1] ) <=0 ){
			return $text_to_return;
		}
		
		$matches = $matches[1];
		
		foreach( $matches AS $key => $token ){
			if ( 'P-' != substr( $token, 0, 2 )){
				unset( $matches[ $key ] );
				continue;	
			}
			
			if ( false !== strpos( $token, ':' ) ){
				$tmp = explode( ':', $token );
				$matches[$key] = $tmp;
			}
		}
		
		// Now we have the tokens, let's replace them for actual values
		foreach( $matches AS $token ){
			$format = $string = null;
			if ( is_array( $token ) ){
				$format = $token[1];
				$token = $token[0];
			}
			
			switch( $token ){
				case 'P-TITLE':
					$string = $the_post->post_title;
					break;
				case 'P-URL':
					$string = $the_post->guid;
					break;
				
				case 'P-DATE':
					if ( '' == $format ){
						$string = mysql2date(get_option('date_format'), $the_post->post_date);
					}else{
		                $string = mysql2date($format, $the_post->post_date);
					}
					break;
				case 'P-AUTHOR':
					$author = get_userdata($the_post->post_author);
					$string = $author->display_name;
					break;
				case 'P-CATEGORIES':
					$string = the_category( ', ', null, $the_post->ID );
					break;
				case 'P-EXCERPT':
					$string = $the_post->post_excerpt;
					break;
				case 'P-CONTENT':
					$string = $the_post->post_content;
					break;
				default:
					$string = '';
					break;
			}
			
			if ( $format ){
				$token .= ':'.$format;
			}
			$text_to_return = str_replace( '*|'.$token.'|*', $string, $text_to_return );
		}
		
		return $text_to_return;
	}
	

}