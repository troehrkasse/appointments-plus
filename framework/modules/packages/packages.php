<?php

define('PACKAGES_URL', trailingslashit(str_replace(WP_CONTENT_DIR, WP_CONTENT_URL, dirname(__FILE__))));
define('PACKAGES_DIR', trailingslashit(dirname(__FILE__)));
if(!defined('PACKAGES_VER')) define('PACKAGES_VER',  '1.0.0' );

/**
 * Include settings and functions files
 */
include_once(PACKAGES_DIR . 'settings.php');
include_once(PACKAGES_DIR . 'functions.php');

/**
 * Module primary driver class
 * 
 * Packages are a custom post type used to track packages of appointments purchased by clients. 
 *
 */
class Packages
{

    protected static $instance;

    protected static $options;

    public static function get_options()
    {
        if (self::$options)
            return self::$options;

        self::$options = get_option('packages', []);

        return self::$options;
    }

    /**
     * get_instance
     * Return previously instantiated instace of framework or new up and return a new instance.
     * Mitigates have multiple instances in memory.
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * __construct
     * Class' constructor
     *
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public function __construct()
    {
        $this->load_dependencies();

        $this->init();
    }

    public function bootstrap_api()
    {
        foreach (glob(PACKAGES_DIR . "/api/*.php") as $file):
            require_once($file);
        endforeach;
    }

    public function wp_admin_enqueue_scripts()
    {
		wp_register_script('packages-admin', trailingslashit(PACKAGES_URL) . "assets/js/packages-admin.js", array(), PACKAGES_VER, false);
		wp_enqueue_script('packages-admin', trailingslashit(PACKAGES_URL) . "assets/js/packages-admin.js");
    }

    /**
     * add_to_timber_context
     * append module specific data to global timber context collection
     *
     * @param $context
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public function add_to_timber_context($context): array
    {
        $adding = array();
        $options = self::get_options();

        if (is_array($options) && !empty($options) && isset($context['options']))
            $context['options'] = array_merge((array)$context['options'], $options);

        if (!isset($context['options']))
            $context['options'] = $options;

        if (is_admin()) {
            return array_merge($context, $adding);
        }

        return array_merge($context, $adding);
    }

    /**
     * Load Requires.
     * Loads all files that are required by the theme.
     *
     * @return none
     * @see __construct
     */
    private function load_dependencies()
    {
        /** Load Classes */
        foreach (glob(PACKAGES_DIR . "/classes/*.php") as $file):
            require_once($file);
        endforeach;
	}
	
	/* Add a tab to the product settings section */
	public function appointment_package_tab( $tabs ) {
		$tabs['appointment_package'] = array(
			'label'	 => __( 'Package Details', 'appointments-plus' ),
			'target' => 'appointment_package_options',
			'class'  => ('show_if_appointment_package'),
			);
		return $tabs;
	}

	/* Add the new type to the selector */
	public function add_appointment_package_type( $type ) {
		$type[ 'appointment_package' ] = __( 'Appointment Package' );
		return $type;
	}

	/* Add settings to the new tab */
	public function appointment_package_options_product_tab_content () {
		// First get all active appointable products
		$args = [
			'type'		=>	'appointment'
		];
		$appointable_products = wc_get_products($args);

		$select_options[''] = __( 'Select a value', 'woocommerce');
		foreach ($appointable_products as $product) {
			$id = $product->get_id();
			$title = $product->get_title();
			$select_options[$id] = $title;
		}

		?><div id='appointment_package_options' class='panel woocommerce_options_panel'><?php
			?><div class='options_group'><?php
				woocommerce_wp_text_input( 
					array(
						'id'		=>	'_appointment_package_quantity',
						'label'		=>	__( 'How many in package?', 'woocommerce' ),
					)
				);
				woocommerce_wp_select( 
					array( 
						'id'      => '_appointment_package_type', 
						'label'   => __( 'Select appointment type', 'woocommerce' ),
						'options' =>  $select_options
						)
					);
			?></div>
		</div><?php
	}

	/* Customize the tabs that display for this product type */
	public function appointment_package_edit_product_tabs ( $tabs ) {
		array_push($tabs['general']['class'], 'show_if_appointment_package');
		return $tabs;
	}
	public function appointment_package_custom_js () {
		if ( 'product' != get_post_type() ) :
			return;
		endif;
		?><script type='text/javascript'>
				jQuery( '.options_group.pricing' ).addClass( 'show_if_appointment_package' );
		</script><?php
	}

