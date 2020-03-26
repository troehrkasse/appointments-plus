<?php
// Dismantle
/* Add Package product type */
// add_action( 'plugins_loaded', 'register_appointment_package_type' );
function register_appointment_package_type() {
	class WC_Product_Appointment_Package extends WC_Product {
		public function __construct( $product ) {
			$this->product_type = 'appointment_package';
			parent::__construct( $product );
			// add additional functions here
		}
	}
}

/* Write to log in a readaable format */
if ( ! function_exists('write_log')) {
	function write_log ( $log )  {
	   if ( is_array( $log ) || is_object( $log ) ) {
		  error_log( print_r( $log, true ) );
	   } else {
		  error_log( $log );
	   }
	}
 }