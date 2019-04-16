<?php

//Add Module to Modules menu selection list
add_filter('af_customizer_control', function($control){
	if(strpos($control['id'], '[modules]') === false)
		return $control;

	$control['choices']['awesome_slides']	=	'Awesome Slides & Ads';

	return $control;
});


global $AWESOME_FRAMEWORK;
if($AWESOME_FRAMEWORK['ENABLED_MODULES'] == null){
	return 'Module Not Enabled';
}

if(
	( !isset($AWESOME_FRAMEWORK['ENABLED_MODULES']) && is_array($AWESOME_FRAMEWORK['ENABLED_MODULES']) )
	||  !in_array('awesome_slides', $AWESOME_FRAMEWORK['ENABLED_MODULES'])
){
	return 'Module Not Enabled';
}

//Add Modudule's cusotm db settings
add_filter('af_customizer_settings', function($settings){
	$my_settings = [
		[
			'id'        =>  'awesome_slides[enable_ads]',
			'type'      =>  'option',
		]
	];

	return array_merge($my_settings, $settings);
}, 60);

//Hook in Module's custom controls
add_filter('af_customizer_controls', function($controls){
	$my_controls = [
		[
			'id'            =>  'awesome_slides[enable_ads]',
			'type'          =>  'checkbox',
			'section'       =>  'awesome_slides_section_general',
			'label'         =>  __( 'Enable Ads' ),
			'description'   =>  __( 'Check if you would like to sell ads on site')
		]
	];

	return array_merge($my_controls, $controls);
}, 60);

//Add Module's custom settings section
add_filter('af_customizer_sections', function($sections){
	$my_sections = [
		[
			'id'            =>  'awesome_slides_section_general',
			'title'         => 'Awesome Slides',
			'description'   =>  'Slides/Ads related settings'
		]
	];

	return array_merge($my_sections, (array) $sections);
}, 60);
