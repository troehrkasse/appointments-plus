jQuery(document).ready(function($){
	var _Ads = new Ads();
	var count = 0;
});

/**
 * Ads Object
 * 
 * Handles printing ads and events surround ads
 */
function Ads(){
	this.wrappers = jQuery('.advertiser-wrapper:visible');
	this.ads_count =0;
	this.ajax_url = $('.advertisers-rail-slides-loader').data('remote');
	this.ads = [];
	
	var _self = this;
	
	this.wrappers.each(function(){
		if( jQuery(this).is(':visible') )
			_self.ads_count = _self.ads_count + parseInt(jQuery(this).data('qty'));
	});
	
	/** Print Ad */
	this.getAdSource = function(_ad){
		if(typeof _ad == "undefined")
			return false;
			
		var _template = "<html><body style='background:transparent; margin:0;'><div>";
			_template += "<a style='display:block;' href='"+_ad.url+"' target='_blank' title='"+_ad.title+"'>";
				_template += "<img width=300 height=250 src='"+_ad.thumbnail+"' />";
			_template += "</a>";
		_template += "</div></body></html>";
		
		return _template;
	}
	
	this.printAds = function(){
		_self.wrappers = jQuery('.advertiser-wrapper:visible');
		
		if(_self.ads < 1){
			_self.setAds();
		}
		
		var $ad_key = 0;
		
		_self.wrappers.each(function(_i, _v){
			var _this = jQuery(this); _this.html('');
			
			for(var i=0; i < _this.data('qty'); i++ ){
				if(typeof _self.ads[$ad_key] == "undefined" || typeof _self.ads[$ad_key].thumbnail == "undefined")
					continue;
				
				var _ga_data  = {
					type:	'event',
					cat:	"ar_ads",
					label:	"rail_ad_"+_self.ads[$ad_key].advertiser+"_"+_self.ads[$ad_key].creative_id,
					context:"rail-ads"
				};
				
				var _iframe = jQuery('<iframe  />', {
					name: 	'ar-ads-'+i,
					id:   	'ar-ads-'+i,
					class:	"advertiser ar-ga-tracker",
					seamless: '',
					scrolling:	"no",
					src:	'javascript:"'+_self.getAdSource(_self.ads[$ad_key])+'"',
					'data-ga_data':	JSON.stringify(_ga_data, null, 2)
				});
				
				_this.append("<div class='ad-frame-wrapper'></div>");

				_this.children('.ad-frame-wrapper').last().append( _iframe);
				
				/* Create event for single ad load */
				jQuery.event.trigger({
					type:		"ad_loaded",
					message:	{
						ad:			_iframe,
						ga_data: 	_ga_data
					}
				});	
			
				$ad_key++;
			}
		});
		
		/** Create event for when all ads are loaded */
		jQuery.event.trigger({
			type:"ads_loaded",
			message: {
				ads:jQuery('iframe.ar-ga-tracker')
			}
		});
	}
	
	this.setAds = function(){
		var _data = {
			action:	"get_rail_slides",
			qty:	_self.ads_count
		};
		
		if(jQuery('.single-review').length > 0){
			_data['review'] = jQuery('article.review').data('id');
		}
		
		if(typeof AF_CONTEXT != 'undefined'){
			_data['context'] = AF_CONTEXT;
		}
		
		jQuery.ajax({
			url:		_self.ajax_url,
			method:		"GET",
			dataType:	'JSON',
			data:	_data
		}).then(function(_d,_o,_e){
			if(_d.success && _d.slides.length > 0){
				_self.ads = _d.slides;
				_self.printAds();
			}
		}).fail(function(_d,_o,_e){
			console.log(_d);
		});
	}
	
	this.setAds(); 
	
	jQuery(window).resize(function(){
		if(jQuery(this).width() < 720){
			_self.printAds();
		}else{
			_self.printAds();
		}
	});
}