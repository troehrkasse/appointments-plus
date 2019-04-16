<?php 

/**
 * Awesome_Transformations_Image
 * Insert description here
 *
 * @category
 * @package
 * @author
 * @copyright
 * @license
 * @version
 * @link
 * @see
 * @since
 */
class Awesome_Transformations_Image extends TimberImage{
    //name of the image
    public $name;
    //title of image
    public $title;
    //constructor
    /**
     * __construct
     * Insert description here
     *
     * @param $iid
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public function __construct($iid){
        parent::__construct($iid);
        
        $this->init_ar_image();
    }
    //make the output look nice
    /**
     * pretty_permalink
     * Insert description here
     *
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public function pretty_permalink(){
        return site_url()."/image/{$this->name}";
    }
    
    /**
     * to_json
     * Insert description here
     *
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public function to_json(){
		return json_encode($this->to_array());
	}
	
    /**
     * to_array
     * Insert description here
     *
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
	public function to_array($args = array()){
	    $args = array_merge(array(
	       'thumb' => array(
	            'dimensions'        =>  array(320,240),
	            'function'          =>  'resize',
	            'letterbox_color'   =>  '#000',
	            'crop_area'         =>  'bottom'
	       ),
	       'large' => array(
	            'dimensions'        =>  array(600,300),
	            'function'          =>  'resize',
	            'letterbox_color'   =>  '#000',
	            'crop_area'         =>  'top'
	       )
	    ), $args);
	    
		$array = get_object_vars($this);
		
		$array = array_merge($array, array(
			'file_loc'	        =>  $this->pretty_permalink(),
			'permalink'         =>  $this->pretty_permalink()
		));
		
		if($args['thumb']['function'] == 'letterbox')
		    $image_link = TimberImageHelper::letterbox($this->src(), $args['thumb']['dimensions'][0], $args['thumb']['dimensions'][1], $args['thumb']['letterbox_color']);
		else
		    $image_link = TimberImageHelper::resize($this->src(), $args['thumb']['dimensions'][0], $args['thumb']['dimensions'][1], $args['thumb']['crop_area']);
		  
		$array['thumbnail'] = array(
		    'height'    =>  $args['thumb']['dimensions'][1],
		    'width'     =>  $args['thumb']['dimensions'][0],
		    'link'      =>  $image_link
		);
		
		if($args['large']['function'] == 'letterbox')
		    $image_link = TimberImageHelper::letterbox($this->src(), $args['large']['dimensions'][0], $args['large']['dimensions'][1], $args['large']['letterbox_color']);
		else
		    $image_link = TimberImageHelper::resize($this->src(), $args['large']['dimensions'][0], $args['large']['dimensions'][1], $args['large']['crop_area']);
		$array['large'] = array(
		    'height'    =>  $args['large']['dimensions'][1],
		    'width'     =>  $args['large']['dimensions'][0],
		    'link'      =>  $image_link
		);
		$array['full']  = array(
		    'link'  =>  $this->src()    
		);
		
		return $array;
	}
	
	/**
	 * 
	 * 
	 */
    public function palette($num=5){
        $palette = ColorThief::getPalette($sourceImage, $num);
        
        return $palette;
    }
     
    public function dominantColor($quality=10, $area=null){
        $dominantColor = ColorThief::getColor($this->file_loc, $quality, $area); 
        
        return $dominantColor;
    }
    
    public function getPixelColor($x=1, $y=1){
        $image = new Imagick($this->file_loc); 
        $pixel = $image->getImagePixelColor($x, $y); 
        $color = cap_rgb2hex( $pixel->getColor());
        
        unset($image); unset($pixel);
        
        return  $color;
    }
    
    //make a recognizable and usable filename
    /**
     * init_ar_image
     * Insert description here
     *
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    private function init_ar_image(){
        $name = explode('/',$this->_wp_attached_file); 
        $name = preg_replace('/\\.[^.\\s]{3,4}$/', '', $name[count($name) - 1]);
        
        $this->name = $name;
        
        $this->title = csip_beautify($this->name);
    }
}