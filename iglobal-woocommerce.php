<?php
  /**
  * Plugin Name: iGlobal Integration
  * Plugin URI:
  * Description: This plugin integrates woocomerce with iGlobal
  * Version: 1.3.1
  * Author: iGlobal Stores
  * Author URI: http://www.iglobalstores.com/
  * License: GPL2
  */
  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }
  /**
      * Check if WooCommerce is active
  **/

  if (!in_array( 'woocommerce/woocomerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    // Load WooCommerce Plugin class
    require_once(__DIR__.'/../woocommerce/includes/class-wc-payment-gateways.php');
    require_once(__DIR__.'/../woocommerce/includes/class-wc-checkout.php');

    // Load plugin class files
    require_once( 'includes/class-iglobal.php' );
    require_once( 'includes/class-iglobal-settings.php' );
    require_once( 'includes/class-iglobal-payment.php' );


    // Load plugin libraries
    require_once( 'includes/lib/class-iglobal-admin-api.php' );
    require_once( 'includes/lib/class-iglobal-post-type.php' );
    require_once( 'includes/lib/class-iglobal-taxonomy.php' );
    require_once( 'includes/lib/igWC.php' );

    /**
     * Returns the main instance of Iglobal_Plugin to prevent the need to use globals.
     *
     * @since  1.2.4
     * @return object Iglobal_Plugin
     */

     function add_gateway_class( $methods ){
       $methods[] = 'WC_Gateway_iGlobal';
       return $methods;
     }

    function Iglobal_Plugin () {
    	$instance = Iglobal_Plugin::instance( __FILE__, '1.1.7' );

    	if ( is_null( $instance->settings ) ) {
    		$instance->settings = Iglobal_Plugin_Settings::instance( $instance );
    	}

    	return $instance;
    }

    Iglobal_Plugin();
    add_filter( 'woocomerce_payment_gateways', 'add_gateway_class' );



  	function import_orders(){
  		$store_id = get_option('iglobal_store_id');
  		$store_key = get_option('iglobal_store_key');

      $store = new IgWC($store_id, $store_key);
      try {
          $store->import_orders();
      }catch(Exception $e){
          //console.log($e);
      }
  	}
    add_action('import_orders_cron', 'import_orders');

  	function activate_cron(){
      if(!wp_next_scheduled('import_orders_cron')){
    		wp_schedule_event(time(), 'hourly', 'import_orders_cron');
      }
  	}
    add_action('wp', 'activate_cron');

  	function deactivate_cron(){
      $timestamp = wp_next_scheduled('import_orders_cron');
  		wp_clear_scheduled_hook($timestamp, 'import_orders_cron');
  	}
		register_deactivation_hook( __FILE__, 'deactivate_cron' );
  }

