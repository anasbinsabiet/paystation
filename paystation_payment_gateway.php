<?php

class paystation_payment_gateway extends WC_Payment_Gateway
{
	function __construct()
	{
		$this->id                 	= 'paystation_payment_gateway';
		$this->method_title       	= __('Paystation Payment', 'paystation_payment_gateway');
		$this->title              	= __('Secure Pay with Paystation', 'paystation_payment_gateway');
		$this->icon = trailingslashit(WP_PLUGIN_URL) . plugin_basename(dirname(__FILE__)) . '/assets/icon.png';
		$this->has_fields = true;
		$this->description = $this->get_option('description');
		$this->supports = array(
			'products'
		);
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Turn these settings into variables we can use
		foreach ($this->settings as $setting_key => $value) {
			$this->$setting_key = $value;
		}

		// Save settings
		if (is_admin()) {
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		}
	}


	// Build the administration fields for this specific Gateway
	public function init_form_fields()
	{
		$this->form_fields = array(
			'ps_merchant_id' => array(
				'title'		=> __('Merchant ID', 'paystation_payment_gateway'),
				'type'		=> 'text',
				'desc_tip'	=> __('This is the Merchant ID provided by Paystation.', 'ps_merchant_id'),
			),
			'ps_password' => array(
				'title'		=> __('Password', 'paystation_payment_gateway'),
				'type'		=> 'password',
				'desc_tip'	=> __('This is the Password provided by Paystation.', 'ps_password'),
			)
		);
	}
}
