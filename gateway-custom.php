<?php

/**
 * Plugin Name: Paystation Payment Gateway
 * Plugin URI: https://www.paystation.com.bd
 * Description: Paystation payment gateway use properly. If you're first time user then you should read Getting Started section first.
 * Version: 1.0.0
 * Author: Md. Anisur Rahaman
 * Author URI: https://anascloud.blogspot.com
 * Tested up to: 4.6.1
 * Text Domain: anascloud
 * Domain Path: /languages/
 *
 * @package Custom Gateway for WooCommerce
 * @author Md. Anisur Rahaman
 */


if (!defined('WPINC')) {
	die; // if accessed directly
}

// check woocommerce activation
$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if (!in_array('woocommerce/woocommerce.php', $active_plugins)) {
	return;
}

// Add custom place order button
// function add_button_after_place_order()
// {
// 	echo '<button type="button" class="button alt wp-element-button" id="track-order-button" style="display:none;">Place order</button>';
// }
// add_action('woocommerce_review_order_after_submit', 'add_button_after_place_order');

// Remove default Place order button
add_filter('woocommerce_order_button_html', 'remove_order_button_html');
function remove_order_button_html($button)
{
	$button = '<button type="button" class="button alt wp-element-button" id="track-order-button">Place order</button>';
	return $button;
}

// plugin directory
define('WOO_CUSTOM_PAYMENT_DIR', plugin_dir_path(__FILE__));

// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action('plugins_loaded', 'paystation_payment_gateway_init', 0);
function paystation_payment_gateway_init()
{

	// load text domain
	load_plugin_textdomain('paystation_payment_gateway', FALSE, WOO_CUSTOM_PAYMENT_DIR . '/languages/');

	// Lets add it too WooCommerce
	add_filter('woocommerce_payment_gateways', 'paystation_payment_gateway');
	function paystation_payment_gateway($methods)
	{
		$methods[] = 'paystation_payment_gateway';
		return $methods;
	}

	// include extended gateway class 
	include_once('paystation_payment_gateway.php');
}

add_action('wp_enqueue_scripts', 'my_plugin_enqueue_scripts');
function my_plugin_enqueue_scripts()
{
	wp_enqueue_script('paystation', plugin_dir_url(__FILE__) . 'assets/custom.js', array('jquery'), '1.0.0', true);
	wp_enqueue_style('paystation', plugins_url('assets/custom.css', __FILE__), false, '1.0.0', 'all');
}

add_action('woocommerce_after_order_notes', 'my_custom_content');
function my_custom_content()
{
	$payment_gateway_id = 'paystation_payment_gateway';
	$payment_gateways   = WC_Payment_Gateways::instance();
	$payment_gateway    = $payment_gateways->payment_gateways()[$payment_gateway_id];
	echo '<input type="hidden" id="baseurl" value=' . get_site_url()	. ' />';
	echo '<input type="hidden" id="cartTotal" value=' . WC()->cart->total	. ' />';
	echo '<input type="hidden" id="ps_merchant_id" value=' . $payment_gateway->ps_merchant_id . ' />';
	echo '<input type="hidden" id="ps_password" value=' . $payment_gateway->ps_password . ' />';
	echo '<input type="hidden" id="payment_url" value=' . plugin_dir_url(__FILE__) . "payment.php" . ' />';
}

