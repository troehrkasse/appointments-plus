<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Awesome_Slide' ) ) :
	/**
	 * Awesome_Slide
	 * Awesome_Slide object
	 *
	 * @category
	 * @package
	 * @author
	 * @copyright
	 * @license
	 * @version 2.0.0
	 * @link
	 * @see
	 */
	class Awesome_Slide extends TimberPost{
		/**
		 *
		 * Summary.
		 *
		 * instance data class
		 *
		 * @since 2.0.0
		 *
		 *
		 */
		protected static $instance;

		/**
		 * All ad's custom meta data
		 *
		 * defaults to only meta data created by Awesome_Slide_Post_Type, unless specified in constructor to include built-in meta data as well.
		 */
		private $slide_custom;

		/**
		 * Shop's unique db id.
		 */
		public $ID;

		/**
		 * Summary.
		 *
		 * @since 2.0.0
		 * @access (public)
		 * Registers meta key. This is for use with WordPress's various APIs as well as
		 * the custom post fields box. If you want to create custom UI, you will need to use add_meta_box.
		 */
		public $registered_metas;

		/**
		 * Summary.
		 *
		 * @since 2.0.0
		 * does the page have advertisments
		 */
		public $is_advertisered;

		/**
		 * Summary.
		 *
		 * @since 2.0.0
		 * The advertiser
		 */
		private $advertiser;

		/**
		 * Summary.
		 *
		 * @since x.x.x (if available)
		 * @var type $var Description.
		 */
		const META_PREFIX = "_awesome_slide_";

		/**
		 * Summary.
		 *
		 * Description.
		 *
		 * @since 2.0.0
		 *
		 * @global type $varname Description.
		 * @global type $varname Description.
		 *
		 * @param type $var Description.
		 * @param type $var Optional. Description.
		 * @return type Description.
		 */
		public function __call($func, $params){
			$key = array_search( str_replace('get_', '',$func), array_column($this->get_registered_meta(), 'meta'));

			$_return = null;

			if($key >= 0){
				if(preg_match("/get_/",$func)){
					return $this->get_slide_custom(str_replace('get_', '',$func));
				}
			}

			return $_return;
		}

		/**
		 * Summary.
		 *
		 * @since 2.0.0
		 *
		 */
		public function get_formatted_address(){
			return $this->get_details_street().", ".$this->get_details_city().", ".$this->get_details_state()." ".$this->get_details_postal_code().", ".$this->get_details_country();
		}

		/**
		 * Summary.
		 *
		 * @since 2.0.0
		 *
		 *  @returns JSON formatted string of data for google analytics hits.
		 */
		public function get_ga_data(){
			$type_term = $this->get_terms('type')[0];

			return json_encode(
				array(
					"type"	    =>  "event",
					"cat"		=> 	"ar_ads",
					"label"	    =>  "{$type_term->slug}_slide_".$this->get_advertiser_id().'_'.$this->ID.'_'.$this->thumbnail->ID,
					"context"	=>  "{$type_term->slug}-slides"
				)
			);
		}

		/**
		 * Summary.
		 *
		 * @since 2.0.0
		 *
		 *  @returns JSON formatted string of data for google analytics hits.
		 */
		public function ga_data(){
			return $this->get_ga_data();
		}

		/**
		 * Summary.
		 *
		 * @since 2.0.0
		 *
		 */
		public function get_slide_thumbnail($size="full"){
			switch($size){
				default:
					return get_the_post_thumbnail_url($this->ID);					
					break;
			}
		}

		/**
		 * get_permalink
		 * returns ad pretty permalink
		 *
		 *
		 * @see
		 * @since 2.0.0
		 */
		public function get_permalink(){
			return get_the_permalink($this->ID);
		}

		/**
		 * Returns the instance custom meta value.
		 *
		 * If no key is passed, all custom meta data is return.
		 * if id is passed, defaults to the current global Awesome_Slide_Post object.
		 *
		 * @param (int) $ID - optinal, defaults to the current global Awesome_Slide_Post objecct
		 * @param (text) $key - optional, defaults to all custom meta data
		 * @return mix
		 */
		public function get_slide_custom($key=null,$ID = null){
			global $wpdb;

			$ID == null ? $this->ID : $ID;

			if($this->slide_custom == null)
				$this->set_slide_custom();

			if($key==null){
				return $this->slide_custom;
			}else{
				if(!isset($this->slide_custom[$key]))
					return "";

				return $this->slide_custom[$key];
			}
		}

		/**
		 * Populate instance with of its custom meta data
		 *
		 * @param $key meta key sans
		 * @return mix
		 */
		public function get_registered_meta($key = null){
			global $wpdb;

			if($this->registered_metas == null)
				$this->registered_metas = $wpdb->get_results(
					"SELECT DISTINCT REPLACE( REPLACE(meta_key, SUBSTRING_INDEX(meta_key, '_awesome_slide_',1), ''), '_awesome_slide_','') AS meta FROM {$wpdb->prefix}postmeta  WHERE meta_key LIKE '%_awesome_slide_%'",
					ARRAY_A
				);

			if($key==null)
				return $this->registered_metas;
			else
				return $this->registered_metas[$key];
		}

		/**
		 * classes
		 * return post css classes
		 *
		 * @param $classes
		 *
		 * @return String
		 *
		 * @access
		 * @static
		 * @see
		 * @since
		 */
		public function classes($classes=array()) : String{
			$classes = array_merge(array('ar-dd'), $classes);

			return implode(' ', get_post_class($classes, $this->ID));
		}

		/**
		 * post_format
		 * return cpt post format
		 *
		 *
		 * @return String
		 *
		 * @access
		 * @static
		 * @see
		 * @since
		 */
		public function post_format(){
			return get_post_format($this->ID);
		}

		/**
		 * src
		 * return cpt post excerept
		 *
		 *
		 * @return String
		 *
		 * @access
		 * @static
		 * @see
		 * @since
		 */
		public function src(){
			return $this->post_excerpt;
		}

		/**
		 * set_slide_custom
		 * pulls cpt cusotm meta data from
		 *
		 *
		 * @access
		 * @static
		 * @see
		 * @since
		 */
		private function set_slide_custom(){
			global $wpdb;

			$metas = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DISTINCT REPLACE( REPLACE(meta_key, SUBSTRING_INDEX(meta_key, '_awesome_slide_',1), ''), '_awesome_slide_','') AS meta, meta_value as value from {$wpdb->prefix}postmeta WHERE meta_key LIKE '%s' AND post_id=%d",
					self::META_PREFIX."%",
					$this->ID
				),
				ARRAY_N
			);

			foreach($metas as $m){
				$this->slide_custom[$m[0]] = $m[1];
			}

			$posts = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}posts WHERE ID=%d",
					$this->ID
				),
				ARRAY_A
			);

			if($this->slide_custom !== null && (is_array($posts) && !empty($posts)))
				$this->slide_custom = array_replace_recursive($this->slide_custom, $posts[0]);
			else
				$this->slide_custom = array();

			/** Set shop advertiser */
			if(empty(trim($this->get_advertiser_id()))){
				$this->advertiser = new WP_Error( 'broke', __( "Slide is not associated with an advertiser", "ar" ) );
				$this->is_advertisered = false;
			}else{
				$this->advertiser = get_user_by( 'ID', $this->get_advertiser_id() );

				if(! is_wp_error($this->advertiser) )
					$this->is_advertisered = true;

				/** Set meta data */
				$advertiser_meta = get_user_meta($this->get_advertiser_id());

				if(is_array($advertiser_meta) && !empty($advertiser_meta)):
					foreach($advertiser_meta as $key => $_m){
						$this->slide_custom["advertiser_meta_".$key] = $_m[0];
					}
					$this->slide_custom['advertiser_meta_role'] = implode($this->advertiser->roles);
					$this->slide_custom['advertiser_meta_email'] = $this->advertiser->data->user_email;
				endif;
			}

			/** Set Slide Thumbnail */
			$attachment_id = get_post_thumbnail_id();
			$image_link  = wp_get_attachment_url( get_post_thumbnail_id() );

			if (  $image_link ){
				$image = wp_get_attachment_url( $attachment_id);
				if(function_exists('aq_resize')){
					$img = aq_resize($image, 600, 440, false, true, true);

					$this->slide_custom['thumbnail']['full'] = $image_link;
				}
			}
		}
	}
