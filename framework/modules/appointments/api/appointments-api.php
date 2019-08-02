<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/** 
 * This class handles webhook triggers from ScheduleOnce.
 * Webhooks are triggered by booking events: scheduled, canceled, rescheduled, etc.
 * 
 * Bookings are tracked with a custom post type of "Appointment" that is defined in this module. 
 * Booking data is stored in postmeta and posts "belong" to a user. 
 */
class Appointments_API extends WP_REST_Controller{
    //protected $base = 'appointments';
    protected static $instance;
    protected static $NAMESPACE = 'appointments';
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
        /* Used for handling all booking events from ScheduleOnce */
        register_rest_route( self::$NAMESPACE, '/booking-event', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'handle_appointment_booking_event' ),
                'permission_callback' => array( $this, 'appointments_permissions_check' )
            )
        ) );
    }
    
    /**
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     *
     * Respond to ScheduleOnce booking events
     */
    public function handle_appointment_booking_event(WP_REST_Request $request){
        $params = $request->get_params();
        switch ($params['type']) {
            case 'booking.scheduled':
                $response = $this->handle_booking_scheduled_event($params);
                break;
            case 'booking.rescheduled':
                $response = $this->handle_booking_rescheduled_event($params);
                break;
            case 'booking.canceled_then_rescheduled':
                $response = $this->handle_booking_canceled_then_rescheduled_event($params);
                break;
            case 'booking.canceled':
                $response = $this->handle_booking_canceled_event($params);
                break;
            case 'booking.completed':
                $respoonse = $this->handle_booking_completed_event($params);
                break;
            case 'booking.no_show':
                $response = $this->handle_booking_no_show_event($params);
                break;
            default:
                $response = 'no implementation for that event exists here.';
                error_log('An event was received from ScheduleOnce but no implementation exists:');
                error_log($params);
        }
        return new WP_REST_Response($response, 200);
    }

    /**
     * Updates the time of an appointment based on event data.
     */
    public function handle_booking_rescheduled_event($event) {
        $external_id = $event['id']; 
        $maybe_appointment = get_posts([
            'post_type'     =>  'appointment',
            'post_status'   =>  'publish',
            'numberposts'   =>  1,
            'meta_query'    =>  [
                [
                    'key'       =>  '_appointment_id',
                    'value'     =>  $external_id
                ]
            ]
        ]);

        if (sizeof($maybe_appointment) > 0) {
            $appointment_id = $maybe_appointment[0]->ID;
            $appointment_data = get_post_meta($appointment_id, '_appointment_data', true);
            $appointment_data['date_and_time'] = $event['data']['starting_time'];
            update_post_meta($appointment_id, '_appointment_data', $appointment_data);
            return true;
        }

        error_log('Could not find local appointment for event ' . $external_id . ', proceeding to dump event received');
        error_log($event);

        return false;
    }
    
    /**
     * Creates a new Appointment based on event data.
     * 
     * Adds the appropriate product to the Woocommerce cart. ScheduleOnce will redirect the user to the checkout page. 
     */
    private function handle_booking_scheduled_event($event) {
        // Identifier used to find the corresponding Woocommerce product
        $identifier = $event['data']['event_type']['name'];

        // User email, to see if they are registered already
        $email = $event['data']['form_submission']['email'];
        $maybeUser = get_user_by('email', $email);

        // Corresponding Woocommerce product 
        $product = get_page_by_title($identifier, OBJECT, 'product');
        /**
         * Appointment data that will be saved as meta on the new post. 
         */
        $meta = [
            '_appointment_data' =>  [
                'id'                        =>  $event['id'],
                'tracking_id'               =>  $event['data']['tracking_id'],
                'subject'                   =>  $event['data']['subject'],
                'status'                    =>  $event['data']['status'],
                'staff'                     =>  $event['data']['owner'],
                'date_and_time'             =>  $event['data']['starting_time'],
                'product_id'                =>  $product->ID,
                'order_id'                  =>  null,
                'cancel_reschedule_link'    =>  $event['data']['cancel_reschedule_link']
            ],
            '_appointment_id'               =>  $event['id'], // Unique identifier for ScheduleOnce system
            '_appointment_identifier'       =>  $identifier, // Just the title of the event
            '_payment_status'                =>  'unpaid', // unpaid or paid or paid with package
            '_customer_email'               =>  $email,
            '_customer_data'    =>  [
                'name'  =>  $event['data']['form_submission']['name'],
                'email' =>  $email,
                'phone' =>  $event['data']['form_submission']['phone']
            ]
        ];
        // Args to create a new Appointment
        $args = [
            'post_title'        =>  $event['data']['event_type']['name'],
            'post_status'       =>  'publish',
            'meta_input'        =>  $meta,
            'post_type'         =>  'appointment'
        ];
        if ($maybeUser) {
            $args['post_author'] = $maybeUser->ID;
        }
        
        $new_appointment = wp_insert_post($args);
        return $new_appointment;
    }

    /* Update an appointment using event data from ScheduleOnce. May be able to combine with the above function */
    private function handle_booking_canceled_then_scheduled_event($event) {
        return 'so far so good';
    }

    /* Delete an appointment using event data from ScheduleOnce */
    private function handle_booking_canceled_event($event) {
        $external_id = $event['id']; 
        $maybe_appointment = get_posts([
            'post_type'     =>  'appointment',
            'post_status'   =>  'publish',
            'numberposts'   =>  1,
            'meta_query'    =>  [
                [
                    'key'       =>  '_appointment_id',
                    'value'     =>  $external_id
                ]
            ]
        ]);

        if (sizeof($maybe_appointment) > 0) {
            $appointment_id = $maybe_appointment[0]->ID;
            $deleted = wp_delete_post($appointment_id, true);
            if ($deleted !== false) {
                return true;
            } else {
                error_log('Could not find local appointment for event ' . $external_id . ', proceeding to dump event received');
                error_log($event);
                return false;
            }
        }

        error_log('Could not find local appointment for event ' . $external_id . ', proceeding to dump event received');
        error_log($event);

        return false;
    }

    /* Mark an appointment as completed using event data from ScheduleOnce */
    private function handle_booking_completed_event($event) {
        return 'so far so good';
    }

    /* Mark an appointment as no-show using event data from ScheduleOnce */
    private function handle_booking_no_show_event($event) {
        return 'so far so good';
    }
    
    
    public function appointments_permissions_check(){
        // TODO - verify requests are coming from ScheduleOnce
        return true;
    }
} Appointments_API::get_instance();

