jQuery(document).ready(function() {
    jQuery('.fulltextfinder-availability').each(function(){
        var $this = jQuery(this);
        var $openurl = jQuery(this).data('openurl');
        var $list = jQuery(this).data('list');
        var $searchClassId = jQuery(this).data('searchClassId');

        jQuery.ajax({
            url:'/vufind/AJAX/JSON?method=getFulltextFinder',
            dataType:'json',
            data:{
                openurl:$openurl,
                list:$list,
                searchClassId:$searchClassId
            },
            success:function(data, textStatus) {
                $this.html(data.data.html);
            }
        });
    });
});