<?php

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