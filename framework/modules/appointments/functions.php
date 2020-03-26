<?php

// Dismantle

/* Add Package product type */
// add_action( 'plugins_loaded', 'register_appointment_type' );
function register_appointment_type() {
	class WC_Product_Appointment extends WC_Product {
		public function __construct( $product ) {
			$this->product_type = 'appointment';
			parent::__construct( $product );
			// add additional functions here
		}
	}
}