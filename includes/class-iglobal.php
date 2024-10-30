<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Iglobal_Plugin {

	/**
	 * The single instance of Iglobal_Plugin.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $file = '', $version = '1.0.8' ) {
		$this->_version = $version;
		$this->_token = 'Iglobal_plugin';

		// Load plugin environment variables
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load frontend JS & CSS
		$mode = get_option('iglobal_is_active');
		if($mode === "active" || $mode === "test"){
    		add_action( 'wp_enqueue_scripts', array( $this, 'add_welcome_mat' ), 99999, 0);
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 100 );
		}

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		add_action( 'parse_request', array( $this, 'checkout_parse_request' ), 0 );
		add_filter( 'wc_order_statuses', array($this, 'add_to_order_statuses' ) );
		add_action( 'init', array( $this, 'register_order_status') );

		add_filter('woocommerce_get_checkout_url', array($this, 'international_redirect'));

		// Load API for generic admin functions
		if ( is_admin() ) {
			$this->admin = new Iglobal_Plugin_Admin_API();
		}

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
	} // End __construct ()


	public function international_redirect($url){
		$domestic = get_option('iglobal_domestic_countries');
		$noship = get_option('iglobal_noship_countries');
		$mode = get_option('iglobal_is_active');
		$checkout_url = $url;
		if( $mode === "active" || ($mode === "test" && isset($_GET['iGlobal']))){
			if( isset($_COOKIE['igCountry']) && !in_array($_COOKIE['igCountry'], $domestic) && !in_array($_COOKIE['igCountry'], $noship)){
				$checkout_url = get_site_url().'/iglobal/checkout/';
			}
		}
		return $checkout_url;
	}

	public function checkout_parse_request( &$wp ) {
		list( $req_uri ) = explode( '?', $_SERVER['REQUEST_URI'] );
		if ( strpos($req_uri, 'iglobal/checkout' ) ) {
			if(is_user_logged_in() || !get_option('iglobal_require_login')){
				include 'templates/iglobal/international-checkout.php';
				exit();
			} else {
				auth_redirect();
				exit();
			}
		} else if( strpos( $req_uri, 'iglobal/success' ) ) {
			include 'templates/iglobal/iglobal-success.php';
			exit();
		}
		return;
	}

	public function register_order_status(){
		register_post_status('wc-validating', array(
			'label' => _x( 'Validating', 'Order status', 'woocommerce' ),
			'public' => true,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'label_count' => _n_noop( 'Awaiting iGlobal Validation <span class="count">(%s)</span>', 'Awaiting iGlobal Validation <span class="count">(%s)</span>' )
		));
		register_post_status('wc-validated', array(
			'label' => _x( 'Validated', 'Order status', 'woocommerce' ),
			'public' => true,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'label_count' => _n_noop( 'Validated by iGlobal <span class="count">(%s)</span>', 'Validated by iGlobal <span class="count">(%s)</span>' )
		));
		register_post_status('wc-test', array(
			'label' => _x( 'Test Order', 'Order status', 'woocommerce' ),
			'public' => true,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'label_count' => _n_noop( 'iGlobal Test Orders <span class="count">(%s)</span>', 'iGlobal Test Orders <span class="count">(%s)</span>')
		));
	}
	public function add_to_order_statuses( $order_statuses ) {
		$order_statuses['wc-validating'] = _x( 'Validating', 'Order status', 'woocommerce' );
		$order_statuses['wc-validated'] = _x( 'Validated', 'Order status', 'woocommerce' );
		$order_statuses['wc-test'] = _x( 'Test Order', 'Order status', 'woocommerce' );

    return $order_statuses;
	}

	public function add_welcome_mat(){
        //set up welcome mat variables
		$store_id = get_option('iglobal_store_id', 0);
		$logo_url = get_option('iglobal_logo_url', "https://checkout.iglobalstores.com/images/demostore.png");
    $country_array = array(
        "AF"=>"Afghanistan","AL"=>"Albania","DZ"=>"Algeria","AS"=>"American Samoa","AD"=>"Andorra","AO"=>"Angola","AI"=>"Anguilla","AG"=>"Antigua","AR"=>"Argentina","AM"=>"Armenia","AW"=>"Aruba","AU"=>"Australia","AT"=>"Austria","AZ"=>"Azerbaijan","BS"=>"Bahamas","BH"=>"Bahrain","BD"=>"Bangladesh","BB"=>"Barbados","BY"=>"Belarus","BE"=>"Belgium","BZ"=>"Belize","BJ"=>"Benin","BM"=>"Bermuda","BT"=>"Bhutan","BO"=>"Bolivia","BQ"=>"Bonaire, St. Eustatius & Saba","BA"=>"Bosnia & Herzegovina","BW"=>"Botswana","BR"=>"Brazil","BN"=>"Brunei","BG"=>"Bulgaria","BF"=>"Burkina Faso","BI"=>"Burundi","KH"=>"Cambodia","CM"=>"Cameroon","CA"=>"Canada","IC"=>"Canary Islands","CV"=>"Cape Verde","KY"=>"Cayman Islands","CF"=>"Central African Republic","TD"=>"Chad","CL"=>"Chile","CN"=>"China - People's Republic of","CO"=>"Colombia","KM"=>"Comoros","CG"=>"Congo","CK"=>"Cook Islands","CR"=>"Costa Rica","HR"=>"Croatia","CW"=>"Curaçao","CY"=>"Cyprus","CZ"=>"Czech Republic","DK"=>"Denmark","DJ"=>"Djibouti","DM"=>"Dominica","DO"=>"Dominican Republic","EC"=>"Ecuador","EG"=>"Egypt","SV"=>"El Salvador","GQ"=>"Equatorial Guinea","ER"=>"Eritrea","EE"=>"Estonia","ET"=>"Ethiopia","FK"=>"Falkland Islands","FO"=>"Faroe Islands (Denmark)","FJ"=>"Fiji","FI"=>"Finland","FR"=>"France","GF"=>"French Guiana","GA"=>"Gabon","GM"=>"Gambia","GE"=>"Georgia","DE"=>"Germany","GH"=>"Ghana","GI"=>"Gibraltar","GR"=>"Greece","GL"=>"Greenland (Denmark)","GD"=>"Grenada","GP"=>"Guadeloupe","GU"=>"Guam","GT"=>"Guatemala","GG"=>"Guernsey","GN"=>"Guinea","GW"=>"Guinea-Bissau","GY"=>"Guyana","HT"=>"Haiti","HN"=>"Honduras","HK"=>"Hong Kong","HU"=>"Hungary","IS"=>"Iceland","IN"=>"India","ID"=>"Indonesia","IQ"=>"Iraq","IE"=>"Ireland - Republic Of","IL"=>"Israel","IT"=>"Italy","CI"=>"Ivory Coast","JM"=>"Jamaica","JP"=>"Japan","JE"=>"Jersey","JO"=>"Jordan","KZ"=>"Kazakhstan","KE"=>"Kenya","KI"=>"Kiribati","KR"=>"Korea, Republic of (South Korea)","KW"=>"Kuwait","KG"=>"Kyrgyzstan","LA"=>"Laos","LV"=>"Latvia","LB"=>"Lebanon","LS"=>"Lesotho","LR"=>"Liberia","LI"=>"Liechtenstein","LT"=>"Lithuania","LU"=>"Luxembourg","MO"=>"Macau","MK"=>"Macedonia","MG"=>"Madagascar","MW"=>"Malawi","MY"=>"Malaysia","MV"=>"Maldives","ML"=>"Mali","MT"=>"Malta","MH"=>"Marshall Islands","MQ"=>"Martinique","MR"=>"Mauritania","MU"=>"Mauritius","YT"=>"Mayotte","MX"=>"Mexico","FM"=>"Micronesia - Federated States of","MD"=>"Moldova","MC"=>"Monaco","MN"=>"Mongolia","ME"=>"Montenegro","MS"=>"Montserrat","MA"=>"Morocco","MZ"=>"Mozambique","MM"=>"Myanmar","NA"=>"Namibia","NR"=>"Nauru, Republic of","NP"=>"Nepal","NL"=>"Netherlands (Holland)","NV"=>"Nevis","NC"=>"New Caledonia","NZ"=>"New Zealand","NI"=>"Nicaragua","NE"=>"Niger","NG"=>"Nigeria","NU"=>"Niue Island","NF"=>"Norfolk Island","MP"=>"Northern Mariana Islands","NO"=>"Norway","OM"=>"Oman","PK"=>"Pakistan","PW"=>"Palau","PA"=>"Panama","PG"=>"Papua New Guinea","PY"=>"Paraguay","PE"=>"Peru","PH"=>"Philippines","PL"=>"Poland","PT"=>"Portugal","PR"=>"Puerto Rico","QA"=>"Qatar","RE"=>"Reunion","RO"=>"Romania","RU"=>"Russia","RW"=>"Rwanda","SM"=>"San Marino","ST"=>"Sao Tome & Principe","SA"=>"Saudi Arabia","SN"=>"Senegal","RS"=>"Serbia & Montenegro","SC"=>"Seychelles","SL"=>"Sierra Leone","SG"=>"Singapore","SK"=>"Slovakia","SI"=>"Slovenia","SB"=>"Solomon Islands","ZA"=>"South Africa","SS"=>"South Sudan","ES"=>"Spain","LK"=>"Sri Lanka","BL"=>"St. Barthelemy","EU"=>"St. Eustatius","KN"=>"St. Kitts and Nevis","LC"=>"St. Lucia","MF"=>"St. Maarten","VC"=>"St. Vincent","SD"=>"Sudan","SR"=>"Suriname","SZ"=>"Swaziland","SE"=>"Sweden","CH"=>"Switzerland","PF"=>"Tahiti","TW"=>"Taiwan","TJ"=>"Tajikistan","TZ"=>"Tanzania","TH"=>"Thailand","TL"=>"Timor-Leste","TG"=>"Togo","TO"=>"Tonga","TT"=>"Trinidad and Tobago","TN"=>"Tunisia","TR"=>"Turkey","TM"=>"Turkmenistan","TC"=>"Turks and Caicos Islands","TV"=>"Tuvalu","UG"=>"Uganda","UA"=>"Ukraine","AE"=>"United Arab Emirates","GB"=>"United Kingdom","US"=>"United States","UY"=>"Uruguay","UZ"=>"Uzbekistan","VU"=>"Vanuatu","VE"=>"Venezuela","VN"=>"Vietnam","VG"=>"Virgin Islands (British)","VI"=>"Virgin Islands (U.S.)","WS"=>"Western Samoa","YE"=>"Yemen","ZM"=>"Zambia","ZW"=>"Zimbabwe"
    );
    $css_selector = get_option('iglobal_css_selector');
    $location = get_option('iglobal_location');
    $element = get_option('iglobal_element');
    $ship_countries = get_option('iglobal_ship_countries', []);
    $noship_countries = get_option('iglobal_noship_countries', []);
    $domestic_countries = get_option('iglobal_domestic_countries', []);
    $checkout_buttons = get_option('iglobal_checkout_selectors');
    $domestic_checkout_url = get_site_url() . '/checkout';
    $international_checkout_url = get_site_url() . '/iglobal/checkout/';
    $mode = get_option('iglobal_is_active');

    //declare javascript vars
    ?>
        <script type="text/javascript">
            var ig_storeId = <?php echo $store_id ?>;
            var ig_cookieDomain = window.location.hostname;// If you prefer, you can put your domain here, like so "yourdomain.com";
            var ig_logoUrl = "<?php echo $logo_url; ?>";
            var ig_domesticCheckoutUrl = "<?php echo($domestic_checkout_url); ?>";
            var ig_internationalCheckoutUrl = "<?php echo($international_checkout_url); ?>";
            var ig_active = false;
            <?php if( $checkout_buttons && ( $mode === "active" || ( $mode === "test" && isset( $_GET['iGlobal'] ) ) ) ){ ?>
                ig_active = true;
            <?php } ?>

            var ig_countries = {
                <?php
                	foreach ( $ship_countries as $country ) {
                        echo '"'.$country.'":"'.$country_array[$country].'",';
                    }
                ?>
            };
            var ig_domesticCountryCodes = [
                <?php
                    if($domestic_countries) {
                        foreach ($domestic_countries as $country ){
                            echo '"'.$country.'",';
                        }
                    }
                ?>
            ];
            var ig_noShipCountryCodes = [
                <?php
                    if($noship_countries){
                        foreach ($noship_countries as $country ){
                            echo '"'.$country.'",';
                        }
                    }
                ?>
            ];
            var ig_checkoutButtons = [];
            <?php
                foreach( explode( ",", $checkout_buttons ) as $button ){
                    echo "ig_checkoutButtons.push('" . $button . "');";
                }
             ?>
            var ig_flagLocation = <?php echo '"' . $css_selector . '"'; ?>;
            var ig_flagMethod = <?php echo '"' . $location . '"'; ?>;
            var ig_flagCode = <?php echo "'" . $element . "'"; ?>;
        </script>
        <?php
	}

	/**
	 * Wrapper function to register a new post type
	 * @param  string $post_type   Post type name
	 * @param  string $plural      Post type item plural name
	 * @param  string $single      Post type item single name
	 * @param  string $description Description of post type
	 * @return object              Post type class object
	 */
	public function register_post_type ( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) return;

		$post_type = new Iglobal_Plugin_Post_Type( $post_type, $plural, $single, $description, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 * @param  string $taxonomy   Taxonomy name
	 * @param  string $plural     Taxonomy single name
	 * @param  string $single     Taxonomy plural name
	 * @param  array  $post_types Post types to which this taxonomy applies
	 * @return object             Taxonomy class object
	 */
	public function register_taxonomy ( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) return;

		$taxonomy = new Iglobal_Plugin_Taxonomy( $taxonomy, $plural, $single, $post_types, $taxonomy_args );

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
        $custom_styling = get_option( 'iglobal_welcome_mat_css_url' );
        $custom_style_path = "";

        if( ! $custom_styling ){
            $custom_style_path = esc_url( $this->assets_url ) . 'css/ig_welcome_mat.css';
        }else{
            $custom_style_path = $custom_styling;
        }

		wp_register_style( $this->_token . '-welcome-mat', $custom_style_path, array(), $this->_version );
		wp_enqueue_style( $this->_token . '-welcome-mat' );
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
        // check if using default welcome mat or custom
        $custom_welcome_mat = get_option( 'iglobal_welcome_mat_javascript_url' );
        $welcome_mat_url = "";
        if( ! $custom_welcome_mat ){
            $welcome_mat_url = esc_url( $this->assets_url ) . 'js/ig_welcome_mat.js';
        }else{
            $welcome_mat_url = $custom_welcome_mat;
        }

    	wp_enqueue_script(
			$this->_token . '-welcome-mat',
			$welcome_mat_url,
			array( 'jquery' ),
			$this->_version,
			true,
			true
		);

		wp_enqueue_script( $this->_token . '-welcome-mat' );
	} // End enqueue_scripts ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-admin' );
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'iglobal', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'iglobal';

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Main Iglobal_Plugin Instance
	 *
	 * Ensures only one instance of Iglobal_Plugin is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Iglobal_Plugin()
	 * @return Main Iglobal_Plugin instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()

}
