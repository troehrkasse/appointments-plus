<?php

function awesome_transformations_beautify($string, $remove_also=array() ){
	$string = ucwords( str_replace( '_', ' ', str_replace('-', ' ',$string) ) );
	
	if(empty($remove_also))
		return $string;
	
	foreach($remove_also as $char){
		$string = str_replace($char, '', $string);
	}
	
	return $string;
}

function awesome_transformations_sanitize_input_KSES($string){
	global $allowedposttags;
	$allowed_atts = array('href' => array(),'title' => array(),'type' => array(),'id' => array(),'class' =>array(), 'rel'=>array(), 'style'=>array(), 'src'=>array());
	$allowedposttags['strong'] = $allowed_atts;
	$allowedposttags['script'] = $allowed_atts;
	$allowedposttags['div'] = $allowed_atts;
	$allowedposttags['small'] = $allowed_atts;
	$allowedposttags['span'] = $allowed_atts;
	$allowedposttags['abbr'] = $allowed_atts;
	$allowedposttags['code'] = $allowed_atts;
	$allowedposttags['div'] = $allowed_atts;
	$allowedposttags['img'] = $allowed_atts;
	$allowedposttags['h1'] = $allowed_atts;
	$allowedposttags['h2'] = $allowed_atts;
	$allowedposttags['h3'] = $allowed_atts;
	$allowedposttags['h4'] = $allowed_atts;
	$allowedposttags['h5'] = $allowed_atts;
	$allowedposttags['ol'] = $allowed_atts;
	$allowedposttags['ul'] = $allowed_atts;
	$allowedposttags['li'] = $allowed_atts;
	$allowedposttags['em'] = $allowed_atts;
	$allowedposttags['p'] = $allowed_atts;
	$allowedposttags['a'] = $allowed_atts;
	return wp_kses_post($string, $allowedposttags);
}
