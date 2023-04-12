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
     * Copy permalink to clipboard.
     */
    $('.permalinkCopy').on('click', function(e){
        e.preventDefault();
        var getUrl = window.location;
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val(getUrl.protocol+"//"+getUrl.host+$(this).attr('href').trim()).select();
        document.execCommand("copy");
        $temp.remove();
    });

    /*
     * Show summary on detail view.
     */
    $('.showSummary').on('click', function(e){
        e.preventDefault();
        document.getElementById('short_summary').style.display='none';
        document.getElementById('long_summary').style.display='block';
    });

    /*
     * Make transactions sortable on client side.
     */
    $('#checkedout_data').tablesorter({
        dateFormat: "ddmmyyyy"
    });
});
