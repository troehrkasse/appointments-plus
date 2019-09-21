<?php

define('APPOINTMENTS_URL', trailingslashit(str_replace(WP_CONTENT_DIR, WP_CONTENT_URL, dirname(__FILE__))));
define('APPOINTMENTS_DIR', trailingslashit(dirname(__FILE__)));
if(!defined('APPOINTMENTS_VER')) define('APPOINTMENTS_VER',  '1.0.0' );

/**
 * Include settings and functions files
 */
include_once(APPOINTMENTS_DIR . 'settings.php');
include_once(APPOINTMENTS_DIR . 'functions.php');

/**
 * Module primary driver class
 *
 */
class Appointments
{

    protected static $instance;

    protected static $options;

    public static function get_options()
    {
        if (self::$options)
            return self::$options;

        self::$options = get_option('appointments', []);

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

    /* Set up API */
    public function bootstrap_api()
    {
        foreach (glob(APPOINTMENTS_DIR . "/api/*.php") as $file):
            require_once($file);
        endforeach;
    }

    public function wp_admin_enqueue_scripts()
    {
        // add admin required scripts here 
		wp_register_script('appointments-admin', trailingslashit(APPOINTMENTS_URL) . "assets/js/appointments-admin.js", array(), APPOINTMENTS_VER, false);
		wp_enqueue_script('appointments-admin', trailingslashit(APPOINTMENTS_URL) . "assets/js/appointments-admin.js");
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
        foreach (glob(APPOINTMENTS_DIR . "/classes/*.php") as $file):
            require_once($file);
        endforeach;
	}
	
	/* Add a tab to the product settings section */
	public function appointment_tab( $tabs ) {
		$tabs['appointment'] = array(
			'label'	 => __( 'Appointment Details', 'appointments-plus' ),
			'target' => 'appointment_options',
			'class'  => ('show_if_appointment'),
			);
		return $tabs;
	}

	/* Add the new type to the Woocommerce product type selector */
	public function add_appointment_type( $type ) {
		$type[ 'appointment' ] = __( 'Appointment' );
		return $type;
    }
    
    /* Add settings to the new tab */
	public function appointment_options_product_tab_content () {
		

		?><div id='appointment_options' class='panel woocommerce_options_panel'><?php
			?><div class='options_group'><?php
				woocommerce_wp_text_input( 
					array(
						'id'		=>	'_appointment_event_type',
						'label'		=>	__( 'ScheduleOnce event type (title)', 'woocommerce' ),
					)
				);
			?></div>
		</div><?php
	}

	/* Customize the tabs that display for this product type */
	public function appointment_edit_product_tabs ( $tabs ) {
		array_push($tabs['general']['class'], 'show_if_appointment');
		return $tabs;
	}
	public function appointment_custom_js () {
		if ( 'product' != get_post_type() ) :
			return;
		endif;
		?><script type='text/javascript'>
				jQuery( '.options_group.pricing' ).addClass( 'show_if_appointment' );
		</script><?php
	}

	/* Save data in our new product fields */
	public function save_appointment_options_field( $post_id ) {
		
		if ( isset( $_POST['_appointment_event_type'] ) ) :
			update_post_meta( $post_id, '_appointment_event_type', sanitize_text_field( $_POST['_appointment_event_type'] ) );
		endif;
	}

	/* Add custom admin menu to Wordpress for appointment tracking */
	public function register_appointment_admin_menu () {
		add_menu_page(
			__( 'Appointments Admin', 'appointments-plus' ),
			'Manage Appointments',
			'manage_options',
			'appointments-plus-plugin-appointments-menu',
			array(&$this, 'render_appointments_admin_menu'),
			'dashicons-admin-tools',
			58
		);
	}

	public function render_appointments_admin_menu(){
        // Get all appointments, past and future
        $all_appointments = get_posts([
			'post_type'		=>	'appointment',
			'post_status'	=>	'publish',
			'numberposts'	=>	-1
		]);
        // Split into current (upcoming) and previous
        $current_appointments = [];
        $previous_appointments = [];
        foreach ($all_appointments as $appointment) {
            $appointment_id = $appointment->ID;
            $appointment_data = get_post_meta($appointment_id, '_appointment_data', true);
            if ($appointment_data['status'] == 'Scheduled') {
                $current_appointments[] = $this->extract_appointment_details_for_admin($appointment_id, $appointment_data);
            } else {
                $previous_appointments[] = $this->extract_appointment_details_for_admin($appointment_id, $appointment_data);
            }
        }

        // Populate list of user names and IDs, used for assigning appointments
        $users_objects = get_users();
        $users = [];
        foreach ($users_objects as $user) {
            $users[] = [
				'name'		=>	get_user_meta($user->ID, 'first_name', true) . ' ' . get_user_meta($user->ID, 'last_name', true),
				'id'		=>	$user->ID
			];
        }

        // Get list of appointment products, used for assigning appointments
        $appointment_products = wc_get_products([
			'type'		=>	'appointment',
			'return' 	=> 'ids'
        ]);
        $appointments = [];
        foreach ($appointment_products as $product) {
            $appointments[] = [
                'title' =>  get_the_title($product),
                'id'    =>  $product
            ];
        }

		// Add all data to context for the page and render it
		$context = [
			'current_appointments'	=>	$current_appointments,
			'previous_appointments'	=>	$previous_appointments,
			'users'					=>	$users,
			'appointments'			=>	$appointments
        ];

		Timber::render('appointment-tracking.twig', $context);
    }
    
    private function extract_appointment_details_for_admin($appointment_id, $appointment_data) {
        $customer_data = get_post_meta($appointment_id, '_customer_data', true);
        $payment_status = get_post_meta($appointment_id, '_payment_status', true);
        $appointment_date = date("F j, Y, g:i a", strtotime($appointment_data['date_and_time']));
        return [
            'user'  =>  [
                'name'      =>  $customer_data['name'],
            ],
            'title'             =>  get_the_title($appointment_id),
            'date'              =>  $appointment_date,
            'payment_status'    =>  $payment_status
        ];
    }

	/* Add My Appointments menu to Woocommerce account page */
	public function appointments_menu_items ( $items ) {
		$items['appointments'] = __( 'Appointments', 'woocommerce' );
		return $items;
	}
	
	public function add_appointments_endpoint() {
		add_rewrite_endpoint( 'appointments', EP_PAGES );
	}
	
	public function appointments_endpoint_content() {
        date_default_timezone_set('America/Denver');
        $user = get_current_user_id();
        $appointments = get_posts([
            'author'        => $user,
            'post_type'     =>  'appointment',
            'orderby'       =>  'date',
            'order'         =>  'ASC'
            ]);
        $html = '<h3>Appointments</h3>';
        if (sizeof($appointments) > 0) {
            $html = $html . '<table style="width:100%">
			<tr>
			<th>Appointment Type</th>
			<th>Date and Time</th> 
			<th>Cancel or Reschedule</th>
            </tr>';
            
            foreach ($appointments as $appointment) {
                $appointment_data = get_post_meta($appointment->ID, '_appointment_data', true);
                //!Kint::dump($appointment_data); die();
                $type = $appointment->post_title;
                $date_and_time = $appointment_data['date_and_time'];
                $cancel_reschedule_link = $appointment_data['cancel_reschedule_link'];
                $html = $html . 
				'<tr>
				<td>' . $type . '</td>
				<td>' . date("F j, g:i a", strtotime($date_and_time)) . '</td>
				<td><a href="' . $cancel_reschedule_link . '">Click here to cancel or reschedule</a></td>
				</tr>';
            }
            $html = $html . '</table>
                <p>You can book a new appointment <a href="' . get_site_url() . '/booking">here</a>.</p>';
        } else {
            $html = $html . '<p>No appointments found. <a href="' . get_site_url() . '/booking">Book one now!</a></p>';
        }
		echo $html;
    }
    
    /* Add the Appointment custom post type to Wordpress */
    public function register_appointment_post_type() {
        $type = 'appointment';
        $args = [
            'public'                =>  true, // TODO make not public after testing is done
            'label'                 =>  'Appointments',
            'description'           =>  'Appointments with clients.',
            'supports'              =>  ['title', 'author', 'page-attributes']
        ];
        register_post_type($type, $args);
    }

    /* Update the appointment status in the system */
    public function post_checkout_update_appointment($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        $line_items = $order->get_items();

        foreach($line_items as $line_item) {
            $product = wc_get_product($line_item->get_product_id());
			$product_type = $product->get_type();
            $product_id = $product->get_id();
            if ($product_type == 'appointment') {
                $title = $product->get_title();
                // Attempt to find appointment already associated with user
                $maybe_appointment = $this->get_appointment_for_user_by_id($user_id, $title);
                if ($maybe_appointment) {
                    $appointment_id = $maybe_appointment[0]->ID;
                    // Update the post
                    $appointment_data = get_post_meta($appointment_id, '_appointment_data', true);
                    $appointment_data['order_id'] = $order_id;
                    if ($order->get_payment_method() == 'cod'  || $order->get_total() == "0.00") {
                        
                        /**
                         * Cash on Delivery can be either pay on arrival or pay with package
                         * First, check for packages for the user
                         */
                        $maybe_packages = $this->get_packages_for_user($user_id, $product_id);
                        if ($maybe_packages) {
                            $consumed = $this->consume_package_slot($maybe_packages, $order_id, $user_id, $product_id);
                            if ($consumed) {
                                update_post_meta($appointment_id, '_payment_status', 'paid with package');
                            } else {
                                update_post_meta($appointment_id, '_payment_status', 'pay on arrival');
                            }
                        } else {
                            update_post_meta($appointment_id, '_payment_status', 'pay on arrival');
                        }
                        update_post_meta($appointment_id, '_appointment_data', $appointment_data);
                    }
                } else {
                    // We didn't find it, which means the post wasn't associated with user yet. Have to search meta data
                    $user_object = get_user_by('id', $user_id);
                    $maybe_appointment = $this->get_appointment_for_user_by_meta($title, $user_object->user_email);
                    if ($maybe_appointment) {
                        $appointment_id = $maybe_appointment[0]->ID;
                        // Update the post
                        $appointment_data = get_post_meta($appointment_id, '_appointment_data', true);
                        $appointment_data['order_id'] = $order_id;
                        if ($order->get_payment_method() == 'cod'  || $order->get_total() == "0.00") {
                            // Check for package for this user
                            $maybe_packages = $this->get_packages_for_user($user_id, $product_id);
                            if ($maybe_packages) {
                                $consumed = $this->consume_package_slot($maybe_packages, $order_id, $user_id, $product_id);
                                if ($consumed) {
                                    update_post_meta($appointment_id, '_payment_status', 'paid with package');
                                } else {
                                    update_post_meta($appointment_id, '_payment_status', 'pay on arrival');
                                }
                            } else {
                                update_post_meta($appointment_id, '_payment_status', 'pay on arrival');

                            }
                            update_post_meta($appointment_id, '_appointment_data', $appointment_data);
                        } else {
                            // Payment method can either be cod or Stripe
                            $appointment_data['payment_status'] = 'paid';
                            update_post_meta($appointment_id, '_appointment_data', $appointment_data);
                        }
                        // Update ownership of the appointment post
                        wp_update_post([
                            'ID'            =>  $appointment_id,
                            'post_author'        =>  $user_id
                        ]); 
                    } else {
                        // Could not find the appointment!
                        write_log('User ' . $user_id . ' scheduled an appointment but it failed to update in Wordpress. Order number ' . $order_id);
                    }
                }
            }
        }
    }

    /* Apply packages and return whether or not a package slot was used */
    private function consume_package_slot($maybe_packages, $order_id, $user_id, $product_id) {
        write_log('attempting to consume a package slot for user ' . $user_id . ' order ' . $order_id . ' product ' . $product_id);

        $consumed = false;
        foreach ($maybe_packages as $package) {
            $package_id = $package->ID;
            $remaining = intval(get_post_meta($package_id, '_package_quantity_remaining', true));
            if ($remaining > 0) {
                $consumed = true;
                write_log('consumed 1 slot from package ' . $package_id . ' for user ' . $user_id . ' order ' . $order_id . ' product ' . $product_id);
                if ($remaining == 1) {
                    // Package consumed!
                    update_post_meta($package_id, '_package_quantity_remaining', 0);
                    update_post_meta($package_id, '_package_active', false);
                } else {
                    // Subtract one from the remaining quantity
                    update_post_meta($package_id, '_package_quantity_remaining', $remaining - 1);
                }
                $package_records = get_post_meta($package_id, '_appointment_usage', true);
                $package_records[] = [
                    'order_id'      =>  $order_id,
                    'user_id'       =>  $user_id,
                    'product_id'    =>  $product_id
                ];
                update_post_meta($package_id, '_appointment_usage', $package_records);
                break;
            }
        }
        write_log('finished attempting to consume a package slot for user ' . $user_id . ' order ' . $order_id . ' product ' . $product_id);
        return $consumed;
    }

    /* Get packages for a certain for a user by ID */
    private function get_packages_for_user($user_id, $product_id) {
        write_log('looking up packages for user ' . $user_id . ' and product ' . $product_id);
        $maybe_packages = get_posts([
            'post_type'     =>  'package',
            'author'   =>  $user_id,
            'meta_query'    =>  [
                [
                    'key'       =>  '_appointment_product_id',
                    'value'     =>  $product_id
                ],
                [
                    'key'       =>  '_package_active',
                    'value'     =>  true
                ]
            ]
        ]);

        if (sizeof($maybe_packages) > 0) {
            foreach($maybe_packages as $p) {
                write_log('package id of ' . $p->ID . ' added to the list');
            }
            write_log('finished looking up packages');
            return $maybe_packages;
        } else {
            return false;
        }
    }

    /* Try to get appointments for a user by meta query */
    private function get_appointment_for_user_by_meta($title, $email) {
        $maybe_appointment = get_posts([
            'post_type'     =>  'appointment',
            'post_title'    =>  $title,
            'post_status'   =>  'publish',
            'meta_query'    =>  [
                [
                    'key'       =>  '_appointment_identifier',
                    'value'     =>  $title
                ],
                [
                    'key'       =>  '_customer_email',
                    'value'     =>  $email
                ],
                [
                    'key'       =>  '_payment_status',
                    'value'     =>  'unpaid'
                ]
            ],
            'date_query'    => [
                'column'  => 'post_date',
                'after'   => '- 1 days'
            ]
        ]);

        if (sizeof($maybe_appointment) > 0) {
            return $maybe_appointment;
        } else {
            return false;
        }
    }

    /* Try to get appointments for a user by ID */
    private function get_appointment_for_user_by_id($user_id, $title) {
        $maybe_appointment = get_posts([
            'post_type'     =>  'appointment',
            'author'        =>  $user_id,
            'post_title'    =>  $title,
            'post_status'   =>  'publish',
            'date_query'    => [
                'column'  => 'post_date',
                'after'   => '- 1 days'
            ],
            'meta_query'    =>  [
                [
                    'key'       =>  '_payment_status',
                    'value'     =>  'unpaid'
                ]
            ]
        ]);

        if (sizeof($maybe_appointment) > 0) {
            return $maybe_appointment;
        } else {
            return false;
        }
    }

    /**
     * Generate a customized message to display above the booking form. 
     */
    public function generate_booking_form_message( $atts ) {
        $maybe_user = is_user_logged_in(); // Will return 0 if no logged in user
        $type = $atts['type'];
        $message = '<h3>';
        if ($maybe_user) {
            // Generate message for logged in user
            $message .= 'Standard prices are displayed on this booking form. If you have a prepaid package, it will automatically be 
            applied at checkout.';
        } else {
            $message .= 'Returning client? <a href="' . site_url() . '/my-account/">Log in</a> first for a faster checkout experience!';
            if ($type == "massage") {
                $message .= '<br>
                If you are a new client, you can <a href="' . site_url() . '/my-account/">create an account</a> to receive a discount on 
                your first 60 or 90 minute massage.';
            }
        }
        $message .= '</h3>';
        return $message;
    }


    protected function init()
    {
        add_filter('timber_context', array(&$this, 'add_to_timber_context'), 60);
        add_action('rest_api_init', array(&$this, 'bootstrap_api'), 60);
		add_action('admin_enqueue_scripts', array(&$this, 'wp_admin_enqueue_scripts'), 60);
		
		/* 
		 * Add new product type Appointment to Woocommerce. 
		 * New tabs, display and save options
		 */
		add_filter( 'woocommerce_product_data_tabs', array(&$this, 'appointment_tab') );
		add_filter( 'product_type_selector', array(&$this, 'add_appointment_type') );
		add_action( 'woocommerce_product_data_panels', array(&$this, 'appointment_options_product_tab_content') );
		if (is_admin()) {
			add_filter( 'woocommerce_product_tabs', array(&$this, 'appointment_edit_product_tabs'), 98 );
        }
        add_action( 'admin_footer', array(&$this, 'appointment_custom_js') );
		add_action( 'woocommerce_process_product_meta', array(&$this, 'save_appointment_options_field') );

		/* Add custom Admin menu to Wordpress */
		add_action( 'admin_menu', array(&$this, 'register_appointment_admin_menu'), 10);

		/* Add Appointments page to Woocommerce account page */
		add_filter( 'woocommerce_account_menu_items', array(&$this, 'appointments_menu_items'), 10, 1 );
		add_action( 'init', array(&$this, 'add_appointments_endpoint') );
        add_action( 'woocommerce_account_appointments_endpoint', array(&$this, 'appointments_endpoint_content') );
        
        /* Add the Appointment custom post type to Wordpress */
        add_action('init', array(&$this, 'register_appointment_post_type'));

        /* Hook into Woocommerce post checkout to update appointment status */
        add_action('woocommerce_thankyou', array(&$this, 'post_checkout_update_appointment'), 10, 1);
        
        /* Display a customized message for customers or guests on the booking pages */
        add_shortcode( 'booking_form_message', array(&$this, 'generate_booking_form_message'));
    }
}
Appointments::get_instance();
