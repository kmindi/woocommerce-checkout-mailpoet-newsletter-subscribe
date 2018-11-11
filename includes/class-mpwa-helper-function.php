<?php
/**
 * Plugin helper functions.
 * @since      1.0.0
 * @package    WooCommerce Checkout MailPoet Newsletter Subscribe
 * @subpackage woocommerce-checkout-mailpoet-newsletter-subscribe/includes
 * @author     Kai Mindermann and Tikweb <kasper@tikjob.dk>
 */
trait MPWA_Helper_Function
{

	//Properties
	public $tab_slug = 'mailpoet';

	/**
	 * Translateable text method
	 * @uses return translate text
	 */
	public static function __($text)
	{
		return __($text, 'add-on-woocommerce-mailpoet');
	}//End of __


	/**
	 * Translateable text method
	 * @uses print translate text
	 */
	public function _e($text)
	{
		echo $this->__($text);
	}//End of __


}//End of trait