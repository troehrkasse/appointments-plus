<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Packages_API extends WP_REST_Controller{
    //protected $base = 'packages';
    protected static $instance;
    protected static $NAMESPACE = 'packages';
    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self;
        }
        return self::$instance;
    }
    public function __construct(){
        $this->register_routes();
    }
    public function register_routes(){
        register_rest_route( self::$NAMESPACE, '/add', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'add_package_to_user' ),
                'permission_callback' => array( $this, 'add_package_to_user_permissions_check' )
            )
        ) );
    }
    
    /**
     * @param WP_REST_Request $request
     * @return string
     *
     * Import products to Woocommerce
     */
    public function add_package_to_user(WP_REST_Request $request){
        // Get params from request and post meta
        $user = $request->get_param('user_id');
        $package = $request->get_param('package_id');
        $quantity = intval(get_post_meta($package, '_appointment_package_quantity', true));
        $quantity_remaining = $request->get_param('quantity_remaining');
        $appointment = intval(get_post_meta($package, '_appointment_package_type', true));

        // Get current packages for this user 
        $current_packages = is_array(get_user_meta($user, 'appointment_packages', true)) ? get_user_meta($user, 'appointment_packages', true) : [];

        // Build the new package info
        $current_packages[] = [
            'order_id'				=>	null,
            'package_id'			=>	$package,
            'appointment_id'		=>	$appointment,
            'quantity'				=>	$quantity,
            'quantity_remaining'	=>	$quantity_remaining,
            'created_by'			=>	'admin'
        ];

        $updated = update_user_meta($user, 'appointment_packages', $current_packages);

        if ($updated) {
            return true;
        } else {
            return null;
        }
        
    }
    
    public function add_package_to_user_permissions_check(){
        // TODO
        return true;
    }
} Packages_API::get_instance();

