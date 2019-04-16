/**
 *  Advertiser Rail Slides 
 */
jQuery(document).ready(function($){
	var _script = $('.advertisers-rail-slides-loader').data('script');
	
	if(typeof _script == "undefined") return false;
	
	var ele = document.createElement("script");
	ele.type="text/javascript";
	ele.setAttribute('defer', 'defer');
	
	ele.src = _script;
	document.body.appendChild(ele);  
});