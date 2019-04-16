<?php

class Awesome_Slides_Shortcodes
{
    public static function awesome_slider($atts)
    {
        $atts = shortcode_atts(
        array(
            'background_color' =>   '#F2F2F2',
            'background_image' =>   false,
            'placement'        =>   ''
        ), $atts, 'awesome_slider' );

        $slider = false;
        if(isset($atts['placement']) && !empty($atts['placement'])){
            $slider = new TimberTerm($atts['placement']);

            if(intval($slider->term_id) > 0){
                $slider->slides = Timber::get_posts([
                    'post_type'     =>  'slide',
                    'tax_query'     =>  [
                        [
                            'taxonomy' => 'placement',
                            'field'    => 'term_id',
                            'terms'    => $slider->term_id,
                        ]
                    ]
                ]);
            }
        }

        $context = compact('atts', 'slider');


        return Timber::compile('shortcodes/awesome-slider.twig', $context);
    }
}

add_shortcode( 'awesome_slider', array('Awesome_Slides_Shortcodes', 'awesome_slider') );