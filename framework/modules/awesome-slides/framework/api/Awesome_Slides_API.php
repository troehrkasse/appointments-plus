<?php 
//require_once(AWESOME_REVIEWS_DIR."framework/api/AR_API_Base.php");

//namespace AWESOME_REVIEWS\API;
/**
 * AR_API_Shop
 * Wrapper for plugin's complete api verbs surrounding AR_Shop
 *
 * @category
 * @package
 * @author
 * @copyright
 * @license
 * @version 1.0.0
 * @link
 * @see
 * @since 1.2.5
 */
class Awesome_Slides_API extends WP_REST_Controller{
    protected $base = 'slide';
    
    public static $NAMESPACE = 'awesome-slides';
    
    protected static $instance;
    
    /**
    * get_instance
    * Singleton Pattern: allows for only one instance of a class at a time. 
    * 
    * Returns the current instance of the class or instantiate and return a new instance.
    *
    *
    * @return self
    *
    * @access
    * @static
    * @see
    * @since 1.0.1
    */
    public static function get_instance() {  
        if ( ! isset( self::$instance ) ) {
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
    public function __construct(){ 
        $this->register_routes();
    }
    
    /**
    * register_routes
    * Register all routes for /ad endpoints
    *
    *
    * @return
    *
    * @access
    * @static
    * @see
    * @since
    */
    public function register_routes(){
        parent::register_routes();
        
        register_rest_route( self::$NAMESPACE, "/{$this->base}/(?P<id>[\d]+)", array(
          array(
              'methods'         => WP_REST_Server::READABLE,
              'callback'        => array( $this, '_read' ),
              'permission_callback' => array( $this, 'update_ad_permissions_check' )
          ),
          array(
              'methods'         => WP_REST_Server::EDITABLE,
              'callback'        => array( $this, '_update' ),
              'permission_callback' => array( $this, 'update_ad_permissions_check' )
          ),
          array(
              'methods'  => WP_REST_Server::DELETABLE,
              'callback' => array( $this, '_delete' ),
              'permission_callback' => array( $this,'delete_ad_permissions_check' ),
              'args'     => array(
                  'force'    => array(
                      'default'      => false,
                  ),
              ),
          )
        ), true );
    }
    
    /**
    * _create
    * Handle Create, and Update verbs
    *
    * @param WP_REST_Request
    * @param $request
    *
    * @return
    *
    * @access
    * @static
    * @see
    * @since
    */
    public function _create(WP_REST_Request $request){
        $response = [
        'result'        =>  true
        ];
        
        
        $response = new WP_REST_Response( $response);
        
        return $response;
    }
    
    /**
    * _read
    * Handle read verbs
    *
    *
    * @return
    *
    * @access
    * @static
    * @see
    * @since
    */
    public function _read(){
        $response = [
         'result'        =>  true
        ];
        
        $response = new WP_REST_Response( $response);
        
        return $response;
    }
    
    /**
    * _update
    * Handle Create, and Update verbs
    *
    * @param WP_REST_Request
    * @param $request
    *
    * @return
    *
    * @access
    * @static
    * @see
    * @since
    */
    public function _update(WP_REST_Request $request){
        $action = !$request['action'] ? 'get' : $request['action']; 
        
        $response = [
            'result'        =>  true
        ];
        
        
        $response = new WP_REST_Response( $response);
        
        return $response;
    }
    
    
    /**
    * _delete
    * Handle Delete verbs
    *
    * @param WP_REST_Request
    * @param $request
    *
    * @return JSON String
    *
    * @access
    * @static
    * @see
    * @since
    */
    public function _delete(WP_REST_Request $request){
        $response = array(
          'success'	=> true,
        );
    
      
        $response = new WP_REST_Response( $response);
        
        return $response;
    }
    
    /**
    * _index
    * Handle read verbs on parameter-less requests
    *
    * @param WP_REST_Request
    * @param $request
    *
    * @return JSON String
    *
    * @access
    * @static
    * @see
    * @since
    */
    public function _index(WP_REST_Request $request){
        $response = array(
          'success'	=> true,
        );
    
        $AR_Shops = AR_Shop_Post_Type::get_instance(); 
        
        $response['ads'] = $AR_Shops->get_ads(
            [
				'ad_count'   	=> 10,
				'post_type'		=> 'slide',
			    'offset'			=> 0,
			    'suppress_filters' 	=> true,
			]    
        );
          
        $response = new WP_REST_Response( $response);
        
        return $response;
    }
    

    /**
    * _index
    * Enforce permissions and authorization around read verbs on parameter-less requests
    *
    * @param WP_REST_Request
    * @param $request
    *
    * @return bool
    *
    * @access
    * @static
    * @see
    * @since
    */
    public function index_permissions_check(){
        if(!parent::permissions_check())
            return false;
        
        return true;
    }
    
    
    /**
    * read_ad_permissions_check
    * Enforce permissions and authorization around read verbs
    *
    *
    * @return bool
    *
    * @access
    * @static
    * @see
    * @since
    */
    public function read_ad_permissions_check(){
      if(!parent::permissions_check())
            return false;
      
      return true;
    }
    
    /**
    * update_ad_permissions_check
    * Enforce permissions and authorization around create and update verbs
    *
    *
    * @return bool
    *
    * @access
    * @static
    * @see
    * @since
    */
    public function update_ad_permissions_check(){
      if(!parent::permissions_check())
            return false;
      
      return true;
    }
    
    /**
    * delete_ad_permissions_check
    * Enforce permissions and authorization around delete verbs
    *
    *
    * @return bool
    *
    * @access
    * @static
    * @see
    * @since
    */
    public function delete_ad_permissions_check(){
      if(!parent::permissions_check())
            return false;
      
      return true;
    }
    
}  Awesome_Slides_API::get_instance(); ?>