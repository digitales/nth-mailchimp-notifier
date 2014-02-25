<?php
/**
 * Nth MailChimp Notifier Logging functions
 *
 * A set of functions to log messages for the plugin
 *
 * @author rtweedie
 * @package scribblar
 * @since 1.0
 * @version 1.o
 * 
 */
class NthMailChimpLogging{
	
    private static $log_dir_path = '';
	private static $log_dir_url  = '';
    private static $error_log_filename = 'nth-mailchimp-error.log';
    private static $message_log_filename = 'nth-mailchimp-message.log';
    private static $extended_log_filename = 'nth-mailchimp-extended.log';
    private static $text_domain = 'nthmailchimp';

    function __construct(){
        self::init();
    }

	
    static function init(){
        $upload_dir = wp_upload_dir();

        self::$log_dir_path = trailingslashit( $upload_dir['basedir'] ). '/logs';
		self::$log_dir_url  = trailingslashit( $upload_dir['baseurl'] ) . '/logs';

         if ( !is_dir( self::$log_dir_path ) ){
            mkdir( self::$log_dir_path );
            chmod( self::$log_dir_path, 766 );
        }
    }
	

	/**
	 * Check and create the file
	 *
	 * Check and create the file if it doesn't exist
	 *
	 * @param string $filepath
	 *
	 * @return true
	 */
    private function check_and_create_file( $filepath ){
        if ( ! file_exists( $filepath ) ){
            $handle = fopen( $filepath, 'w' );
            fclose( $handle );
        }
        return true;
    }

	
    /**
	 * Log errors to a file
	 *
	 * @since 0.1
	 **/
	public static function log( $errors, $type = 'error' ) {
		if ( empty( $errors ) ){
			return;
		}

        if ( ! self::$log_dir_path ) {
            self::init();
        }

        switch( $type ){
            case 'message':
                $filename = self::$message_log_filename;
                break;
            case 'extended':
                $filename = self::$extended_log_filename;
                break;
            default:
                $filename = self::$error_log_filename;
                break;
        }

        self::check_and_create_file( self::$log_dir_path . '/' . $filename );

		$log = @fopen( self::$log_dir_path . '/' . $filename, 'a' );
		@fwrite( $log, sprintf( __( 'BEGIN %s' , self::$text_domain ), date( 'Y-m-d H:i:s', time() ) ) . "\n" );

        if ( is_array( $errors ) ){
            foreach ( $errors as $key => $error ) {
                $line = $key + 1;
                $message = is_object( $error )? $error->get_error_message() : $error ;
                @fwrite( $log, sprintf( __( '[Line %1$s] %2$s' , self::$text_domain ), $line, $message ) . "\n" );
            }
        } else {
            $message = is_object( $errors )? $errors->get_error_message() : $errors ;
            @fwrite( $log, sprintf( __( '[Line %1$s] %2$s' , self::$text_domain ), 1, $message ) . "\n" );
        }
		@fclose( $log );
	}
}