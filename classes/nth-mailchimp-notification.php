<?php
/**
 * MailChimp notifier notifications
 *
 * A set of functions to send notifications
 *
 * @author rtweedie
 * @package nth mailchimp
 * @since 1.2
 * @version 1.5
 */

include_once( NTHMAILCHIMPPATH . 'classes/nth-mailchimp-core.php' );

include_once( NTHMAILCHIMPPATH . 'vendor/mailchimp-api/src/Mailchimp.php' );

class NthMailChimpNotification{

		static $test_mode = false;
		
		 // The notifications will only
	  static $test_domains = array( '.dev', '.local', 'drewlondon', 'duefriday' );

		static function new_post_notification( $post, $post_id = null ){
				self::send_notification($post, $post_id );
		}

		static function post_notification( $post_id, $post = null ){
				self::send_notification($post, $post_id );
		}


		/**
		 *  Check if it is ok to send the notification
		 *
		 * @param integer $post_id The post ID
		 * @param object WP_post $post    The WP Post
		 *
		 * @return false || integer
		 */
		static function ok_to_send_notifications( $post_id, $post )
		{
				$send_notification = $notification_sent = null;
				

				// We only want to send the notifications for blog posts being published.
				// Other content types don't need to send notifications.
				if( 'post' !== $post->post_type ){
					return false;
				}

				if ( 'publish' !== $post->post_status ){
						return false;
				}

				$notification_sent = get_post_meta( $post->ID, 'notification_sent', true );
				$send_notification = get_post_meta( $post->ID, '_send_notification', true );

				if ( $notification_sent ){
						return false;
				}

				if ( ! $send_notification ){
						return false;
				}

				$current_user = wp_get_current_user();

				$ignore_user_updates = false;
				$ignore_user_updates = apply_filters( 'nth_mailchimp_ignore_user_updates', $current_user->data->ID );
				
				if ( true == $ignore_user_updates ){
						self::$test_mode = true;
						return 2;
				}

				$domain = $_SERVER['HTTP_HOST'];

				$test_domains = self::$test_domains;
								
				$test_domains = apply_filters( 'nth_mailchimp_test_domains', $test_domains );
				
				foreach( $test_domains AS $the_domain )
				{
						if ( strpos( $domain, $the_domain ) !== false ){
								self::$test_mode = true;
								return 3;
						}
				}

				// We've got this far, so let's send the notification
				return true;
		}


		static function send_notification( $post, $post_id = null )
		{
				if ( ! $post_id ){
						$post_id = isset( $_GET['post_id'] ) && !empty( $_GET['post_id'] )? (int)$_GET['post_id'] : 0;
				}

				if ( ! $post_id ){
					$post_id = $post->ID;
				}

				$send_notification = self::ok_to_send_notifications( $post_id, $post );
				
				if ( false == $send_notification ){
						return false;
				}

				$content_sections = $notification_content = array();

				$send_notification = $date_format = $test_mode = false;

				$the_post = get_post( $post_id );

				// We don't want to bombard the users with notifications.
				$notification_sent = get_post_meta( $post_id, 'notification_sent', true );

				if( $notification_sent ){
					return false;
				}

				// We only want to send notifications if they option is selected

				$send_notification = get_post_meta( $post_id, '_send_notification', true );

				if( 1 != $send_notification ){
					return false;
				}

				$api_settings = NthMailChimpCore::get_settings('nthmc_api_key', true );
				$settings = NthMailChimpCore::get_settings(null, true );

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

				$subject = NthMailChimpCore::process_tags( $settings['email_subject'], $the_post );

				$html_content = NthMailChimpCore::process_tags( wpautop( $settings['email_content'] ), $the_post );

				$text_content = NthMailChimpCore::process_tags( $settings['email_text'], $the_post );

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

				// Let's save the contents of the notification for future reference
				$date = new DateTime();

				$notification_content = array();
				$notification_content['subject'] 			= $subject;
				$notification_content['html'] 				= $html_content;
				$notification_content['text'] 				= $text_content;
				$notification_content['segment_id'] 	= $settings['segment_id'];
				$notification_content['date'] 				= $date->getTimestamp();
				$notification_content['campaign_id']	= $result['id'];

				if ( isset( $settings['test_mode'] ) && 1 == $settings['test_mode'] ){
						$test_mode = true;
				}

				if ( isset( self::$test_mode ) && true == self::$test_mode ){
						$test_mode = true;
				}

				// Now we have created a campaign, should we send it?
				if ( true == $test_mode ){

						$email_addresses = explode(',', $settings['test_emails'] );

						foreach( $email_addresses AS $key => $address ){
								$email_addresses[$key] =  str_replace( '&amp;', '&', trim( $address) );
						}

						$test_result = $mailchimp->campaigns->sendTest( $campaign_id, $email_addresses );

						return true;

				} else {
						$mailchimp->campaigns->send( $campaign_id );

						update_post_meta( $post_id, 'notification_sent', true );
						update_post_meta( $post_id, 'notification_sent_at', $date->getTimestamp() );
						update_post_meta( $post_id, 'notification_content', $notification_content );

						return true;
				}
		}
		
		
		/**
		 * Filter to test if we should send notifications depending upon the current domain
		 *
		 * @param array $domains
		 *
		 * @return array
		 */
		static function test_domains( $domains ){				
				return $domains;
		}
		
}
