<?php
  if ( ! defined( 'ABSPATH' ) ) exit;

  $store_id = get_option('iglobal_store_id');
  $store_key = get_option('iglobal_store_key');
  $subdomain = get_option('iglobal_subdomain', 'checkout');

  $store = new IgWC($store_id, $store_key);

  $cart = WC()->cart;

  if ( !$cart ) {

  } else {
    if(is_user_logged_in()){
      $user = get_current_user_id();
    } else {
      $user = '';
    }
    $tempId = $store->create_temp_cart( $cart, get_site_url()."/iglobal/success", $user );
    $countryCode = '';
    if(isset($_COOKIE['igCountry']) && $_COOKIE['igCountry']){
      $countryCode = $_COOKIE['igCountry'];
    }
    if(isset($_COOKIE['ig_clientId'])){
      $clientId = $_COOKIE['igClientId'];
    }
    $url = 'https://'.$subdomain.'.iglobalstores.com?store='.$store_id.'&amp;tempCartUUID='.$tempId.'&amp;country='.$countryCode.'&clientId='.$clientId;
    ?>
      <html><head> <title><?php echo wp_get_document_title(); ?></title>
        <style type="text/css"> body, html {margin: 0; padding: 0; height: 100%; overflow: hidden;} #content{position:absolute; left: 0; right: 0; bottom: 0; top: 0;} </style>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
      </head><body><div id="content">
          <iframe src="<?php echo $url ?>" width="100%" height="100%" />
      </div></body></html>
    <?php
  }

