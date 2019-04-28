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

 add_filter( 'woocommerce_product_data_tabs', 'appointment_package_tab' );
 function appointment_package_tab( $tabs ) {
    $tabs['appointment_package'] = array(
		'label'	 => __( 'Package Details', 'appointments-plus' ),
		'target' => 'appointment_package_options',
		'class'  => ('show_if_appointment_package'),
	);
	return $tabs;
 }