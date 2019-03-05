$(document).ready(function() {
    jQuery('.rvk-wrapper').each(function(event){
        var rvk = $(this);
        var rvkStatus = rvk.find('.rvk-status');
        var rvkDetails = rvk.find('.rvk-details');
        var rvkLink = rvk.find('.rvk-link');

        rvkStatus.addClass('spinner');
        rvkStatus.addClass('bel-laden01');
        jQuery.ajax({
            url:'/vufind/AJAX/JSON?method=getRVKStatus&rvk='+rvk.find('.rvk-details').attr('data-rvk'),
            dataType:'json',
            success:function(data, textStatus, jqXHR){
                rvkStatus.removeClass('spinner');
                rvkStatus.removeClass('bel-laden01');
                if (typeof rvkDetails.data('rvk-hover') === 'undefined') {
                    rvkDetails.addClass('rvk-details-show');
                    rvkDetails.html(data.data.join('<i class="bel-pfeil-l01"></i>'));
                } else {
                    rvkDetails.addClass('rvk-details-hide');
                    rvkDetails.html(data.data.join('<i class="bel-pfeil-l01"></i>'));
                    //rvkLink.attr('data-uk-tooltip', 'true');
                    //rvkLink.attr('title', data.data.join('<i class="bel-pfeil-l01"></i>'));

                    rvkLink.qtip({
                        content: {
                            text: data.data.join('<i class="bel-pfeil-l01"></i>'),
                        },
                        position: {
                            my: 'middle left',
                            at: 'middle right',
                        },
                        style: {
                            classes: 'qtip-light'
                        }
                    });
                }
            }
        });
    });
});