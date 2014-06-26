<?php
/**
 * Nth MailChimp notifier admin
 *
 * A set of functions to output pages in the administration system
 *
 * @author rtweedie
 * @package nth mailchimp
 * @since 1.0
 * @version 1.0
 */

class NthMailChimpAdmin extends NthMailChimpCore
{

		static $account_settings;// Used to store the account details from the API.
		static $merged_settings;// Used to store the merged settings between the API and WP Options.

		function init()
		{
				parent::init();

				// Support installation and uninstallation
				register_activation_hook( $this->plugin_file, array( __CLASS__, 'install' ) );
				register_deactivation_hook( $this->plugin_file, array( __CLASS__, 'uninstall' ) );

				add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
				add_action( 'admin_init', array( __CLASS__, 'settings_init' ) );

				add_action( 'init', array( __CLASS__, 'register_styles_and_scripts' ) );

				add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

				$api_settings = self::get_settings( 'nthmc_api_key', true );
				$settings = self::get_settings( null, true );

				$api_token = $api_key = null;

				$api_token = isset( $settings['api_token'] ) && ! empty( $settings['api_token'] )? true : false;
				$api_key = isset( $api_settings['api_key'] ) && ! empty( $api_settings['api_key'] )? true : false;

				if ( ( ! $api_key && ! $api_token )
					|| ( ! isset( $api_token ) && $api_key && ! isset( $_POST['submit'] ) ) ) {
					add_action( 'admin_notices', array( __CLASS__, 'api_activation_warning' ) );
				}

		}

		static function register_styles_and_scripts()
		{
				wp_register_style( 'nth-mailchimp-notifier-css', plugins_url('assets/css/nth-mailchimp-notifier.css', dirname(__FILE__) ) );
    }


		static function enqueue_scripts( $hook )
		{
				wp_enqueue_style( 'nth-mailchimp-notifier-css' );
		}


    static function admin_menu()
    {

				add_options_page( 'MailChimp notifications', 'MailChimp notifications', 'edit_posts', 'nth-mailchimp-settings', array( __CLASS__,'settings_page' ) );

				add_submenu_page( null, 'Send test email', 'Send test email', 'edit_posts', 'send-test-email', array( __CLASS__,'new_post_notification' ) );

				add_submenu_page( null, 'Nth MailChimp API', 'Nth MailChimp API', 'edit_posts', 'nth-mailchimp-api', array( __CLASS__,'api_settings' ) );

    }


    /**
     * Install the plugin and create the database.
     *
     * @param void
     * @return void
     */
    static function install()
    {
				parent::install();
    }

    /**
     * Uninstall the plugin and create the database.
     *
     * @param void
     * @return void
     */
    static function uninstall()
    {
				parent::uninstall();
    }

    /**
     * Add the administration menu
     *
     * @param void
     * @return void
     */
    static function add_admin_pages()
    {
				// Stub
		}


    /**
     * The most viewed index page
     *
     * @param void
     * @return void
     */
    static function index_page()
    {
				self::include_view( 'index' );
    }


		static function api_settings()
		{
				self::include_view( 'api_settings' );
		}



		static function settings_init()
		{
				register_setting(
            'api_group', // Option group
            'nthmc_api_key', // Option name
            array( __CLASS__, 'sanitise_api' ) // Sanitise
						);

        add_settings_section(
            'setting_api', // ID
            'API Settings', // Title
            array( __CLASS__, 'print_section_info' ), // Callback
            'api-admin' // Page
        );

        add_settings_field(
            'mailchimp_api',
            'MailChimp API key',
            array( __CLASS__, 'api_callback' ),
            'api-admin',
            'setting_api'
        );
		}

	static public function sanitise_api( $input )
	{
		$new_input = array();

		$new_input = self::get_settings('nthmc_api_key');

        if( isset( $input['api_key'] ) ){
            $new_input['api_key'] = esc_attr( $input['api_key'] );
        }
        return $new_input;
	}


	static public function api_callback()
    {
		return self::text_callback( 'api_key', 'nthmc_api_key' );
    }

	static public function text_callback( $option_name, $option_group = 'settings' )
    {
		$settings = self::get_settings( 'nthmc_api_key' );

        printf(
            '<input class="all-options" id="%2$s" name="%3$s[%2$s]" value="%s" />',
            isset( $settings[ $option_name ] ) ? esc_attr( $settings[ $option_name ]) : '', $option_name, $option_group
        );
    }

	static function print_section_info()
	{
		// Stub
	}


