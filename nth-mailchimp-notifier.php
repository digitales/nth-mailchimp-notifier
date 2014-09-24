<?php
/*
Plugin Name: Nth MailChimp notifier
Description: A plugin to make it easier to setup and run email campaignes in MailChimp.
Version: 1.3
Author: Ross Tweedie
Author URI: http://nthdesigns.co.uk/
*/

if ( version_compare( $GLOBALS['wp_version'], '3.3', '<' ) || !function_exists( 'add_action' ) ) {
    if ( !function_exists( 'add_action' ) ) {
	$exit_msg = 'I\'m just a plugin, please don\'t call me directly';
    } else {
	$exit_msg = sprintf( __( 'This version of Most viewed requires WordPress 3.3 or greater.' ) );
    }
    exit( $exit_msg );
}

define( 'NTHMAILCHIMPPATH',   	trailingslashit( dirname( __FILE__ ) ) );
define( 'NTHMAILCHIMPDIR',   	trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );
define( 'NTHMAILCHIMPURL',	    plugin_dir_url( dirname( __FILE__ ) ) . NTHMAILCHIMPDIR );

load_plugin_textdomain( 'nthmailchimp', false, basename( dirname( __FILE__ ) ) . '/languages' );

// Set maximum execution time to 5 minutes - won't affect safe mode
$safe_mode = array( 'On', 'ON', 'on', 1 );
if ( !in_array( ini_get( 'safe_mode' ), $safe_mode ) && ini_get( 'max_execution_time' ) < 300 ) {
    @ini_set( 'max_execution_time', 300 );
}

global $nth_mailchimp;

/**
 * Now let's load the main classes, depending upon if we are in the admininstration system
 */
require_once( NTHMAILCHIMPPATH . 'classes/nth-mailchimp-core.php' );
if ( is_admin() ) {
    require_once( NTHMAILCHIMPPATH . 'classes/nth-mailchimp-admin.php' );
    $nth_mailchimp = new NthMailchimpAdmin();
} else {
    require_once( NTHMAILCHIMPPATH . 'classes/nth-mailchimp-frontend.php' );
    $nth_mailchimp = new NthMailchimpFrontend();
}

$nth_mailchimp->init();