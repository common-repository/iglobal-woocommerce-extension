<?php
  if( !defined( 'ABSPATH' ) ) {
    exit;
  }
  class WC_Gateway_iGlobal extends WC_Payment_Gateways {
    public function __construct(){
      $this->id = 'iglobal_payment';
      $this->has_fields = false;
      $this->method_title = __( 'iGlobal accepted payment', 'iglobal' );
      $this->method_description = __( 'Checkout through iGlobal Stores international Checkout', 'iglobal' );
    }

    public function is_available() {
      return false;
    }

    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        $order->payment_complete();

        return array(
          'result' => 'success',
          'redirect' => '.'
        );
    }
  }

