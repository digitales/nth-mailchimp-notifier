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

		$api_settings = self::get_settings('nthmc_api_key', true );
		$settings = self::get_settings(null, true );

		$api_token = $api_key = null;

		$api_token = isset( $settings['api_token'] ) && !empty( $settings['api_token'] )? true : false;
		$api_key = isset( $api_settings['api_key'] ) && !empty( $api_settings['api_key'] )? true : false;

		if ( ( ! $api_key && ! $api_token )
			|| ( !isset( $api_token) && $api_key &&  !isset($_POST['submit'] ) ) ) {
			add_action('admin_notices', array( __CLASS__, 'api_activation_warning') );
		}

		// Actions are added via the Nth Mailchimp Core

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

		add_options_page( 'MailChimp notifications', 'MailChimp notifications', 'edit_plugins', 'nth-mailchimp-settings', array( __CLASS__,'settings_page' ) );
		add_submenu_page( null, 'Send test email', 'Send test email', 'edit_posts', 'send-test-email', array( __CLASS__,'new_post_notification' ) );
		add_submenu_page( null, 'Nth MailChimp API', 'Nth MailChimp API', 'edit_posts', 'nth-mailchimp-api', array( __CLASS__,'api_settings' ) );

	}


	/**
	* Add the post type metaboxes
	*
	* @param void
	*
	* @return void
	*/
	public static function add_metaboxes()
	{
		add_meta_box('nth_mailchimp_notifications', 'MailChimp Notifications', array( __CLASS__, 'meta_boxes'), 'post', 'side', 'default');
	}


	/**
	* The post type metaboxes
	*
	* @param void
	*
	* @return void
	*/
	static function meta_boxes( $post )
	{
		$post_id = $notification_sent = $notification_sent_at = $send_notification = null;

		if ( isset( $post ) && isset( $post->ID ) ) {
			$post_id = $post->ID;
		}

		// Let's get the details for this post.
		if ( $post_id ){
			$notification_sent 		= get_post_meta( $post_id, 'notification_sent', true );
			$notification_sent_at = get_post_meta( $post_id, 'notification_sent_at', true );
			$notification_content = get_post_meta( $post_id, 'notification_content', true );

			$send_notification 		= get_post_meta( $post_id, '_send_notification', true );
		}

		if ( $notification_sent ){
			echo '<p>'.__('A notification has already been sent to the subscribers' ).'</p>';
			if ( $notification_sent_at ){

				$date_format = ( '' == $date_format )? get_option('date_format') : $date_format ;

				$date = new DateTime( $notification_sent_at );

				echo 'Notification sent: <b>'. $date->format( $date_format ).'</b>';
			}
			return true;
		}

		echo '<label for="_send_notification">';
		echo '<input type="checkbox" id="_send_notification" name="_send_notification" value="1" ';
		echo isset( $send_notification ) && 1 == $send_notification ? ' checked="checked" ': '';
		echo '/> ';
		_e('Email subscribers about this blog post');
		echo '</label>';

		// Get the MailChimp details

		$api_settings = NthMailChimpCore::get_settings('nthmc_api_key', true );
		$settings = NthMailChimpCore::get_settings(null, true );

		echo '<p><strong>MailChimp notification settings</strong></p>';
		echo '<p>';
		echo 'Notifications are <b>';
		echo isset( $settings['enabled'] ) && 1 == $settings['enabled']? 'enabled' : 'disabled';
		echo '</b></p>';

		echo '<p>';
		echo 'Test mode is <b>';
		echo isset( $settings['test_mode'] ) && 1 == $settings['test_mode']? 'active' : 'disabled';
		echo '</b></p>';

		if ( isset( $settings['list_name'] ) ){
			echo '<p>Subscriber list: <b>'.$settings['list_name'].'</b></p>';
		}

		if ( isset( $settings['segment_name'] ) ){
			echo '<p>List segment: <b>'.$settings['segment_name'].'</b></p>';
		}

		if ( isset( $settings['template_name'] ) ){
			echo '<p>Template name: <b>'.$settings['template_name'].'</b></p>';
		}

		echo sprintf( '<a href="%1$s" class="button">%2$s</a></p>', admin_url( 'options-general.php?page=nth-mailchimp-settings'), __('Change the settings') );

	}


	/**
	* Save the form data post type meta data
	*
	* @param integer $post_id
	* @param object Wp_Post $post
	*
	* @return void
	*/
	static function save_meta( $post_id, $post ) {

		$action = isset( $_GET['action'] ) && !empty( $_GET['action'] )? esc_attr( $_GET['action'] ) : null ;

		if ( 'trash' == $action ||  'delete' == $action ){
			return null;
		}

		$post_type = get_post_type();

		if ( 'post' !==  $post_type ){ return $post->ID; }

		if ( !current_user_can( 'edit_post' , $post->ID )){ return $post->ID; }

		$data = array();

		if( $post->post_type == 'revision' ){
			return; // Don't store custom data twice
		}

		$send_notification = isset( $_POST[ '_send_notification' ] ) && !empty( $_POST['_send_notification'])? 1 : null ;

		if( get_post_meta( $post->ID, '_send_notification', FALSE ) ) { // If the custom field already has a value
			update_post_meta( $post->ID, '_send_notification', $send_notification );
		}else { // If the custom field doesn't have a value
			add_post_meta( $post->ID, '_send_notification', $send_notification );
		}

		//if(!$value){
		//	delete_post_meta( $post->ID, $field_name ); // Delete if blank
		//}
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


			$settings['template_id'] 	= isset( $_POST['template_id'] ) && !empty( $_POST['template_id'] ) && 'Please select ...' != $_POST['template_id'] ? esc_attr($_POST['template_id']) : null ;
			$settings['list_id'] 			= isset( $_POST['list_id'] ) && !empty( $_POST['list_id'] ) && 'Please select ...' != $_POST['list_id']? esc_attr($_POST['list_id']) : null ;
			$settings['segment_id'] 	= isset( $_POST['segment_id'] ) && !empty( $_POST['segment_id'] ) && 'Please select ...' != $_POST['segment_id']? esc_attr($_POST['segment_id']) : null ;

			$settings['enabled'] 			= isset( $_POST['enabled'] ) && !empty( $_POST['enabled'] )? 1 : 0 ;
			$settings['test_mode'] 		= isset( $_POST['test_mode'] ) && !empty( $_POST['test_mode'] )? 1 : 0 ;
			$settings['test_emails'] 	= isset( $_POST['test_emails'] ) && !empty( $_POST['test_emails'] )? esc_attr( $_POST['test_emails'] ) : null ;

			$settings['email_subject'] 	= isset( $_POST['email_subject'] ) && !empty( $_POST['email_subject'] )? esc_attr( $_POST['email_subject'] ) : null ;
			$settings['email_content'] 	= isset( $_POST['email_content'] ) && !empty( $_POST['email_content'] )? htmlspecialchars( balanceTags( $_POST['email_content'] ) ) : null ;
			$settings['email_text'] 		= isset( $_POST['email_text'] ) && !empty( $_POST['email_text'] )? esc_attr( $_POST['email_text'] ) : null ;

			$settings['template_name'] = isset( $data_for_view['template_list'] )? self::get_template_name( $settings['template_id'], $data_for_view['template_list']  ) : null ;

			$settings['list_name'] = isset( $data_for_view['list_list'] )? self::get_list_name( $settings['list_id'], $data_for_view['list_list']  ) : null ;

			if ( isset( $settings['list_id'] ) &&!empty( $settings['list_id'] ) ){
				$data_for_view['segment_list'] = $mailchimp->lists->segments($settings['list_id']);

				$settings['segment_name'] = isset( $data_for_view['segment_list'] )? self::get_segment_name( $settings['segment_id'], $data_for_view['segment_list']  ) : null ;

			}

			self::save_settings( $settings );

			$data_for_view['template_id']  	= $settings['template_id'];
			$data_for_view['list_id']  		= $settings['list_id'];
			$data_for_view['segment_id']	= $settings['segment_id'];

			$data_for_view['enabled']		= $settings['enabled'];
			$data_for_view['test_mode']		= $settings['test_mode'];
			$data_for_view['test_emails']	= $settings['test_emails'];
			$data_for_view['email_subject']	= $settings['email_subject'];

			$data_for_view['email_content']	= stripcslashes( htmlspecialchars_decode ( $settings['email_content'] ) ) ;
			$data_for_view['email_text']	= $settings['email_text'];

			$data_for_view['message'] = 'Congratulations, the settings have been saved.';
		}

		if ( isset( $settings['list_id'] ) &&!empty( $settings['list_id'] ) ){
			$data_for_view['segment_list'] = $mailchimp->lists->segments($settings['list_id']);
		}

		self::include_view( 'settings', $data_for_view );
	}


	static function get_template_name( $template_id, $template_list )
	{
		// Let's get the template name
		foreach ( $template_list AS $group_name => $group_templates ){
			if ( is_array( $group_templates ) && 0 < count( $group_templates )  ){
				foreach( $group_templates AS $template_details )
				{
					if ( $template_details[ 'id' ] == $template_id ){
						return $template_details['name'];
					}
				}
			}
		}
		return false;
	}


	static function get_segment_name( $segment_id, $segment_list ){

		if ( !isset( $segment_list['saved'] ) ){
			return false;
		}

		// Let's get the template name
		foreach ( $segment_list['saved'] AS $details ){
			if ( $details[ 'id' ] == $segment_id ){
				return $details['name'];
			}
		}
		return false;


	}


	static function get_list_name( $list_id, $list_list ){

		if ( !isset( $list_list['data'] ) ){
			return false;
		}

		// Let's get the template name
		foreach ( $list_list['data'] AS $list_details ){
			if ( $list_details[ 'id' ] == $list_id ){
				return $list_details['name'];
			}
		}
		return false;
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
