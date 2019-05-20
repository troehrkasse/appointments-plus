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
	$title = $product->get_title();
	$select_options[$title] = $title;
}
?><div id='appointment_package_options' class='panel woocommerce_options_panel'><?php
	?><div class='options_group'><?php
		woocommerce_wp_select( 
			array( 
				'id'      => '_appointment_package_type', 
				'label'   => __( 'Select appointment type', 'woocommerce' ),
				'options' =>  $select_options
				)
			);
			woocommerce_wp_text_input( 
				array(
					'id'		=>	'appointment_package_quantity',
					'label'	=>	'How many in package?'
				)
				);
	?></div>
</div><?php

}

/* Customize the tabs that display for this product type */
add_filter( 'woocommerce_product_tabs', 'appointment_package_edit_product_tabs', 98 );
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