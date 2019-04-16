<?php

if ( ! defined( 'ABSPATH' ) )  exit; //exit if access directly

add_action( 'customize_register', function(){

    /**
     * Multiple checkbox customize control class.
     * 
     *
     */
    class Awesome_Transformations_Customize_Control_Editor extends WP_Customize_Control {
    
        /**
         * The type of customize control being rendered.
         *
         * @since  1.0.0
         * @access public
         * @var    string
         */
        public $type = 'checkbox-multiple';
    
        /**
         * Enqueue scripts/styles.
         *
         * @since  1.0.0
         * @access public
         * @return void
         */
        public function enqueue() {
            //wp_enqueue_script( 'csip-customize-controls', trailingslashit( AWESOME_TRANSFORMATIONS_URI ) . 'assets/js/customize-controls.js', array( 'jquery' ), null, true );
        }
    
        /**
         * Displays the control content.
         *
         * @since  1.0.0
         * @access public
         * @return void
         */
        public function render_content() {
            ?>
                <label>
                  <span class="customize-text_editor"><?php echo esc_html( $this->label ); ?></span>
                  <?php
                    $settings = [
                      'textarea_name'   => $this->id,
                      'teeny'           =>  false
                    ];
                    
                    wp_editor($this->value(), $this->id, $settings );
                  ?>
                </label>
        <?php }
        
        /**
         * Sanitize the Multiple checkbox values.
         *
         * @param string $values Values.
         * @return array Checked values.
         */
        public function sanitize_control( $values ) { 
        	$multi_values = ! is_array( $values ) ? explode( ',', $values ) : $values;
            return !empty( $multi_values ) ? array_map( 'sanitize_text_field', $multi_values ) : array();
        }
    }
    
}, 10 );