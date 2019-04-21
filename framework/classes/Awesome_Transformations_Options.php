<?php
if ( ! defined( 'ABSPATH' ) )  exit; //exit if access directly

class APPOINTMENTS_PLUS_Options{
    private $_options=array();
    
    /**
     * 
     */
    protected static $instance;
    
    
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}  
	
	public function __construct(){
	    
	    $this->init();
	}
    
    public function get_options(){
            
        return $this->_options;
    }
	
	public function create_customizer_settings(WP_Customize_Manager $wp_customize){
	    $panels = apply_filters('af_customizer_panels', (array) $this->get_panels() );// !Kint::dump($panels);
	    $panels = apply_filters('awesome_framework_customizer_panels', $panels );
	    
	    foreach($panels as $panel){
	        $panel = (array) $panel;
	        $panel = apply_filters('awesome_framework_customizer_panel', $panel );
	        $panel = apply_filters('af_customizer_panel', $panel );
	        
	        if(!isset($panel['id'])) continue;
	        
	        $wp_customize->add_panel( $panel['id'], $panel );
	    }
	    
	    $sections = apply_filters('af_customizer_sections', (array) $this->get_sections() ); 
	    $sections = apply_filters('awesome_framework_customizer_sections', $sections ); 

	    foreach($sections as $section){
	        $section = (array) $section;
	        $section = apply_filters('awesome_framework_customizer_section', $section );
	        $section = apply_filters('af_customizer_section', $section );
	        
	        if(!isset($section['id'])) continue;
	        
	        if(!isset($section['panel'] ))
	        	$section['panel'] = 'appointments_plus_panel_options';
	        
	        $wp_customize->add_section( $section['id'], $section );
	    }
	    
	    $settings = apply_filters('af_customizer_settings', (array) $this->get_settings() );
	    $settings = apply_filters('awesome_framework_customizer_settings', $settings );
	    foreach($settings as $setting){
	        $setting = (array) $setting;
	        $setting = apply_filters('awesome_framework_customizer_setting', $setting );
	        
	        if(!isset($setting['id'])) continue;
	        
	        $wp_customize->add_setting( $setting['id'], $setting );
	    }
	    
	    $controls = apply_filters('af_customizer_controls', (array) $this->get_controls() ); //!Kint::dump($controls); die();
	    $controls = apply_filters('awesome_framework_customizer_controls', $controls );
	    foreach($controls as $control){ //!Kint::dump($control); die();
	        $control = (array) $control;
	        $control = apply_filters('awesome_framework_customizer_control', $control );
	        $control = apply_filters('af_customizer_control', $control );
	        
	        if(!isset($control['id'])) continue;
	        
	        if(isset($control['class'])){
	            $wp_customize->add_control( new $control['class']( $wp_customize, $control['id'], $control ) );
	        }else{
	            $wp_customize->add_control( $control['id'], $control );
	        }
	    }
        
	}
    
    protected function get_panels():Array{
	    return [
	        [
	            'id'            =>  'appointments_plus_panel_options',
	            'title'         => 'Site Options',
                'description'   =>  'Site Customization Options'        
	        ]
	    ];
	}
    	
	protected function get_sections():Array{
	    return [
	        [
	            'id'            =>  'appointments_plus_section_general',
	            'title'         => 'General',
                'description'   =>  'General Settings',
                'panel'         =>  'appointments_plus_panel_options'
	        ],
	        [
	            'id'            =>  'appointments_plus_section_modules',
	            'title'         => 'Modules',
                'description'   =>  'Enable or Disable modules',
                'panel'         =>  'appointments_plus_panel_options'
	        ],
	        [
	            'id'            =>  'appointments_plus_section_apis',
	            'title'         => 'APIs',
                'description'   =>  'API access (i.e, facebook, google, strip, etc)',
                'panel'         =>  'appointments_plus_panel_options'
	        ],
            [
                'id'            =>  'appointments_plus_section_support',
                'title'         =>  'Support Contacts',
                'description'   =>  'Contact information for client support staff',
                'panel'         =>  'appointments_plus_panel_options'
            ]
	    ];
	}
	
	protected function get_settings():Array{
	    return [
	        [
	            'id'        =>  'appointments_plus_settings[modules]',
	            'default'   => __( '', 'csip' ),
                'type'      =>  'option',
	        ],
	        [
	            'id'        =>  'appointments_plus_settings[api][facebook][app_key]',
                'type'      =>  'option',
	        ],
	        [
	            'id'        =>  'appointments_plus_settings[api][facebook][app_secret]',
                'type'      =>  'option',
	        ],
	        [
	            'id'        =>  'appointments_plus_settings[api][google][maps_api_key]',
                'type'      =>  'option',
	        ],
	        [
	            'id'        =>  'appointments_plus_settings[awesome_search][live_search_params]',
                'type'      =>  'option',
                'sanitize_callback' => '',
                'sanitize_js_callback' => '',
	        ],
            [
                'id'        =>  'appointments_plus_settings[support][contact][message]',
                'default'   =>  'If you have any problems scheduling an appointment please contact [name] at [email] or by text at [phone]',
                'type'      =>  'option',
            ],
            [
                'id'        =>  'appointments_plus_settings[support][contact][name]',
                'type'      =>  'option',
            ],
            [
                'id'        =>  'appointments_plus_settings[support][contact][email]',
                'type'      =>  'option',
            ],
            [
                'id'        =>  'appointments_plus_settings[support][contact][phone]',
                'type'      =>  'option',
            ]
	    ];
	}
	
	protected function get_controls():Array{
	    return [
	        [
	            'id'            =>  'appointments_plus_settings[modules]',
	            'class'         =>  'Appointments_Plus_Customize_Control_Checkbox_Multiple',
                'type'          =>  'checkbox',
                'section'       =>  'appointments_plus_section_modules',
                'label'         =>  __( 'Modules' ),
                'description'   =>  __( 'Select Modules you would like to use on this site.'),
                'sanitize_callback' =>  array("Appointments_Plus_Customize_Control_Checkbox_Multiple", "sanitize_control"),
                'choices'       =>  [
                    'mailgun'     	=>  'Mailgun'
                ],
                'input_attrs'   =>  [
                    'class'     => 'customize-control-checkbox-multiple'    
                ]
	        ],
	        [
	            'id'            =>  'appointments_plus_settings[api][facebook][app_key]',
                'type'          =>  'text',
                'section'       =>  'appointments_plus_section_apis',
                'label'         =>  __( 'Facebook APP Key' ),
                'description'   =>  __( 'Enter APP Key to link site with facebook'),
	        ],
	        [
	            'id'            =>  'appointments_plus_settings[api][facebook][app_secret]',
                'type'          =>  'text',
                'section'       =>  'appointments_plus_section_apis',
                'label'         =>  __( 'Facebook APP Secret' ),
                'description'   =>  __( 'Enter App Secret to link site with facebook'),
	        ],
	        [
	            'id'            =>  'appointments_plus_settings[api][google][maps_api_key]',
                'type'          =>  'text',
                'section'       =>  'appointments_plus_section_apis',
                'label'         =>  __( 'Google Maps API Key' ),
                'description'   =>  __( ''),
	        ],
            [
                'id'            =>  'appointments_plus_settings[support][contact][message]',
                'type'          =>  'textarea',
                'section'       =>  'appointments_plus_section_support',
                'label'         =>  __( 'Support Contact Message' ),
                'description'   =>  __( 'Customize the message that will appear. Use [name], [email], or [phone] to include those details.'),
            ],
            [
                'id'            =>  'appointments_plus_settings[support][contact][name]',
                'type'          =>  'text',
                'section'       =>  'appointments_plus_section_support',
                'label'         =>  __( 'Support Contact Name' ),
                'description'   =>  __( 'Enter the name of the support person to feature on the site'),
            ],
            [
                'id'            =>  'appointments_plus_settings[support][contact][email]',
                'type'          =>  'email',
                'section'       =>  'appointments_plus_section_support',
                'label'         =>  __( 'Support Contact Email' ),
                'description'   =>  __( 'Enter the email address of the support person to feature on the site'),
            ],
            [
                'id'            =>  'appointments_plus_settings[support][contact][phone]',
                'type'          =>  'number',
                'section'       =>  'appointments_plus_section_support',
                'label'         =>  __( 'Support Contact Phone' ),
                'description'   =>  __( 'Enter the phone number of the support person to feature on the site'),
            ]
	    ];
	}
	
	protected function init(){ 
	    add_action('customize_register', array(&$this, 'create_customizer_settings'), 40);
	}
}APPOINTMENTS_PLUS_Options::get_instance();