endif;

if ( ! class_exists( 'Awesome_Slide_Post_Type' ) ) :
	/**
	 * Awesome_Slide_Post_Type
	 * api and framework surrounding slides
	 *
	 * @category
	 * @package
	 * @author
	 * @copyright
	 * @license
	 * @version
	 * @link
	 * @see
	 * @since 1.0.0
	 */
	class Awesome_Slide_Post_Type{
		/**
		 * Holds cpt instance available meta data
		 */
		private $registered_metas;

		/**
		 * Current instances of post type. (holds a Awesome_Slide_Post objects)
		 */
		private $slides;

		private $post_type;

		/**
		 *
		 */
		private $classes = array();

		/**
		 *
		 */
		const META_PREFIX = "_awesome_slide_";

		/**
		 *
		 */
		protected static $instance;

		/**
		 *
		 * Return instance of Class
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * Class constructor
		 */
		public function __construct(){
			$this->init();

			$this->slides = array();
		}

		/**
		 * Create advertiser meta boxes
		 */
		public function slide_advertiser() {
			$prefix = '_awesome_slide_advertiser_';

			$cmb_details = new_cmb2_box( [
				'id'            => $prefix . 'metabox',
				'title'         => __( 'Advertiser', 'awesome_slides' ),
				'object_types'  => array( 'slide', ), // Post type
				'priority'   => 'core',
				'show_names' => true, // Show field names on the left
				'closed'     => false, // true to keep the metabox closed by default
			] );

			$cmb_details->add_field( [
				'name'       	=> __( 'User ID', 'awesome_slides' ),
				'desc'       	=> __( '', 'awesome_slides' ),
				'id'         	=> $prefix . 'id',
				'type'       	=> 'hidden',
			] );

			$cmb_details->add_field( array(
				'name'       	=> __( 'Find Sponsor', 'awesome_slides' ),
				'desc'       	=> __( 'Search for usr (<i>user must already be in the system to be added):', 'awesome_slides' ),
				'show_names' 	=> false,
				'id'         	=> $prefix . 'id',
				'type'       	=> 'ar_select2_select',
				'row_classes'	=>	['ar-shop-search-sponsor-wrapper'],
				'object_class'	=>	'WP_User',
				'attributes'	=> [
					'class'	=> 'ar-select2-ajax',
					'data'	=> [
						'url'	=>	'/user/search',
						'data'	=> 'users'
					],
					'multiple'	=> false
				]
			) );

			$cmb_details->add_field( [
				'name'       	=> __( 'First Name', 'awesome_slides' ),
				'desc'       	=> __( '', 'awesome_slides' ),
				'id'         	=> $prefix . 'first_name',
				'type'       	=> 'slide_advertiser_info',
				'attributes' 	=> array(
					'readonly' => 'readonly',
				),
			] );

			$cmb_details->add_field( [
				'name' 			=> __( 'Last Name', 'awesome_slides' ),
				'desc' 			=> __( '', 'awesome_slides' ),
				'id'   			=> $prefix . 'last_name',
				'type' 			=> 'slide_advertiser_info',
				'attributes' 	=> array(
					'readonly' => 'readonly',
				),
			] );

			$cmb_details->add_field( [
				'name' 			=> __( 'Email', 'awesome_slides' ),
				'desc' 			=> __( '', 'awesome_slides' ),
				'id'   			=> $prefix . 'email',
				'type' 			=> 'slide_advertiser_info',
				'attributes' 	=> array(
					'readonly' => 'readonly',
				),
			] );

			$cmb_details->add_field( [
				'name' 			=> __( 'Phone', 'awesome_slides' ),
				'desc' 			=> __( '', 'awesome_slides' ),
				'id'   			=> $prefix . 'phone',
				'type' 			=> 'slide_advertiser_info',
				'attributes' 	=> array(
					'readonly' => 'readonly',
				),
			] );
		}

		/**
		 * Slide sponsor meta boxes
		 */
		public function slide_details() {
			$prefix = '_awesome_slide_details_';

			$cmb_details = new_cmb2_box( [
				'id'            => $prefix . 'metabox',
				'title'         => __( 'Slide Details', 'awesome_slides' ),
				'object_types'  => array( 'slide', ), // Post type
				'priority'      => 'core',
				'show_names'    => true, // Show field names on the left
				'closed'        => false, // true to keep the metabox closed by default
			] );

			$cmb_details->add_field( [
				'name'       	=> __( 'Slide Link', 'awesome_slides' ),
				'desc'       	=> __( 'Button Link. If no Button Text is specified, the entire image may be hyperlinked (up to the theme implementation)', 'awesome_slides' ),
				'id'         	=> $prefix . 'url',
				'type'       	=> 'text_url',
				'attributes' 	=> [

				],
			] );

			$cmb_details->add_field( [
				'name'       	=> __( 'Button URL Title', 'awesome_slides' ),
				'desc'       	=> __( '', 'awesome_slides' ),
				'id'         	=> $prefix . 'url_title',
				'type'       	=> 'text',
				'attributes' 	=> [

				],
			] );

			$cmb_details->add_field( [
				'name'       	=> __( 'Button Text', 'awesome_slides' ),
				'desc'       	=> __( '', 'awesome_slides' ),
				'id'         	=> $prefix . 'button_text',
				'type'       	=> 'text',
				'attributes' 	=> array(

				),
			] );

            $cmb_details->add_field( [
                'name'       	=> __( 'Background Color', 'awesome_slides' ),
                'desc'       	=> __( '', 'awesome_slides' ),
                'id'         	=> $prefix . 'background_color',
                'type'       	=> 'colorpicker',
            ] );

            $cmb_details->add_field( [
                'name'       	=> __( 'Background Image', 'awesome_slides' ),
                'desc'       	=> __( '', 'awesome_slides' ),
                'id'         	=> $prefix . 'background_image',
                'type'       	=> 'file',
            ] );

			$cmb_details->add_field( [
				'name'       	=> __( 'Make Slide sticky', 'awesome_slides' ),
				'desc'       	=> __( '', 'awesome_slides' ),
				'id'         	=> $prefix . 'sticky_slide',
				'type'       	=> 'checkbox',
				'attributes' 	=> [

				]
			] );
		}

		/**
		 * gets rail slides designed for side bar etc.
		 */
		public function ajax_get_advertisers_rail_slides(){
			$response = [
				'success'	=> true,
				'errors'	=>	array(),
				'slides'		=>	array()
			];

			$response['slides']  = TimberHelper::transient(Awesome_Slides::get_transient_key("archive_advertisers_rail_slides_ajax"), function(){
				$args = [
					'post_type'			=> 'slide',
					'orderby'       	=> 'rand',
					'post_status'		=> 'publish',
					'posts_per_page'   	=> -1,
					'meta_query' => [
						'relation' => 'OR',
						array(
							'key'     => '_awesome_slide_details_sticky_ad',
							'value'   => 'on',
							'compare' => '!=',
						),
						array(
							'key'     => '_awesome_slide_details_sticky_ad',
							'compare' => 'NOT EXISTS'
						)
					],
					'tax_query' => array(
						'relation'	=> 'AND',
						array(
							'taxonomy' => 'placement',
							'field'    => 'slug',
							'terms'    => 'rail',
						),
						array(
							'taxonomy' 	=> 'post_format',
							'field'    	=> 'slug',
							'terms'    	=> array('post-format-link'),
							'operator'	=> 'NOT IN'
						)
					)
				]; $slides_q = new WP_Query( $args );

				$slides = array();
				while($slides_q->have_posts()){ $slides_q->the_post();
					$_ad = TimberHelper::transient(Awesome_Slides::get_transient_key('slide_'.get_the_id()), function(){
						return new Awesome_Slide(get_the_id());
					},Awesome_Slides::DATA_CACHE_TIME_LONG);

					switch($_ad->post_format){
						case 'link':
							$temp = array(
								'id'			=>	$_ad->ID,
								'title'			=> 	$_ad->get_post_title()
							);
							$temp['src'] = TimberHelper::ob_function(function(){
								echo $_ad->post_excerpt;
							});
							$slides[] = $temp;
							break;
						default:
							$slides[] = array(
								'id'			=>	$_ad->ID,
								'title'			=> 	$_ad->get_post_title(),
								'thumbnail'		=> 	$_ad->get_slide_thumbnail(),
								'creative_id'	=>	$_ad->thumbnail->ID,
								'url'			=> 	$_ad->get_details_url(),
								'advertiser'	=>	$_ad->get_advertiser_id()
							);
							break;
					}
				}wp_reset_postdata(); unset($slides_q);

				return $slides;
			},Awesome_Slides::DATA_CACHE_TIME_LONG);

			if(is_array($response['slides']) && count($response['slides']) > 0 )
				shuffle($response['slides']);

			$sticky_slides  = TimberHelper::transient(Awesome_Slides::get_transient_key("archive_advertisers_rail_sticky_slides_{$brand}"), function() use ($brand){
				$args = array(
					'post_type'			=> 'slide',
					'orderby'       	=> 'rand',
					'post_status'		=> 'publish',
					'posts_per_page'   	=> -1,
					'meta_query' => array(
						array(
							'key'     => '_awesome_slide_details_sticky_ad',
							'value'   => 'on',
							'compare' => '=',
						)
					),
					'tax_query' => array(
						'relation'	=>	'AND',
						array(
							'taxonomy' => 'type',
							'field'    => 'slug',
							'terms'    => 'rail',
						),
						array(
							'taxonomy' 	=> 'post_format',
							'field'    	=> 'slug',
							'terms'    	=> array('post-format-link'),
							'operator'	=> 'NOT IN'
						)
					)
				); $slides_q = new WP_Query( $args );

				$slides = array();
				while($slides_q->have_posts()){ $slides_q->the_post();
					$_ad = TimberHelper::transient(Awesome_Slides::get_transient_key('slide_'.get_the_id()), function(){
						return new Awesome_Slide(get_the_id());
					},Awesome_Slides::DATA_CACHE_TIME_LONG);

					$slides[] = array(
						'id'			=>	$_ad->ID,
						'title'			=> 	$_ad->get_post_title(),
						'thumbnail'		=> 	$_ad->get_slide_thumbnail(),
						'url'			=> 	$_ad->get_details_url(),
						'advertiser'	=>	$_ad->get_advertiser_id()
					);
				}wp_reset_postdata(); unset($slides_q);

				return $slides;
			}, Awesome_Slides::DATA_CACHE_TIME_LONG);

			$response['slides'] = array_merge($sticky_slides, $response['slides']);

			$response['slides'] = apply_filters('af_awesome_slides_get_advertiser_slides', $response['slides']);

			wp_send_json($response); die();
		}

		/**
		 * Register cpt using custom-post-type library
		 */
		public function register_post_type(){
			if(!class_exists('CPT')) return;

			$this->post_type = new CPT(
				array(
					'post_type_name' => 'slide',
					'singular' => 'Slide',
					'plural' => 'Slides',
					'slug' => 'slide'
				),
				array(
					'has_archive' 			=> 	true,
					'menu_position' 		=> 	8,
					'menu_icon' 			=> 	'dashicons-layout',
					'supports' 				=> 	array('title', 'excerpt', 'content','thumbnail', 'post-formats')
				)
			);

			$labels = array('menu_name'=>'Types');
			$this->post_type->register_taxonomy('placement',array(
				'hierarchical'               => true,
				'public'                     => true,
				'show_ui'                    => true,
				'show_admin_column'          => true,
				'show_in_nav_menus'          => true,
				'show_tagcloud'              => true,
			),$labels);
		}

		/*
		* Load required scripts
		*/
		public function load_admin_scripts_styles(){

		}

		/**
		 * Save the meta when the post is saved.
		 *
		 * @param int $post_id The ID of the post being saved.
		 */
		public function save_post($post_id){
			// Check security nonce
			if (! isset( $_POST['address_autocomplete_nonce'] ) || ! wp_verify_nonce( $_POST['address_autocomplete_nonce'], 'save_address_autocomplete' )) {
				return new WP_Error( 'security_fail', __( 'Security check failed.' ) );
			}

			// Check title submitted
			if ( empty( $_POST['post_title'] ) ) {
				return new WP_Error( 'post_data_missing', __( 'New post requires a title.' ) );
			}

			// If this is an autosave, our form has not been submitted
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return $post_id;

			// Check the user's permissions.
			if ( 'page' == $_POST['post_type'] ) {
				if ( ! current_user_can( 'edit_page', $post_id ) )
					return $post_id;
			} else {
				if ( ! current_user_can( 'edit_post', $post_id ) )
					return $post_id;
			}

			foreach($_POST as $key=> $_meta){
				if(! preg_match('/_awesome_slide_/', $key)) continue;

				/** Update the meta field. **/
				update_post_meta( $post_id, $key, sanitize_text_field($_meta) );
			}
		}

		/**
		 * Populate instance with of its custom meta data
		 *
		 * @param $key meta key sans
		 * @return mix
		 */
		public function get_registered_meta($key = null){

			if($this->registered_metas == null){

				$this->registered_metas = TimberHelper::transient(Awesome_Slides::get_transient_key('slide_registered_metas'), function(){
					global $wpdb;
					return $wpdb->get_results("SELECT DISTINCT REPLACE( REPLACE(meta_key, SUBSTRING_INDEX(meta_key, '_awesome_slide_',1), ''), '_awesome_slide_','') AS meta FROM {$wpdb->prefix}postmeta  WHERE meta_key LIKE '%_awesome_slide_%'", ARRAY_A );
				},Awesome_Slides::DATA_CACHE_TIME_LONG);
			}

			if($key==null)
				return $this->registered_metas;
			else
				return $this->registered_metas[$key];
		}

		/**
		 * Returns a single Awesome_Slide_Post object
		 */
		public function get_slide($ID = null){
			return  TimberHelper::transient(Awesome_Slides::get_transient_key('slide_'.$ID), function() use (&$ID){
				return new Awesome_Slide($ID);
			},Awesome_Slides::DATA_CACHE_TIME_LONG);
		}

		/**
		 *	Add slide columns to post type
		 */
		public function custom_slide_columns( $columns ) {
			$new_columns = array();
			// re-arrange $columns array to display columns in a specific order
			foreach( $columns as $key => $title ){
				// add the following columns before the 'date' column
				if( $key == 'date' ){
					$new_columns['ar_primary_contact'] 	= __( 'Primary Contact' );
					$new_columns['phone'] 				= __( 'Phone' );
				}
				$new_columns[$key] = $title;
			}
			return $new_columns;
		}

		/**
		 *	Add slide column data
		 */
		public function custom_slide_columns_data( $column, $post_id ){
			global $CAP;

			switch ( $column ) {
				case 'ar_primary_contact':
					$ad = $this->get_slide( $post_id );
					echo $ad->get_advertiser_meta_first_name();
					break;

				case 'phone':
					$ad = $this->get_slide( $post_id );
					echo $ad->get_advertiser_meta_phone();
					break;
			}
		}

		/**
		 * Print html for slide placement customer meta data
		 */
		public function add_placement_meta_fields(){
			Timber::render('admin/add-meta-fields-placement.twig', []);
		}

		/**
		 * Print and prepopulate html forms when editing slide placement customer meta data.
		 */
		public function edit_placement_meta_fields($term) {
			$context = [
				'autoplay'          =>   boolval(get_term_meta($term->term_id, 'autoplay', true)),
				'autoplaySpeed'     =>   get_term_meta($term->term_id, 'autoplaySpeed', true),
                'height'            =>   get_term_meta($term->term_id, 'height', true),
				'arrows'            =>   boolval(get_term_meta($term->term_id, 'arrows', true))
			];
			Timber::render('admin/edit-meta-fields-placement.twig', $context);
		}

		public function save_placement_meta_fields($term_id, $tt_id ){
			if(!isset($_POST['placement_meta'])) return;

			foreach($_POST['placement_meta'] as $key => $meta) {
				update_term_meta( $term_id, $key, $meta );
			}

			if(!isset($_POST['placement_meta']['arrows'])){
				update_term_meta( $term_id, 'arrows', 0 );
			}
			if(!isset($_POST['placement_meta']['autoplay'])){
				update_term_meta( $term_id, 'autoplay', 0 );
			}

            if(!isset($_POST['placement_meta']['height'])){
                update_term_meta( $term_id, 'height', 0 );
            }
		}

		/**
		 * Housekeeping
		 *
		 * @return void
		 */
		private function init(){
			$this->register_post_type();

			/** Return advertiser slides */
			add_action('wp_ajax_get_rail_slides', array(&$this,'ajax_get_advertisers_rail_slides'));
			add_action('wp_ajax_nopriv_get_rail_slides', array(&$this,'ajax_get_advertisers_rail_slides'));

			if(!is_admin()) return;

			/** Load custom ad details meta boxes */
			add_action('cmb2_admin_init', array(&$this,'slide_details'));

			/** Save Post */
			add_action( 'save_post_slide', array( &$this, 'save_post' ) );

			/** Load back-end scripts and styles */
			add_action( 'admin_enqueue_scripts', array( &$this,'load_admin_scripts_styles' ) );

			add_action( "placement_add_form_fields", [&$this, 'add_placement_meta_fields'], 60, 2 );
			add_action( "placement_edit_form_fields", [&$this, 'edit_placement_meta_fields'], 60, 2 );
			add_action( "edited_placement", [&$this, 'save_placement_meta_fields'], 60, 2 );
			add_action( "created_placement", [&$this, 'save_placement_meta_fields'], 60, 2 );

			// Add slide columns to post type
			// add_filter( 'manage_slide_posts_columns' , array( &$this, 'custom_slide_columns' ) );

			// Add slide column data
			// add_action( 'manage_slide_posts_custom_column' , array( &$this, 'custom_slide_columns_data' ), 10, 2 );
		}
	}Awesome_Slide_Post_Type::get_instance();
endif;