$(document).ready(function() {
    jQuery('.belugaSFXLinkDetail').each(function(){
        var link = jQuery(this);
        jQuery.ajax({
            url:'/vufind/BelugaAjax/JSON?method=checkSFX&link='+link.attr('data-link'),
            dataType:'json',
            success:function(data, textStatus, jqXHR){
                link.css('display', 'inline');
                if (data.data == 'fullText') {
                    link.find('span').css('background', 'url(/vufind/themes/belugax/images/icon_openbook.png) no-repeat 0px 1px');
                    link.attr('title', 'Zum elektronischen Text');
                } else {
                    link.remove();
                }
            }
        });
    });

    jQuery('.bu_fulltext').click(function(event){
        event.preventDefault();
        //jQuery(this).attr('class', jQuery(this).attr('class')+' bu_fulltext_disabled');
        jQuery(this).css('display', 'none');
        jQuery('#bu_fulltext_links_'+jQuery(this).attr('data-id')).attr('style', 'display:block; margin-top: 15px;');
        jQuery('#bu_fulltext_links_'+jQuery(this).attr('data-id')).find('.belugaSFXLink').each(function(){
            var link = jQuery(this);
            link.css('display', 'inline');
            link.css('color', '#B0B0B0');
            link.css('cursor', 'default');
            link.find('.sfx-status').addClass('spinner');
            link.find('.sfx-status').addClass('bel-laden01');

            jQuery.ajax({
                url:'/vufind/AJAX/JSON?method=getSFXStatus&link='+link.attr('data-link'),
                dataType:'json',
                success:function(data, textStatus, jqXHR){
                    link.css('color', '#1D1D1D');
                    link.css('cursor', 'pointer');
                    if (data.data == 'fullText') {
                        link.find('.sfx-status').removeClass('spinner');
                        link.find('.sfx-status').removeClass('bel-laden01');
                        link.find('.sfx-status').addClass('bel-papier');
                    } else {
                        link.remove();
                    }
                }
            });
        });
    });
});