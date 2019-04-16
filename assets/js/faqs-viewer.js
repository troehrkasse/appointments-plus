/**
 * Check to see if global event bus have already been defined by other vue widgets/apps. 
 * if not, define and instantiate a new bus.
 */
if(!bus){
    var bus = new Vue();
}


new Vue({
  el:'#faqs-viewer-app',
  template:'#faqs-app-viewer-template',
  data: function(){
    return {
      faq: null
    }
  }, 
  created: function(){
    this.faq = false;
    bus.$on('view-faq', faq => this.viewFAQ(faq));
  },
  computed: {
    
  },
  methods: {
    viewFAQ: function(faq){
      if(typeof faq == 'undefined')
        return;
        
      jQuery('.faqs-viewer-wrapper').siblings().removeClass('show');
      jQuery('.faqs-viewer-wrapper').addClass('show');
      
      this.faq = faq;
    },
    closeViewer: function(){
      jQuery('.faqs-viewer-wrapper').removeClass('show');
    }
  }
});

jQuery(document).ready(function($){
  $('.awesome-faqs-trigger').click(function(evt){
    evt.preventDefault();
    
    const faq = {
      text: $(this).closest('.awesome-faq').data('faq'),
      title: $(this).text()
    }
    
    bus.$emit('view-faq', faq, evt);
  });
});