	/* Save data in our new product fields */
	public function save_appointment_package_options_field( $post_id ) {
		
		if ( isset( $_POST['_appointment_package_type'] ) ) :
			update_post_meta( $post_id, '_appointment_package_type', sanitize_text_field( $_POST['_appointment_package_type'] ) );
		endif;

		if ( isset( $_POST['_appointment_package_quantity'] ) ) :
			update_post_meta( $post_id, '_appointment_package_quantity', sanitize_text_field( $_POST['_appointment_package_quantity'] ) );
		endif;
		
		if ( isset( $_POST['_appointment_package_price'] ) ) :
			update_post_meta( $post_id, '_appointment_package_price', sanitize_text_field( $_POST['_appointment_package_price'] ) );
		endif;
	}

	/* Add custom admin menu to Wordpress for package tracking */
	public function register_appointment_package_admin_menu () {
		add_menu_page(
			__( 'Packages Admin', 'appointments-plus' ),
			'Package Tracking',
			'manage_options',
			'appointments-plus-plugin-packages-menu',
			array(&$this, 'render_packages_admin_menu'),
			'dashicons-admin-tools',
			58
		);
	}

	public function render_packages_admin_menu(){
		// Build data on current and previous packages for all users
		$all_packages = get_posts([
			'post_type'		=>	'package',
			'post_status'	=>	'publish',
			'numberposts'	=>	-1
		]);
		//!Kint::dump($all_packages); die();
		$current_packages = [];
		$previous_packages = [];
		foreach ($all_packages as $package) {
			$package_id = $package->ID;
			$package_title = get_the_title($package_id);
			$package_active = get_post_meta($package->ID, '_package_active', true);
			if ($package_active) {
				$current_packages[] = [
					'user'	=>	[
						'name'	=>	get_post_meta($package_id, '_user_name', true),
						'id'	=>	get_post_meta($package_id, '_user_id', true)
					],
					'title'		=>	$package_title,
					'quantity'	=>	get_post_meta($package_id, '_package_quantity', true),
					'remaining'	=>	get_post_meta($package_id, '_package_quantity_remaining', true)
				];
			} else {
				$previous_packages[] = [
					'user'	=>	[
						'name'	=>	get_post_meta($package_id, '_user_name', true),
						'id'	=>	get_post_meta($package_id, '_user_id', true)
					],
					'title'		=>	$package_title,
					'quantity'	=>	get_post_meta($package_id, '_package_quantity', true),
					'remaining'	=>	get_post_meta($package_id, '_package_quantity_remaining', true)
				];
			}
		}

		/* Populate list of user names and IDs, for assigning new packages in the admin screen */
		$users_objects = get_users();
		$users = [];
		foreach ($users_objects as $user) {
			$users[] = [
				'name'		=>	get_user_meta($user->ID, 'first_name', true) . ' ' . get_user_meta($user->ID, 'last_name', true),
				'id'		=>	$user->ID
			];
		} 

		// Populate list of Appointment Packages
		$appointment_packages = wc_get_products([
			'type'		=>	'appointment_package',
			'return' 	=> 'ids'
		]);
		$packages = [];
		foreach ($appointment_packages as $appointment_package) {
			$packages[] = [
				'title'		=>	get_the_title($appointment_package),
				'id'		=>	$appointment_package
			];
		}

		// Add all data to context for the page and render it
		$context = [
			'current_packages'		=>	$current_packages,
			'previous_packages'		=>	$previous_packages,
			'users'					=>	$users,
			'packages'				=>	$packages
		];
		Timber::render('package-tracking.twig', $context);
	}

	/* Add the "Add to Cart" button on appointment package pages */
	public function appointment_package_add_to_cart_button() {
		wc_get_template( 'single-product/add-to-cart/simple.php' );
	}

