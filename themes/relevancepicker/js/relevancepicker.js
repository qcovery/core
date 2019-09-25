jQuery(document).ready(function() {
  jQuery('a.relevanceTooltip').click(function(){
    jQuery(this).children('span').attr('style', 'display:block');
  });
  //jQuery('a.relevanceTooltip').mouseout(function(){
  //  jQuery(this).children('span').attr('display', 'none');
  //});
});
