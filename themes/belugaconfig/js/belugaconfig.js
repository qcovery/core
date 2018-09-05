$(document).ready(function() {
    /*
     * Get result count for inactive Tabs
     */
    $('.searchTabResultCount').each(function(){
        var $this = $(this);
        var searchClass = $(this).data('searchclass');
        var queryString = $(this).data('query');
        jQuery.ajax({
            url:'/vufind/AJAX/JSON?method=getResultCount',
            dataType:'json',
            data:{querystring:queryString, source:searchClass},
            success:function(data, textStatus, jqXHR){
                $this.find('a').append(' ('+data.data.total+')')
            }
        });
    });
});
