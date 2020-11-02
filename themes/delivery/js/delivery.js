jQuery(document).ready(function() {
    jQuery('span.delivery_article').each(function() {
        var element = $(this);
        var topId = element.attr('data-top-id');
        var domain = element.attr('data-domain');
        var searchClassId = element.attr('data-searchclass-id');
        jQuery.ajax({
            url:'/vufind/AJAX/JSON?method=checkAvailability',
            dataType:'json',
            data:{ppn:topId, source:searchClassId, domain:domain},
            success:function(data, textStatus) {
                if (data.data.available == 'available') {
                    element.attr('style', 'display:inline');
                }
            }
        });
    });
    jQuery('p#delivery_email a').on('click', function(event) {
        jQuery('#delivery_email #delivery_email_text').attr('style', 'display:none');
        jQuery('#delivery_email #delivery_email_field').attr('style', 'display:inline');
    });
});
