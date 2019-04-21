<?php class Appointments_Plus_Term extends TimberTerm{
    
    public $thumbnail;
    
    public $metas;
    
    public $PostClass = 'APPOINTMENTS_PLUS_Product';
    
    public $TermClass = 'Appointments_Plus_Term';
    
    public $type = 'taxonomy';
    
    public $text;
    
    public $link;
    
    protected $_specs;
    
    public function meta($key){
        if( isset( $this->metas[$key]) )
            return $this->metas[$key];
            
        return '';
    }
    
    public function specs():Array{
        if($this->_specs != null)
            return $this->_specs;
            
        $options = APPOINTMENTS_PLUS_Theme::get_options(); 
        
        if(!isset($options['pages']['strains']))
            return [];
        
        if(!isset($options['pages']['strains']['single_page_metas']))
            return [];
            
        $metas = $options['pages']['strains']['single_page_metas'];
        
        if(!is_array($metas) || empty($metas))
            return [];
         
        $metas = array_map(function($m){
            $meta = $this->meta($m);
            if(empty($meta))
                return false;
                
            return $meta;
        }, $metas);
        
        $metas = array_filter($metas, function($m){
            if($m === false)
                return false;
                
            return true;
        });
            
        $this->_specs = $metas;
        
        return $this->_specs;   
    }

    public function thumbnail(){
        $thumbnail_id = intval( get_term_meta($this->term_id, "_appointments_plus_term_meta_thumbnail_id", true) );
        if( $thumbnail_id > 1)
            return new Appointments_Plus_Image($thumbnail_id);
            
        $thumbnail_id = intval( get_term_meta($this->term_id, 'thumbnail_id', true) );
        if($thumbnail_id  > 1)
            return new Appointments_Plus_Image( $thumbnail_id);
            
        return $thumbnail_id;
    }
    
    public function children(){
        if ( !isset($this->_children) ) {
			$children = get_term_children($this->ID, $this->taxonomy);
			foreach ( $children as &$child ) {
				$child = new Appointments_Plus_Term($child);
			}
			$this->_children = $children;
		}
		return $this->_children;
    }
    
    public function to_rest(){
        $array = $this->to_array();
        
        unset($array['thumbnail']['file_loc']);
        
        return $array;
    }
    
    public function content(){
        return apply_filters('the_content', $this->description());
    }
    
    public function excerpt(){
        return $this->meta("_appointments_plus_term_meta_excerpt")['value'];
    }
    
    public function to_array(){
        $parent  = $this->parent;
        
        if(is_a($parent, 'Appointments_Plus_Term'))
            $parent = get_object_vars($parent);
            
        $array = get_object_vars($this); 
        
        $array['thumbnail'] = $this->thumbnail ? $this->thumbnail->to_array(): null;
        unset($array['_children']);
        
        if($this->thumbnail){
            $array['thumbnail']['sizes']['default'] = array(
                'link'=> TimberImageHelper::letterbox($this->thumbnail->src(),440,240,'#FFF')
            );
        }
        
        $array['parent'] = $parent;
        
        return $array;
    }
    
    protected function init($tid){ 
        parent::init($tid);
        
        $this->metas = get_term_meta($this->ID);
        $metas_proccessed = array();
        
        foreach($this->metas as $m_key => $m){
            $metas_proccessed[$m_key]= [
              'meta'    =>  ucwords( str_replace('_', ' ', str_replace('percent', '%',  str_replace('_or_', '/', str_replace('_appointments_plus_term_meta_', '', $m_key) ) ) ) ),
              'value'   =>  $m[0]
            ];
        }
        
        $this->metas = $metas_proccessed;
        unset($metas_proccessed);
        
        $this->text = $this->name;
        $this->link = $this->link();
        
        if(defined('REST_REQUEST') && REST_REQUEST){
            
        }
        
        if($this->parent > 0)
            $this->parent = new self($this->parent);
        
        $this->thumbnail = $this->thumbnail();
       /* if($this->meta("_appointments_plus_term_meta_thumbnail_id")){
            $this->thumbnail = new Appointments_Plus_Image( $this->meta("_appointments_plus_term_meta_thumbnail_id") ); 
        }elseif($this->meta('thumbnail_id')){
            $this->thumbnail = new Appointments_Plus_Image( $this->meta('thumbnail_id') );
        }*/
        
        
        
        //$this->count = appointments_plus_get_term_post_count($this->taxonomy, $this->ID);
    }
}