add_action('wp_ajax_complete_order', 'complete_order_callback');
add_action('wp_ajax_nopriv_complete_order', 'complete_order_callback');
function complete_order_callback()
{
	$demo = $_POST["data"];
	$data = array();
	foreach ($demo as $item) {
		$data[$item['name']] = $item['value'];
	}
	$order_notes = $data['order_comments'];

	$billing_address = array(
		'first_name' => $data["billing_first_name"],
		'last_name'  => $data["billing_last_name"],
		'company'    => $data["billing_company"],
		'email'      => $data["billing_email"],
		'phone'      => $data["billing_phone"],
		'address_1'  => $data["billing_address_1"],
		'address_2'  => $data["billing_address_2"],
		'city'       => $data["billing_city"],
		'state'      => $data["billing_state"],
		'postcode'   => $data["billing_postcode"],
		'country'    => $data["billing_country"],
	);
	
	$shipping_address = array(
		'first_name' => $data["shipping_first_name"],
		'last_name'  => $data["shipping_last_name"],
		'company'    => $data["shipping_company"],
		'address_1'  => $data["shipping_address_1"],
		'address_2'  => $data["shipping_address_2"],
		'city'       => $data["shipping_city"],
		'state'      => $data["shipping_state"],
		'postcode'   => $data["shipping_postcode"],
		'country'    => $data["shipping_country"],
	);

	$order = wc_create_order();
	$payment_gateways = WC()->payment_gateways->payment_gateways();
	$order->set_payment_method($payment_gateways[$data["payment_method"]]);
	$cart = WC()->cart;
	foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
		$product_id = $cart_item['product_id'];
		$quantity = $cart_item['quantity'];

		$variation_attributes = $cart_item['variation'];

		$attributes = array();

		if (isset($variation_attributes['attribute_pa_color'])) {
			$attributes['Color'] = $variation_attributes['attribute_pa_color'];
		}

		if (isset($variation_attributes['attribute_pa_size'])) {
			$attributes['Size'] = $variation_attributes['attribute_pa_size'];
		}

		$order->add_product(get_product($product_id), $quantity, array(
			'variation' => $attributes,
		));
	}

	if (!empty(WC()->cart->get_applied_coupons())) {
		$applied_coupons = WC()->cart->get_applied_coupons();
		$order->apply_coupon($applied_coupons[0]);
	}

	$shipping_methods = array();
	$shipping_id = null;
	$shipping_label = null;
	$shipping_cost = null;
	$label_name = [];
	$method_title = [];
	$shippingMain = [];

	$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

	foreach ( WC()->cart->get_shipping_packages() as $package_id => $package ) {
		if ( WC()->session->__isset( 'shipping_for_package_'.$package_id ) ) {
			foreach ( WC()->session->get( 'shipping_for_package_'.$package_id )['rates'] as $shipping_rate_id => $shipping_rate ) {
				$rate_id = $shipping_rate->get_id();
				$shipping_costs = $shipping_rate->get_cost();
				$shippingSub = array(
					'rate_id'       => $shipping_rate->get_id(),
					'method_id'     => $shipping_rate->get_method_id(), // The shipping method slug
					'instance_id'   => $shipping_rate->get_instance_id(), // The instance ID
					'label_name'    => $shipping_rate->get_label(), // The label name of the method
					'shipping_cost' => $shipping_costs, // The cost without tax
					'tax_cost'      => $shipping_rate->get_shipping_tax(), // The tax cost
					'taxes'         => $shipping_rate->get_taxes(), // The taxes details (array)
				);
				$shippingMain[] = $shippingSub;
				foreach ($shippingMain as $shipping_rate) {
					if (in_array($shipping_rate['rate_id'], $chosen_shipping_methods)) {
						$shipping_cost = $shipping_rate['shipping_cost'];
						$shipping_label = $shipping_rate['label_name'];
						$shipping_id = $shipping_rate['rate_id'];
						break;
					}
				}

			}
		}
	}

	$ship_rate_ob = new WC_Shipping_Rate();
	$ship_rate_ob->id=$shipping_id;
	$ship_rate_ob->label=$shipping_label;
	$ship_rate_ob->taxes=array();
	$ship_rate_ob->cost=$shipping_cost;

	// wp_send_json(["shipping_cost" => $shipping_cost, "ship_rate_ob" => $ship_rate_ob, "shippingMain" => $shippingMain]);

	$order->add_shipping($ship_rate_ob);
	$order->add_order_note($order_notes);
	$order->calculate_totals();
	$order->set_customer_ip_address(WC_Geolocation::get_ip_address());
	$order->set_customer_user_agent(wc_get_user_agent());
	$order->set_address($billing_address, 'billing');
	$order->set_address($shipping_address, 'shipping');
	$order_id = $order->get_id();
	update_post_meta($order_id, '_customer_user', get_current_user_id());

	$order_key = get_post_meta($order_id, '_order_key', true);
	$returnURL = site_url() . '/checkout/order-received/' . $order_id . '/?key=' . $order_key;

	wp_send_json(["success" => true, "order_id" => $order_id, "order_key" => $order_key, "returnURL" => $returnURL]);
}

add_action('woocommerce_thankyou', 'add_thank_you_message');
function add_thank_you_message($order_id)
{
	$payment_status = $_GET['status'] ?? 'Pending payment';
	$order = wc_get_order($order_id);
	if ($payment_status == 'Successful') {
		$order->update_status('completed');
	} elseif ($payment_status === 'Canceled') {            
		$order->update_status('cancelled');
	}
	$order = wc_get_order($order_id);
}

add_action('woocommerce_thankyou', 'display_payment_status', 10);

function display_payment_status($order_id)
{
    $order = wc_get_order($order_id);
    $payment_status = $order->get_status();
	echo '<input type="hidden" id="wc_payment_status" value="' . esc_attr($payment_status) . '" />';

}
