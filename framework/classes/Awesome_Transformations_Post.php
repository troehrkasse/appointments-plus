<?php 
if ( ! defined( 'ABSPATH' ) ) exit;

class Appointments_Plus_Post extends TimberPost{
     public $TermClass = 'Appointments_Plus_Term';
     public $PostClass = 'Appointments_Plus_Post';
     public $ImageClass = 'Appointments_Plus_Image';
     
    /**
  	 * to_array
  	 * returns all product fields in an array
  	 *
  	 *
  	 * @return
  	 *
  	 * @access
  	 * @static
  	 * @see
  	 * @since
  	 */
  	public function to_array(){
  		$array = get_object_vars($this);
  	
  		$array['link'] = $this->link();
  		$array['post_excerpt'] = wp_trim_words($array['post_content'], 18, '...');
  		
  		return $array;
  	}
  	
  	/**
  	 * to_array
  	 * Return product with attribute save for RESTful interactions
  	 *
  	 *
  	 * @return
  	 *
  	 * @access
  	 * @static
  	 * @see
  	 * @since
  	 */
  	public function to_rest(){
  		$array =  $this->to_array();
  		unset($array['post_password']);
  		
  		$array = array_merge($array, array(
  		    'images'	    =>  $this->get_thumbnail() ? $this->get_thumbnail()->to_array(): null,
  		    'post_content'  =>   apply_filters('the_content', $array['post_content'])
  		));
  		
  		return $array;
  	}

    /**
     * init
     * Insert description here
     *
     * @param $pid
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    protected function init($pid=false){
        parent::init($pid);
    }
}