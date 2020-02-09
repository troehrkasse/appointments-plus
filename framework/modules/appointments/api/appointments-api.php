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

        // Manually create an appointment from WP Admin
        register_rest_route( self::$NAMESPACE, '/add', array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_manual_appointment' ),
                'permission_callback' => array( $this, 'appointments_permissions_check' )
            )
        ) );
    }

    /**
     * @param WP_REST_Request $request
     * 
     */
    public function create_manual_appointment( WP_REST_Request $request) {
        $user_id = $request->get_param('user_id');
        $appointment_id = $request->get_params('appointment_id');
        $status = $request->get_params('status');
        $date = $request->get_params('date');
        return $params;
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
                write_log('An event was received from ScheduleOnce but no implementation exists:');
                write_log($params);
        }
        return new WP_REST_Response($response, 200);
    }

    /**
     * Updates the time of an appointment based on event data.
     */
    public function handle_booking_rescheduled_event($event) {
        write_log('ScheduleOnce rescheduled event received');
        write_log($event);
        $external_id = $event['data']['tracking_id']; 
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
            write_log('booking reschedule event for event ID ' . $external_id . ' handled! Updated Appointment ID of ' . $appointment_id . 'with this data:');
            write_log($appointment_data);
            return true;
        }

        write_log('Could not find local appointment for event ' . $external_id . ', proceeding to dump event received');
        write_log($event);

        return false;
    }
    
    /**
     * Creates a new Appointment based on event data.
     * 
     * Adds the appropriate product to the Woocommerce cart. ScheduleOnce will redirect the user to the checkout page. 
     */
    private function handle_booking_scheduled_event($event) {
        write_log('ScheduleOnce scheduled event received');
        write_log($event);
        // Identifier used to find the corresponding Woocommerce product
        $identifier = $event['data']['event_type']['name'];

        // User email, to see if they are registered already
        $email = $event['data']['form_submission']['email'];
        $maybeUser = get_user_by('email', $email);
        
        write_log('attempting to find user for email ' . $email);
        write_log($maybeUser);

        // Corresponding Woocommerce product 
        $product = get_page_by_title($identifier, OBJECT, 'product');

        write_log('attempting to find product for ' . $identifier);
        write_log($product->id);
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
            '_appointment_id'               =>  $event['data']['tracking_id'], // Unique identifier for ScheduleOnce system
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

        write_log('built args for new appointment is: ');
        write_log($args);

        if ($maybeUser) {
            $args['post_author'] = $maybeUser->ID;
        }
        
        $new_appointment = wp_insert_post($args);

        write_log('new appointment created: ' . $new_appointment);
        return $new_appointment;
    }

    /* Update an appointment using event data from ScheduleOnce. May be able to combine with the above function */
    private function handle_booking_canceled_then_scheduled_event($event) {
        return 'so far so good';
    }

    /* Delete an appointment using event data from ScheduleOnce */
    // TODO un-consume package when this happens! 
    private function handle_booking_canceled_event($event) {
        write_log('ScheduleOnce booking canceled event received: ');
        write_log($event);

        $external_id = $event['data']['tracking_id']; 
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

        write_log('looking for appointment with external ID of ' . $external_id);

        if (sizeof($maybe_appointment) > 0) {
            $appointment_id = $maybe_appointment[0]->ID;
            $deleted = wp_delete_post($appointment_id, true);
            if ($deleted !== false) {
                write_log('deleted appointment with ID of ' . $appointment_id);
                return true;
            } else {
                write_log('Could not find local appointment for event ' . $external_id . ', proceeding to dump event received');
                write_log($event);
                return false;
            }
        }

        write_log('Could not find local appointment for event ' . $external_id . ', proceeding to dump event received');
        write_log($event);

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

