$(document).ready(function() {
    // executes when HTML-Document is loaded and DOM is ready
   
     
      if (location.hash !== '') $('a[href="' + location.hash + '"]').tab('show');
         return $('a[data-toggle="tab"]').on('shown', function(e) {
         return location.hash = $(e.target).attr('href').substr(1);
       });
     
     
   // document ready  
});