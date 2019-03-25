jQuery(document).ready(function() {
    $('span.delivery_article').each(function() {
        var element = $(this);
        var topId = element.attr('data-top-id');
        var searchClassId = element.attr('data-searchclass-id');
        $.ajax({
            url:'/vufind/AJAX/JSON?method=checkAvailability',
            dataType:'json',
            data:{ppn:topId, source:searchClassId},
            success:function(data, textStatus) {
                if (data.data.available == 'available') {
                    element.attr('style', 'display:block');
                }
            }
        });
    });
});