	/* 
	* Hook into post-checkout Woocommerce and add any purchased packages to the user's account
	*/
	public function add_package_to_user($order_id) {
		// Get info for the order, the user, and line items in the order
		$order = wc_get_order($order_id);
		$user_id = $order->get_user_id();
		$user_name = get_userdata($user_id)->first_name . ' ' . get_userdata($user_id)->last_name;
		$line_items = $order->get_items();


		foreach ($line_items as $line_item) {
			$product = wc_get_product($line_item->get_product_id());
			$product_type = $product->get_type();
			$product_id = $product->get_id();
			if ($product_type == 'appointment_package') {
				// Add the package to the user
				// Package data that will be saved as meta on the new post
				$quantity = intval(get_post_meta($product_id, '_appointment_package_quantity', true));
				// TODO update meta to all use _package
				$meta = [
					'_package_product_id'			=>	$product_id, // The Woocommerce package product ID
					'_appointment_product_id'		=>	intval(get_post_meta($product_id, '_appointment_package_type', true)), // The Woocommerce appointment product that this is a bundle of
					'_order_id'						=>	$order_id,
					'_package_quantity'				=>	$quantity,
					'_package_quantity_remaining'	=>	$quantity,
					'_package_active'				=>	true, // sets to false when used up
					'_user_id'						=>	$user_id,
					'_user_name'					=>	$user_name,
					'_appointment_usage'			=>	[] // Will track when appointments are used
				];
				// Args for creating new Package
				$args = [
					'post_title'		=>	$product->get_title(),
					'post_status'		=>	'publish',
					'meta_input'		=>	$meta,
					'post_type'			=>	'package',
					'post_author'		=>	$user_id
				];

				$new_package = wp_insert_post($args);
				if (!$new_package) {
					error_log('Package save failure for order ' . $order_id . ' for user ' . $user_id);
				}
				
			}
		}
	}

	/* Add My Packages menu to Woocommerce account page */
	public function packages_menu_items ( $items ) {
		$items['packages'] = __( 'Packages', 'woocommerce' );
		return $items;
	}
	
	public function add_packages_endpoint() {
		add_rewrite_endpoint( 'packages', EP_PAGES );
	}
	
	public function packages_endpoint_content() {
		$user_id = get_current_user_id();
		$all_packages = get_posts([
			'post_type'		=>	'package',
			'post_status'	=>	'publish',
			'numberposts'	=>	-1,
			'author'		=>	$user_id
		]);
		$current_packages = [];
		$previous_packages = [];
		if (sizeof($all_packages) > 0) {
			foreach ($all_packages as $package) {
				$package_active = get_post_meta($package->ID, '_package_active', true);
				if ($package_active) {
					$current_packages[] = $package;
				} else {
					$previous_packages[] = $package;
				}
			}
		}
		
		// Current packages
		$html = 
		'<h3>Current Packages</h3>';
		if (sizeof($current_packages) > 0) {
			$html = $html . '<table style="width:100%">
			<tr>
			<th>Package Title</th>
			<th>Quantity Remaining</th> 
			<th>Book Appointment</th>
			</tr>';
			foreach ($current_packages as $current_package) {
				$current_package_id = $current_package->ID;
				$title = $current_package->post_title;
				$quantity_remaining = get_post_meta($current_package_id, '_package_quantity_remaining', true);
				$book_appointment = site_url() . '/massage-appointments';
				$html = $html . 
				'<tr>
				<td>' . $title . '</td>
				<td>' . $quantity_remaining . '</td>
				<td><a href="' . $book_appointment . '">Click here to book</a></td>
				</tr>';
			}
			$html = $html . '</table>';
		} else {
			$html = $html . '<p>No current packages found. You can purchase one <a href="' . get_site_url() . '/massage-appointments">here</a>.</p>';
		}
		
		// Previous packages
		$html = $html . '<h3>Previous Packages</h3>';
		if (sizeof($previous_packages) > 0) {
			$html = $html . '<table style="width:100%">
			<tr>
			<th>Package Title</th>
			<th>Quantity Remaining</th> 
			<th>Purchase New Package</th>
			</tr>';
			foreach ($previous_packages as $previous_package) {
				$previous_package_id = $previous_package->ID;
				$title = $previous_package->post_title;
				$quantity_remaining = '0';
				$buy_package = get_post_permalink(get_post_meta($previous_package_id, '_package_product_id', true));
				$html = $html . 
				'<tr>
				<td>' . $title . '</td>
				<td>' . $quantity_remaining . '</td>
				<td><a href="' . $buy_package . '">Click here to purchase</a></td>
				</tr>';
			}
			$html = $html . '</table>';
		} else {
			$html = $html . '<p>No previous packages found.</p>';
		}
		echo $html;
	}

	/* Register the Package custom post type with Wordpress */
	public function register_package_post_type() {
		$type = 'package';
        $args = [
            'public'                =>  true, // TODO make not public after testing is done
            'label'                 =>  'Packages',
            'description'           =>  'Packages that clients have.',
            'supports'              =>  ['title', 'author', 'page-attributes']
        ];
        register_post_type($type, $args);
	}

