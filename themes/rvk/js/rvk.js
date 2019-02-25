$(document).ready(function() {
    jQuery('.rvk-wrapper').each(function(event){
        var rvk = $(this);
        rvk.find('.rvk-status').addClass('spinner');
        rvk.find('.rvk-status').addClass('bel-laden01');
        jQuery.ajax({
            url:'/vufind/AJAX/JSON?method=getRVKStatus&rvk='+rvk.find('.rvk-details').attr('data-rvk'),
            dataType:'json',
            success:function(data, textStatus, jqXHR){
                rvk.find('.rvk-status').removeClass('spinner');
                rvk.find('.rvk-status').removeClass('bel-laden01');
                rvk.find('.rvk-details').addClass('rvk-details-show');
                rvk.find('.rvk-details').html(data.data.join('<i class="bel-pfeil-l01"></i>'));
            }
        });
    });
});