<?php

/**
 * Packages are added as a Woocommerce product type. They are a bundle of Appointable products.
 */

// TODO: add checks to make sure Woocommerce and Appointments plugins are active

/* Add Package product type */
add_action( 'plugins_loaded', 'register_appointment_package_type' );
function register_appointment_package_type() {
	class WC_Product_Appointment_Package extends WC_Product {
	public function __construct( $product ) {
		$this->product_type = 'appointment_package';
		parent::__construct( $product );
		// add additional functions here
	}
	}
}

/* Add the new type to the selector */
add_filter( 'product_type_selector', 'add_appointment_package_type' );
function add_appointment_package_type( $type ) {
	$type[ 'appointment_package' ] = __( 'Appointment Package' );
return $type;
}

/* Add a tab to the product settings section */
add_filter( 'woocommerce_product_data_tabs', 'appointment_package_tab' );
function appointment_package_tab( $tabs ) {
	$tabs['appointment_package'] = array(
	'label'	 => __( 'Package Details', 'appointments-plus' ),
	'target' => 'appointment_package_options',
	'class'  => ('show_if_appointment_package'),
);
return $tabs;
}

/* Add settings to the new tab */
add_action( 'woocommerce_product_data_panels', 'appointment_package_options_product_tab_content' );
function appointment_package_options_product_tab_content () {
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
//!Kint::dump(get_post_meta(get_the_ID())); die();
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
if (is_admin()) {
	add_filter( 'woocommerce_product_tabs', 'appointment_package_edit_product_tabs', 98 );
}
function appointment_package_edit_product_tabs ( $tabs ) {
	array_push($tabs['general']['class'], 'show_if_appointment_package');
	return $tabs;
}
add_action( 'admin_footer', 'appointment_package_custom_js' );
function appointment_package_custom_js () {
	if ( 'product' != get_post_type() ) :
		return;
  endif;
  ?><script type='text/javascript'>
		jQuery( '.options_group.pricing' ).addClass( 'show_if_appointment_package' );
  </script><?php
}

/* Save data in our new product fields */
add_action( 'woocommerce_process_product_meta', 'save_appointment_package_options_field' );
function save_appointment_package_options_field( $post_id ) {
	
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

/* Add custom menu to Wordpress Admin for package tracking */
add_action( 'admin_menu', 'register_appointment_package_admin_menu', 10);
function register_appointment_package_admin_menu () {
	add_menu_page(
		__( 'Appointments Plus Admin', 'appointments-plus' ),
		'Package Tracking',
		'manage_options',
		'appointments-plus-plugin-admin-menu',
		'render_appointments_plus_admin_menu',
		'dashicons-admin-tools',
		58
  );
}
function render_appointments_plus_admin_menu(){
	$context = [
		 'title'     =>  'Appointment Package Tracking',
		 'info'      =>  'Details for packages and the ability to add new ones will show here.'
	];
	Timber::render('package-tracking.twig', $context);
}

/* Add the "Add to Cart" button on appointment package pages */
function appointment_package_add_to_cart_button() {
    wc_get_template( 'single-product/add-to-cart/simple.php' );
}
add_action( 'woocommerce_appointment_package_add_to_cart', 'appointment_package_add_to_cart_button' );

/* 
 * Customize the "Added to Cart" message for Packages
 * If an appointment package is added to the cart we want the user to schedule an appointment before checking out
 */
add_filter( 'wc_add_to_cart_message_html', 'appointment_packages_add_to_cart_function', 10, 2 ); 
function appointment_packages_add_to_cart_function( $message, $products ) {
	$purchased = intval(key($products));
	// Get a list of appointment package products
	$appointment_packages = wc_get_products([
		'type'		=>	'appointment_package',
		'return' 	=> 'ids'
	]);

	// See if the purchased item matches an appointment package product
	foreach ($appointment_packages as $package) {
		if ($package == $purchased) {
			$appointment = intval(get_post_meta($purchased, '_appointment_package_type', true));
			// Match! Customize the message accordingly
			$message = '<a href="' . get_permalink($appointment) . '" tabindex="1" class="button wc-forward">Schedule Appointment</a>' . 
			get_the_title(key($products)) . 
			' has been added to your cart. Please schedule your first appointment before checking out!';
		}
	}
	return $message; 
}

/* Hook into post-checkout Woocommerce to look for packages and add package/appt data to user
 * Also,  */
add_action('woocommerce_thankyou', 'add_package_to_user', 10, 1);
function add_package_to_user($order_id) {
	// Get info for the order, the user, and any packages/appointments in the order
	$order = wc_get_order($order_id);
	$user = $order->get_user_id();
	$line_items = $order->get_items();
	$packages = [];
	$appointments = [];
	$current_packages = is_array(get_user_meta($user, 'appointment_packages', true)) ? get_user_meta($user, 'appointment_packages', true) : [];
	foreach ($line_items as $line_item) {
		$product = wc_get_product($line_item->get_product_id());
		$product_type = $product->get_type();
		$product_id = $product->get_id();
		if ($product_type == 'appointment_package') {
			$packages[$product_id] = intval(get_post_meta($product_id, '_appointment_package_type', true));
		} elseif ($product_type == 'appointment') {
			$appointments[$product_id] = $line_item->get_quantity();
		}
	}

	/*
	 * Check to see if any packages were purchased
	 * If so, apply any qualifying appointments on this order
	 */
	if (sizeof($packages) > 0) {

		foreach ($packages as $package=>$appointment) {
			$quantity = intval(get_post_meta($package, '_appointment_package_quantity', true));
			$consumed = $appointments[$appointment];
			$current_packages[$order_id] = [
				'order_id'				=>	$order_id,
				'package_id'			=>	$package,
				'appointment_id'		=>	$appointment,
				'quantity'				=>	$quantity,
				'quantity_remaining'	=>	$quantity - $consumed,
				'created_by'			=>	'user'
			];
			// The appointment has been accounted for, so remove it before the next step
			unset($appointments[$appointment]);
		}
	}

	/*
	 * Check for any remaining appointments on this order
	 * If appointments and if user selected to pay with package and if the user has current packages
	 */
	if ((sizeof($appointments) > 0) && ($order->get_payment_method() == 'cod') && (sizeof($current_packages) > 0)) {
		foreach ($current_packages as $package_key => $package) {
			if ($package['quantity_remaining'] > 0) {
				foreach ($appointments as $appointment => $quantity) {
					if ($appointment == $package['appointment_id']) {
						// Subtract this appointment 
						$package['quantity_remaining'] -= $quantity;
						$current_packages[$package_key] = $package;
						unset($appointments[$appointment]);
						// If no more appointments are in the order, mark the appointment as paid
						if (sizeof($appointments) == 0) {
							$order->update_status('completed');
						}
					}
				}
			}
			
		}
		//!Kint::dump($current_packages, $appointments); die();
	}
	//!Kint::dump($order, $current_packages); die();

	// Update packages user meta 
	$updated = update_user_meta($user, 'appointment_packages', $current_packages);
}

/* Apply discounts at checkout for any package holders 
add_action('woocommerce_cart_calculate_fees' , 'apply_package_discounts', 10, 1);
function apply_package_discounts($cart) {
	$cart_contents = $cart->get_cart_contents();
	foreach($cart_contents as $line_item) {
		//!Kint::dump($line_item); die();
		$appointment = $line_item['product_id'];
	}
	!Kint::dump($cart); die();
}
*/