	static function settings_page()
	{
		$data_for_view = array();

		$api_key = $api_token = null;

		$api_settings = self::get_settings('nthmc_api_key', true );
		$settings = self::get_settings(null, true );


		$data_for_view = $settings;

		/*
		 * Let's query the API to retrieve:
		 * * list of templates
		 * * List of lists
		 * * List of segments in the list
		 */

		if ( isset( $settings['api_token'] ) ){
			$api_token = $settings['api_token'].'-'.$settings['api_dc'];
		}

		if ( isset( $api_settings['api_key'] ) ){
			$api_token = $api_settings['api_key'];
		}



		if ( ! $api_token ){
			$error = sprintf( __('To make full use of the Nth MailChimp Notifier plugin on your site, please <a href="%s" class="button  button-primary">Link it with your MailChimp account</a> or <a href="%s" class="button  button-primary">Add an API key</a>', 'nthmailchimp'), add_query_arg(array("nthmc_action" => "authorise"), home_url('index.php')), add_query_arg(array("page" => "nth-mailchimp-api"), admin_url('options.php')) );
			self::include_view( 'settings', array( 'error' => $error ) );

			self::include_view( 'activation_warning' );

			exit;
		}

		$mailchimp = new Mailchimp( $api_token );

		$data_for_view['template_list'] = $mailchimp->templates->getList();

		if ( isset( $settings['template_id'] ) && !empty( $settings['template_id'] ) ){
			$data_for_view['template_id'] = $settings['template_id'];
		} else{
			$data_for_view['template_id'] = 0;
		}


		$data_for_view['email_content']	= isset( $settings['email_content'] )? stripcslashes( htmlspecialchars_decode ( $settings['email_content'] ) ) : '' ;

		$data_for_view['list_list'] = $mailchimp->lists->getList();

		// Get the segments for the list.
		$data_for_view['segment_list'] = array();

				if ( isset( $_POST['_wpnonce-mailchimp-notifications'] ) ) {
						check_admin_referer( 'mailchimp-notifications', '_wpnonce-mailchimp-notifications' );

					$settings['template_id'] 		= isset( $_POST['template_id'] ) && !empty( $_POST['template_id'] ) && 'Please select ...' != $_POST['template_id'] ? esc_attr($_POST['template_id']) : null ;
					$settings['list_id'] 				= isset( $_POST['list_id'] ) && !empty( $_POST['list_id'] ) && 'Please select ...' != $_POST['list_id']? esc_attr($_POST['list_id']) : null ;
					$settings['segment_id'] 		= isset( $_POST['segment_id'] ) && !empty( $_POST['segment_id'] ) && 'Please select ...' != $_POST['segment_id']? esc_attr($_POST['segment_id']) : null ;

					$settings['enabled'] 				= isset( $_POST['enabled'] ) && !empty( $_POST['enabled'] )? 1 : 0 ;
					$settings['test_mode'] 			= isset( $_POST['test_mode'] ) && !empty( $_POST['test_mode'] )? 1 : 0 ;
					$settings['test_emails'] 		= isset( $_POST['test_emails'] ) && !empty( $_POST['test_emails'] )? esc_attr( $_POST['test_emails'] ) : null ;

					$settings['email_subject'] 	= isset( $_POST['email_subject'] ) && !empty( $_POST['email_subject'] )? esc_attr( $_POST['email_subject'] ) : null ;
					$settings['email_content'] 	= isset( $_POST['email_content'] ) && !empty( $_POST['email_content'] )? htmlspecialchars( balanceTags( $_POST['email_content'] ) ) : null ;
					$settings['email_text'] 		= isset( $_POST['email_text'] ) && !empty( $_POST['email_text'] )? esc_attr( $_POST['email_text'] ) : null ;

					self::save_settings( $settings );

					$data_for_view['template_id']		= $settings['template_id'];
					$data_for_view['list_id']  			= $settings['list_id'];
					$data_for_view['segment_id']		= $settings['segment_id'];

					$data_for_view['enabled']				= $settings['enabled'];
					$data_for_view['test_mode']			= $settings['test_mode'];
					$data_for_view['test_emails']		= $settings['test_emails'];
					$data_for_view['email_subject']	= $settings['email_subject'];

					$data_for_view['email_content']	= stripcslashes( htmlspecialchars_decode ( $settings['email_content'] ) ) ;
					$data_for_view['email_text']		= $settings['email_text'];

					$data_for_view['message'] 			= 'Congratulations, the settings have been saved.';
				}

				if ( isset( $settings['list_id'] ) && ! empty( $settings['list_id'] ) ){
						$data_for_view['segment_list'] = $mailchimp->lists->segments( $settings['list_id'] );
				}

				self::include_view( 'settings', $data_for_view );
		}


		static function api_page()
		{
				self::include_view( 'api' );
		}


		/**
		* Display a message informing the user to provide the API key
		*
		*/
		static public function api_activation_warning() {
				global $hook_suffix;

				if ( $hook_suffix == 'plugins.php' ) {
						self::include_view( 'activation_warning' );

		   	}
		}
}
