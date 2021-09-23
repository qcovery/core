$(document).ready(function() {
    function formatNumber(number) {
        number = number.toString();
        var len = number.length;
        var thousands = number.substring(len-3,len);
        var millions = number.substring(len-6,len-3);
        var billions = number.substring(len-9,len-6);
        var formattedNumber = '';
        if (billions != '') {
            formattedNumber += billions + '.';
        }
        if (millions != '') {
            formattedNumber += millions + '.';
        }
        if (thousands != '') {
            formattedNumber += thousands;
        }
        return formattedNumber;
    }

    /*
     * Get result count for inactive Tabs
     */
    $('.searchTabResultCount').each(function(){
        var $this = $(this);
        var searchClass = $(this).data('searchclass');
        var queryString = $(this).data('query');
        jQuery.ajax({
            url:VuFind.path + '/AJAX/JSON?method=getResultCount',
            dataType:'json',
            data:{querystring:queryString, source:searchClass},
            success:function(data, textStatus){
                $this.find('a').append(' ('+formatNumber(data.data.total)+')')
            }
        });
    });
});
