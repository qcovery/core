let loginTippyInstance = null;
$(document).ready(function() {
    /*
     * Register change events for catalog select on home page.
     */

    $('#belugax-catalog-select-searchbox').on('change', function(){
        if ($(this).prop('checked')) {
            belugaxCatalogSelect('/vufind/Search/Results');
        } else {
            belugaxCatalogSelect('/vufind/Search2/Results');
        }
    });

    /*
     * Perform change of catalog on home page.
     */
    function belugaxCatalogSelect (action) {
        $('#searchForm').attr('action', action);
    }
    
    $('.media-body-inner').find('a').each(function(){
        if ($(this).attr('href').indexOf('/vufind/') < 0) {
            $(this).attr('target', '_blank');
        }
    });

    /*
     * Register input event for login field.
     */
    $('#modal .modal-body').on('input', '#login_ILS_username', function(e) {
        performLoginCheck(e);
    }).on('blur', '#login_ILS_username', function(e) {
        loginTippyInstance.destroy();
        loginTippyInstance = null;
    });

    /*
     * Hide summary on detail view.
     */
    $('.hideSummary').on('click', function(e){
        e.preventDefault();
        document.getElementById('short_summary').style.display='block';
        document.getElementById('long_summary').style.display='none';
    });
});

function initClearLink (link) {
    $(link).on('click', function (e) {
        e.preventDefault();
        $('input[type="text"]').val('');
        $("option:selected").removeAttr("selected");
        $("#illustrated_-1").click();
    });
}

function initDaiaPlusOverlay () {
    $('.daiaplus-overlay').on('click', function(e){
        e.preventDefault();
        $('#modal .modal-body').html($('#'+$(this).data('daiaplus-overlay')).html());
        VuFind.modal('show');
    });
}

/*
 * Make sure input field does not contain any spaces. Show tooltip if user inserted spaces.
 */
function performLoginCheck(event) {
    let regex = / /g;
    if (event.target.value.match(regex)) {
        event.target.value = event.target.value.replace(regex, '');
        if (loginTippyInstance == null) {
            loginTippyInstance = tippy(event.target, {content: VuFind.translate('input_no_space'), trigger: 'manual'});
        }
        loginTippyInstance.show();
    }
}