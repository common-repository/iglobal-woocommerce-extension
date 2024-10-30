<?php
  if ( ! defined( 'ABSPATH' ) ) exit;

  get_header();

  $store_id = get_option('iglobal_store_id');
  $store_key = get_option('iglobal_store_key');
  $subdomain = get_option('iglobal_subdomain');
  $test_orders = get_option('iglobal_import_tests');

  $store = new IgWC($store_id, $store_key);

  $order_id = explode( '=', $_SERVER['REQUEST_URI'] )[1];
  $order = $store->import_order($order_id);
  if($order->order->merchantOrderId) {
    $order_id =$order->order->merchantOrderId;
  }
  WC()->cart->empty_cart($clear_persistent_cart=true);
  ?>
  <div class="center-column">

    <h3>Thank You for Your Order!</h3>

    <p>Your Order Number is: #<span id="_orderNumber"><?php echo $order_id; ?></span></p>
    <p>
      You will receive an e-mail shortly from <?php echo get_bloginfo('name'); ?> containing the details of your order.
      Your credit card statement will show a purchase of
      <span id="_totalLabel">$<?php echo $order->order->grandTotal; ?> USD</span>.
      We look forward to your next order!
    </p>
  </div>
  <?php

  get_footer();

