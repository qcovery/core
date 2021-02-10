jQuery(document).ready(function() {
    jQuery('.fulltextfinder-availability').each(function(){
        var $this = jQuery(this);
        var $openurl = jQuery(this).data('openurl');
        var $list = jQuery(this).data('list');

        jQuery.ajax({
            url:'/vufind/AJAX/JSON?method=getFulltextFinder',
            dataType:'json',
            data:{
                openurl:$openurl,
                list:$list
            },
            success:function(data, textStatus) {
                $this.html(data.data.html);
            }
        });
    });
});