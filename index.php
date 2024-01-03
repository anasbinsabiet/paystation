<?php // Silence is golden

// Add custom place order button
function add_button_after_place_order()
{
    echo '<button type="button" class="button" id="track-order-button">Place order</button>';
}
add_action('woocommerce_review_order_after_submit', 'add_button_after_place_order');

// Remove default Place order button
add_filter('woocommerce_order_button_html', 'remove_order_button_html');
function remove_order_button_html($button)
{
    $button = '';
    return $button;
}
