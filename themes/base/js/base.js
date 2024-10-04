jQuery(document).ready(function() {
    jQuery('.uisettings-contrast').on('click', function(e){
        e.preventDefault();
        var url = VuFind.path + '/AJAX/JSON?' + $.param({
            method: 'setUISettings',
            ui_settings_contrast: jQuery(this).data('uisettings-contrast')
        });
        $.ajax({
            dataType: 'json',
            cache: false,
            url: url
        })
        .done(function setUserInterfaceSettingsDone() {
            location.reload();
        });
    });

    jQuery('#staffview-toggle').on('click', function(e){
        e.preventDefault();
        jQuery('#staffview-content').toggleClass('hidden');
    });

    jQuery('#showSummaryLink').on('click', function(e){
        e.preventDefault();
        document.getElementById('showSummaryLink').style.display='none';
        document.getElementById('hideSummaryLink').style.display='block';
        document.getElementById('short_summary').style.display='none';
        document.getElementById('long_summary').style.display='block';
    });
    jQuery('#hideSummaryLink').on('click', function(e){
        e.preventDefault();
        document.getElementById('showSummaryLink').style.display='block';
        document.getElementById('hideSummaryLink').style.display='none';
        document.getElementById('short_summary').style.display='block';
        document.getElementById('long_summary').style.display='none';
    });

    /*
     * Make transactions sortable on client side.
     */
    $('#checkedout_data').tablesorter({
        dateFormat: "ddmmyyyy"
    });

});
