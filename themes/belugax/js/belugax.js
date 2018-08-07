$(document).ready(function() {
    /*
     * Init tooltips on belugino format icons on result list.
     */
    $('.belugino-icon-result-list').qtip({
        style: {
            classes: 'belugino-icon-result-list-tooltip'
        },
        position: {
            my: 'right center',
            at: 'left center'
        }
    });

    /*
     * Init tooltips on belugino icon on home page.
     */
    $('#infoopen').find('i').qtip({
        style: {
            classes: 'belugino-icon-result-list-tooltip'
        },
        position: {
            my: 'bottom center',
            at: 'top center'
        }
    });

    /*
     * Register change events for catalog select on home page.
     */
    $('.belugax-catalog-select').on('change', function(){
        belugaxCatalogSelect($(this).data('index'));
    });
    $('#belugax-catalog-select-searchbox').on('change', function(){
        if ($(this).prop('checked')) {
            belugaxCatalogSelect(0);
        } else {
            belugaxCatalogSelect(1);
        }
    });

    /*
     * Perform change of catalog on home page.
     */
    function belugaxCatalogSelect (index) {
        if (index == 0) {
            $('#belugax-catalog-select-searchbox').prop('checked', true);
        } else {
            $('#belugax-catalog-select-searchbox').prop('checked', false);
        }
        $('#belugax-catalog-select_'+index).prop('checked', true);

        $('#searchForm').attr('action', $('#belugax-catalog-select_'+index).data('url'));
    }

    /*
     * Set badge as item is added to or removed from book bag.
     */
    $('.cart-add').on('click', function(){
        badgeStatus($(this).parents('.result').find('.result-badge i'));
    })
    $('.cart-remove').on('click', function(){
        badgeStatus($(this).parents('.result').find('.result-badge i'));
    })
    function badgeStatus(element){
        element.toggleClass('bel-stern01');
    }

    /*
     * Toggle summary an detail page.
     */
    $('#belugax-summary-toggle').on('click', function(){
       $('#belugax-summary-preview').toggleClass('hidden');
       $('#belugax-summary-full').toggleClass('hidden');
       $(this).find('i').toggleClass('bel-pfeil-u01').toggleClass('bel-pfeil-o01');
    });

    /*
     * Get result count for inactive Tabs
     */
    $('.belugax-getTabResultCountAjax').each(function(){
        var $this = $(this);
        var indexClass = $(this).data('belugaxgettabresultcountajaxclass');
        var lookfor = $(this).data('belugaxgettabresultcountajaxlookfor');
        jQuery.ajax({
            url:'/vufind/Ajax/JSON?method=getTabResultCount&class='+indexClass+'&lookfor='+lookfor,
            dataType:'json',
            success:function(data, textStatus, jqXHR){
                $this.find('a').append(' ('+data.data+')')
            }
        });
    });
});