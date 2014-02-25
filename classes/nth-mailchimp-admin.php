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
		
		add_action('init', array( __CLASS__, 'register_styles_and_scripts' ) );
		
		add_action('admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		
		$settings = self::get_settings();
		if ( ( isset( $settings['api_token'] ) && empty( $settings['api_token'] ) )
			|| ( !isset( $settings['api_token']) &&  !isset($_POST['submit'] ) ) ) {
			add_action('admin_notices', array( __CLASS__, 'api_activation_warning') );
		}
		
    }

	static function register_styles_and_scripts()
    {
		// Stub
    }
	
	
	static function enqueue_scripts( $hook )
	{
		// Stub
	}
    
    
    static function admin_menu()
    {
		
		add_options_page( 'MailChimp notifications', 'MailChimp notifications', 'edit_plugins', 'nth-mailchimp-settings', array( __CLASS__,'settings_page' ) );
		
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
	

	static function settings_init()
	{
		// Stub	
	}
	
	static function print_section_info()
	{
	}
	
	static function print_account_info()
	{
	}
	
	
	static function settings_page()
	{	
		$data_for_view = array();
		
		$settings = self::get_settings();
		
		/*
		 * Let's query the API to retrieve:
		 * * list of templates
		 * * List of lists
		 * * List of segments in the list
		 */
		
		$api_token = $settings['api_token'].'-'.$settings['api_dc'];
		
		if ( ! $api_token ){
			
			self::include_view( 'settings' );
			
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
		
		var_dump( $data_for_view['template_list']);
		
		
		$data_for_view['list_list'] = $mailchimp->lists->getList();
		
		
		
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


 
