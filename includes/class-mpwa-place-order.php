<?php
/**
 * Run actions after place order
 * @since      1.0.0
 * @package    WooCommerce Checkout MailPoet Newsletter Subscribe
 * @subpackage woocommerce-checkout-mailpoet-newsletter-subscribe/includes
 * @author     Kai Mindermann and Tikweb <kasper@tikjob.dk>
 */

use MailPoet\Models\Subscriber;
use MailPoet\Subscribers\ConfirmationEmailMailer;

if(!class_exists('MPWA_Place_Order')){
	class MPWA_Place_Order
	{
		//Helper trait
		use MPWA_Helper_Function;

		/**
		 * Get user information and subscribe
		 */
		public static function subscribe_user($errors = '')
		{
			//If submited form has no error
			if(empty($errors->errors)){
				
				//Form Data
				$posted_data = $_POST;

				//If Multi-Subscription enable
				if(isset($posted_data['mailpoet_multi_subscription'])){

					$list_id_array = $posted_data['mailpoet_multi_subscription'];
					self::save_subscriber_record($list_id_array, $posted_data);

				}elseif(isset($posted_data['mailpoet_checkout_subscribe']) && !empty($posted_data['mailpoet_checkout_subscribe'])){
					
					$list_id_array = get_option('wc_mailpoet_segment_list');	
					self::save_subscriber_record($list_id_array, $posted_data);

				}//End if

				// If unsubscribe requested.
				if ( isset($posted_data['gdpr_unsubscribe']) && $posted_data['gdpr_unsubscribe'] == 'on' ){

					self::unsubscribe_user( $posted_data );

				} //End if

			}//End if
			
		}//End of subscribe_user

		/**
		 * Save subscriber record
		 */
		public static function save_subscriber_record($list_id_array = '', $posted_data)
		{
			//List id array must not be empty
			if(is_array($list_id_array) && !empty($list_id_array)){

				$subscribe_data = array(
					'email'     => $posted_data['billing_email'],
					'first_name' => $posted_data['billing_first_name'],
					'last_name'  => $posted_data['billing_last_name'],
					'segments' => $list_id_array
				);

				//Get `Enable Double Opt-in` value
				$double_optin = get_option('wc_mailpoet_double_optin', 'yes'); 
				if(!$double_optin) {
					// if option is not set, it will be false
					$options = array(
						'send_confirmation_email' => true, 
					);
				} else {
					$options = array(
						'send_confirmation_email' => $double_optin == 'yes' ? true : false, 
					);
				}

				// Get subscriber if he/she exists
				try {
					$subscriber = MailPoet\API\API::MP('v1')->getSubscriber($subscribe_data['email']); 
					// if the subscriber exists, subscribe him to the lists
					try {
						$subscriber = \MailPoet\API\API::MP('v1')->subscribeToLists($subscriber, $lists, $options);
					} catch(Exception $exception) {
						// return $exception->getMessage();
					}

				} catch(Exception $exception) {
					//This subscriber does not exist

					// create the subscriber and subscribe to lists
					try {
						$subscriber = \MailPoet\API\API::MP('v1')->addSubscriber($subscribe_data, $list_id_array, $options);
					} catch(Exception $exception) {
						// return $exception->getMessage();
					}
				}

				// Display success notice to the customer.
				if($subscriber !== false){
					if($options['send_confirmation_email']) {
						wc_add_notice( 
							apply_filters(
								'mailpoet_woocommerce_subscribe_confirm', 
								self::__('We have sent you an email to confirm your newsletter subscription. Please confirm your subscription. Thank you.') 
							) 
						);
					} else {
						wc_add_notice( 
							apply_filters(
								'mailpoet_woocommerce_subscribe_thank_you', 
								self::__('Thank you for subscribing to our newsletters.') 
							) 
						);
					}
				//Show error notice if unable to save data
				}else{
					self::subscribe_error_notice();
				
				}//End of if $subscriber !== false
			}
		}//End of save_subscriber_record

		/**
		 * Unsubscribe User
		 */
		public static function unsubscribe_user( $posted_data )
		{

			$email = isset($posted_data['billing_email']) ? $posted_data['billing_email'] : false;
			
			try {
				$subscriber = MailPoet\API\API::MP('v1')->getSubscriber($email); // $identifier can be either a subscriber ID or e-mail
			} catch(Exception $exception) {
				// return $exception->getMessage();
			}

			if ( $subscriber !== false ){
				try {
					$subscriber = MailPoet\API\API::MP('v1')->unsubscribeFromLists($subscriber, \MailPoet\API\API::MP('v1')->getLists()); 
				} catch(Exception $exception) {
					// return $exception->getMessage();
				}

				wc_add_notice( 
					apply_filters(
						'mailpoet_woocommerce_unsubscribe_confirm', 
						self::__('You will no longer receive our newletter! Feel free to subscribe our newsletter anytime you want.') 
					)
				);

			}

		} // End of unsubscribe_user

		/**
		 * Save data Error notice
		 */
		public static function subscribe_error_notice()
		{
			wc_add_notice( 
				apply_filters( 
					'mailpoet_woocommerce_subscribe_error', 
					self::__('There appears to be a problem subscribing you to our newsletters. Please let us know so we can manually add you ourselves. Thank you.') 
				), 
				'error' 
			);
		}//End of subscribe_error_notice

	}//End of class

}//End if