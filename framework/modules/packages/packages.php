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
		wp_register_script('packages-admin', trailingslashit(PACKAGES_URL) . "assets/js/admin.js", array(), PACKAGES_VER, false);
		wp_enqueue_script('packages-admin', trailingslashit(PACKAGES_URL) . "assets/js/admin.js");
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
		$users_objects = get_users();
		$current_packages = [];
		$previous_packages = [];
		$users = [];
		foreach ($users_objects as $user) {
			$users[] = [
				'name'		=>	get_user_meta($user->ID, 'first_name', true) . ' ' . get_user_meta($user->ID, 'last_name', true),
				'id'		=>	$user->ID
			];
			$user_packages = is_array(get_user_meta($user->ID, 'appointment_packages', true)) ? get_user_meta($user->ID, 'appointment_packages', true) : false;
			if ($user_packages) {
				foreach ($user_packages as $package) {
					if ($package['quantity_remaining'] > 0) {
						$current_packages[] = [
							'user'		=>	[
								'name'		=>	get_user_meta($user->ID, 'first_name', true) . ' ' . get_user_meta($user->ID, 'last_name', true),
								'id'		=>	$user->ID
							],
							'title'		=>	get_the_title($package['package_id']),
							'quantity'	=>	$package['quantity'],
							'remaining'	=>	$package['quantity_remaining'],
						];
					} else {
						$previous_packages[] = [
							'user'		=>	[
								'name'		=>	get_user_meta($user->ID, 'first_name', true) . ' ' . get_user_meta($user->ID, 'last_name', true),
								'id'		=>	$user->ID
							],
							'title'		=>	get_the_title($package['package_id']),
							'quantity'	=>	$package['quantity'],
							'remaining'	=>	$package['quantity_remaining'],
						];
					}
				}
			}
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
			'previous_packages'	=>	$previous_packages,
			'users'				=>	$users,
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
		$user = $order->get_user_id();
		$line_items = $order->get_items();


		foreach ($line_items as $line_item) {
			$product = wc_get_product($line_item->get_product_id());
			$product_type = $product->get_type();
			$product_id = $product->get_id();
			if ($product_type == 'appointment_package') {
				// Add the package to the user
				// Package data that will be saved as meta on the new post
				$quantity = intval(get_post_meta($product_id, '_appointment_package_quantity', true));
				$meta = [
					'_package_product_id'			=>	$product_id, // The Woocommerce package product ID
					'_appointment_product_id'		=>	intval(get_post_meta($product_id, '_appointment_package_type', true)), // The Woocommerce appointment product that this is a bundle of
					'_order_id'						=>	$order,
					'_package_quantity'				=>	$quantity,
					'_package_quantity_remaiing'	=>	$quantity,
					'_user_id'						=>	$user
				];
				// Args for creating new Package
				$args = [
					'post_title'		=>	$product->get_title(),
					'post_status'		=>	'publish',
					'meta_input'		=>	$meta,
					'post_type'			=>	'package',
					'post_author'		=>	$user
				];

				$new_package = wp_insert_post($args);
				if (!$new_package) {
					error_log('Package save failure for order ' . $order_id . ' for user ' . $user);
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
		$user = get_current_user_id();
		$packages = is_array(get_user_meta($user, 'appointment_packages', true)) ? get_user_meta($user, 'appointment_packages', true) : false;
		$current_packages = [];
		$previous_packages = [];
		if ($packages) {
			foreach ($packages as $package) {
				if ($package['quantity_remaining'] == 0) {
					$previous_packages[] = $package;
				} else {
					$current_packages[] = $package;
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
				$title = get_the_title($current_package['package_id']);
				$quantity_remaining = $current_package['quantity_remaining'];
				$book_appointment = get_post_permalink($current_package['appointment_id']);
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
				$title = get_the_title($previous_package['package_id']);
				$quantity_remaining = $previous_package['quantity_remaining'];
				$buy_package = get_post_permalink($previous_package['package_id']);
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

		/* Hook into Woocommerce post checkout to apply packages */
		add_action('woocommerce_thankyou', array(&$this, 'add_package_to_user'), 10, 1);

		/* Add the Package custom post type to Wordpress */
        add_action('init', array(&$this, 'register_package_post_type'));

		/* Add Packages page to Woocommerce account page */
		add_filter( 'woocommerce_account_menu_items', array(&$this, 'packages_menu_items'), 10, 1 );
		add_action( 'init', array(&$this, 'add_packages_endpoint') );
		add_action( 'woocommerce_account_packages_endpoint', array(&$this, 'packages_endpoint_content') );
    }
}
Packages::get_instance();