	public function apply_packages_at_checkout(WC_Cart $cart) {
		$user_id = get_current_user_id();

		if ($user_id !== 0) {
			$items = $cart->get_cart();
			foreach ($items as $item) {
				if ($item['data']->product_type == "appointment") {
					$maybe_packages = get_posts([
						'post_type'		=>	'package',
						'post_status'	=>	'publish',
						'numberposts'	=>	-1,
						'author'		=>	$user_id,
						'meta_query'	=>	[
							[
								'key'		=>	'_package_active',
								'value'		=>	1
							],
							[
								'key'	=>	'_appointment_product_id',
								'value'	=>	$item['product_id']
							]
						]
					]);

					/**
					 * If posts were returned, the user has at least one active package for this appointment type.
					 * 
					 * Apply discount.
					 * 
					 * Don't need to subtract the package from the user - this happens after checkout. 
					 */
					if (sizeof($maybe_packages) > 0) {
						$price = $item['data']->regular_price;
						$cart->add_fee('Prepaid Package Applied', -$price);
					}
				}
			}
		}
	}

	/**
	 * Change the text of the Place Order button at checkout
	 */
	public function change_place_order_button_text( $button_text ) {
		return 'Complete Booking';
	}

	/**
	 * Disable the new order email if the price was $0
	 */
	public function disable_new_order_email_for_packages($recipient, $order) {
		$page = $_GET['page'] = isset( $_GET['page'] ) ? $_GET['page'] : '';
		if ( 'wc-settings' === $page ) {
			return $recipient; 
		}
		if( $order->get_total() === '0.00' ) $recipient = '';
		return $recipient;
	}

    protected function init()
    {
        add_filter('timber_context', array(&$this, 'add_to_timber_context'), 60);
        add_action('rest_api_init', array(&$this, 'bootstrap_api'), 60);
		add_action('admin_enqueue_scripts', array(&$this, 'wp_admin_enqueue_scripts'), 60);
		
		/* 
		 * Add new product type Appointment Package to Woocommerce. 
		 * New tabs, display and save options
		 */
		add_filter( 'woocommerce_product_data_tabs', array(&$this, 'appointment_package_tab') );
		add_filter( 'product_type_selector', array(&$this, 'add_appointment_package_type') );
		add_action( 'woocommerce_product_data_panels', array(&$this, 'appointment_package_options_product_tab_content') );
		if (is_admin()) {
			add_filter( 'woocommerce_product_tabs', array(&$this, 'appointment_package_edit_product_tabs'), 98 );
		}
		add_action( 'admin_footer', array(&$this, 'appointment_package_custom_js') );
		add_action( 'woocommerce_process_product_meta', array(&$this, 'save_appointment_package_options_field') );

		/* Add custom Admin menu to Wordpress */
		add_action( 'admin_menu', array(&$this, 'register_appointment_package_admin_menu'), 10);

		/* Show add to cart button on appointment package pages */
		add_action( 'woocommerce_appointment_package_add_to_cart', array(&$this, 'appointment_package_add_to_cart_button') );

		/* Hook into Woocommerce post checkout to apply packages to user account */
		add_action('woocommerce_thankyou', array(&$this, 'add_package_to_user'), 10, 1);

		/* Add the Package custom post type to Wordpress */
        add_action('init', array(&$this, 'register_package_post_type'));

		/* Add Packages page to Woocommerce account page */
		add_filter( 'woocommerce_account_menu_items', array(&$this, 'packages_menu_items'), 10, 1 );
		add_action( 'init', array(&$this, 'add_packages_endpoint') );
		add_action( 'woocommerce_account_packages_endpoint', array(&$this, 'packages_endpoint_content') );

		/* Apply packages at checkout */
		add_filter ('woocommerce_cart_calculate_fees', array(&$this, 'apply_packages_at_checkout') , 10, 3 );

		/* Change the text of the Place Order button */
		add_filter('woocommerce_order_button_text', array(&$this, 'change_place_order_button_text'));

		/* Disable the order email if the customer used a package and the price is free */
		add_filter('woocommerce_email_recipient_customer_completed_order', array(&$this, 'disable_new_order_email_for_packages'), 10, 2);
		add_filter('woocommerce_email_recipient_customer_processing_order', array(&$this, 'disable_new_order_email_for_packages'), 10, 2);
		add_filter('woocommerce_email_recipient_customer_new_order', array(&$this, 'disable_new_order_email_for_packages'), 10, 2);
    }
}
Packages::get_instance();
