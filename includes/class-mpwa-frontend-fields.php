<?php
/**
 * Checkout page field
 * @since      1.0.0
 * @package    WooCommerce Checkout MailPoet Newsletter Subscribe
 * @subpackage woocommerce-checkout-mailpoet-newsletter-subscribe/includes
 * @author     Kai Mindermann and Tikweb <kasper@tikjob.dk>
 */

if(!class_exists('MPWA_Frontend_Fields')){
	class MPWA_Frontend_Fields
	{
		//Helper trait
		use MPWA_Helper_Function;

		//Properties
		public $form_position;
		public $show_form;
		public $default_status;
		public $list_ids;
		private static $_this_class = NULL;

		/**
		 * Initialize the class
		 */
		public static function init()
		{
			if(empty(self::$_this_class)){
				self::$_this_class = new self;
			}
			return self::$_this_class;
		}//Edn of init

		/**
		 * Constructor
		 */
		private function __construct()
		{
			$this->run_actions();
		}//End of __construct

		/**
		 * Run necessary hooks
		 * Show subscribe form
		 */
		public function run_actions()
		{
			//Get the form showing position
			$this->form_position = get_option('wc_'.$this->tab_slug.'_subscription_position');

			//Enable show subscription form
			$this->show_form = get_option('wc_'.$this->tab_slug.'_enable_subscription');

			//Get default checkbox status
			$this->default_status = get_option('wc_'.$this->tab_slug.'_checkbox_status');

			//Get default checkbox status
			$this->multi_subscription = get_option('wc_'.$this->tab_slug.'_multi_subscription');

			// Display GDPR consent
			$this->show_gdpr_consent = get_option('wc_'.$this->tab_slug.'_show_gdpr_consent');

			// Privacy page
			$this->privacy_page = get_option('wp_page_for_privacy_policy');

			if ( !empty($this->privacy_page) ){
				$this->privacy_page = get_permalink( $this->privacy_page );

				if ( !empty($this->privacy_page) ){
					$this->privacy_page = '<a href="'. $this->privacy_page .'" target="_blank">Privacy Policy</a>';
				} else {
					$this->privacy_page = '[privacy_policy]';
				}
			}

			// GDPR Consent Text
			$this->gdpr_consent_text = get_option('wc_'.$this->tab_slug.'_gdpr_subscription_consent_text');

			// Display GDPR unsubscribe option
			$this->show_gdpr_unsub = get_option('wc_'.$this->tab_slug.'_gdpr_show_unsubscribe');

			// GDPR unsubscribe label text
			$this->gdpr_unsub_label = get_option('wc_'.$this->tab_slug.'_gdpr_unsubscribe_label');


			//Subscription Lists selected
			$this->list_ids = get_option('wc_mailpoet_segment_list', []); 

			//If tick the `Enable subscription` checkbox
			if('yes' == $this->show_form){
				//Hook into the checkout page. Adds the subscription fields.
				add_action('woocommerce_'.$this->form_position, array($this, 'checkout_page_form'));
			}
		}//End of run_actions

		/**
		 * Checkout page form
		 */
		public function checkout_page_form()
		{
			?>
			<div class="mailpoet-subscription-section" style="clear:both;">
				
				<?php if(('yes' == $this->multi_subscription) && !empty($this->list_ids)): ?>
					<h3><?php $this->_e('Subscribe to Newsletters'); ?></h3>
					<?php
						$lists = MailPoet\API\API::MP('v1')->getLists();
						// filter out not configured lists
						foreach ($lists as $key=>$list){
							
							if (!in_array($list['id'], $this->list_ids)){
									unset($lists[$key]);
							}
						}	
						if(is_array($lists)): foreach($lists as $list):
					?>
					<p class="form-row form-row-wide mailpoet-subscription-field" id="mailpoet-list-<?php echo $list['id']; ?>">
						<label>
							<input class="input-checkbox" name="mailpoet_multi_subscription[]" value="<?php echo $list['id']; ?>" type="checkbox" <?php checked($this->default_status, 'checked'); ?> > 
							<?php echo $list['name']; ?>
						</label>
					</p>

				<?php endforeach; ?>

					<?php if( $this->show_gdpr_consent == 'yes' ): ?>
						<p class="form-row form-row-wide mailpoet-subscription-field-gdpr">
							<?php echo str_replace('[privacy_policy]', $this->privacy_page, $this->gdpr_consent_text); ?>
						</p>
					<?php endif; ?>

					<?php if( $this->show_gdpr_unsub == 'yes'): ?>
						<p class="form-row form-row-wide mailpoet-subscription-field-gdpr">
							<label>
								<input type="checkbox" class="input-checkbox" name="gdpr_unsubscribe"> <?php echo $this->gdpr_unsub_label; ?>
							</label>
						</p>
					<?php endif; ?>
				
				<?php endif; ?>
				<?php else: ?>
					<h3><?php $this->_e('Subscribe to Newsletter'); ?></h3>
					<?php
						// Subscribe Checkbox Label
						$checkout_label = get_option('wc_'.$this->tab_slug.'_checkout_label'); 
						// Puts default label if not set in the settings.
						$subscribe_label = !empty($checkout_label) ? $checkout_label : $this->__('Yes, please subscribe me to the newsletter.');
					?>
					<p class="form-row form-row-wide mailpoet-subscription-field">
						<label>
							<input class="input-checkbox" name="mailpoet_checkout_subscribe" value="1" type="checkbox" <?php checked($this->default_status, 'checked'); ?> > 
							<?php echo $subscribe_label; ?>
						</label>
					</p>

					<?php if( $this->show_gdpr_consent == 'yes' ): ?>
						<p class="form-row form-row-wide mailpoet-subscription-field-gdpr">
							<?php echo str_replace('[privacy_policy]', $this->privacy_page, $this->gdpr_consent_text); ?>
						</p>
					<?php endif; ?>

					<?php if( $this->show_gdpr_unsub == 'yes'): ?>
						<p class="form-row form-row-wide mailpoet-subscription-field-gdpr">
							<label>
								<input type="checkbox" class="input-checkbox" name="gdpr_unsubscribe"> <?php echo $this->gdpr_unsub_label; ?>
							</label>
						</p>
					<?php endif; ?>

				<?php endif; ?>
			</div>
			<?php
		}//End of checkout_page_form

	}//End of class

	/**
	 * Instentiate class after posts selection
	 */
	$MPWA_Frontend_Fields = '';
	function mpwa_frontend_fields_init_posts_selection(){
		global $MPWA_Frontend_Fields;
		if(function_exists('is_checkout') && is_checkout() && empty($MPWA_Frontend_Fields)){
			$MPWA_Frontend_Fields = MPWA_Frontend_Fields::init();
		}
	}
	add_action('posts_selection', 'mpwa_frontend_fields_init_posts_selection');
}//End if