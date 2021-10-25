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

    jQuery('.bkl-wrapper').each(function(event){
        var bkl = $(this);
        var bklStatus = bkl.find('.bkl-status');
        var bklDetails = bkl.find('.bkl-details');
        var bklLink = bkl.find('.bkl-link');

        bklStatus.addClass('spinner');
        bklStatus.addClass('bel-laden01');
        jQuery.ajax({
            url:'/vufind/AJAX/JSON?method=getBKLStatus&bkl='+bkl.find('.bkl-details').attr('data-bkl'),
            dataType:'json',
            success:function(data, textStatus, jqXHR){
                bklStatus.removeClass('spinner');
                bklStatus.removeClass('bel-laden01');
                if (typeof bklDetails.data('bkl-hover') === 'undefined') {
                    bklDetails.addClass('bkl-details-show');
                    bklDetails.html(data.data.join('<i class="bel-pfeil-l01"></i>'));
                } else {
                    bklDetails.addClass('bkl-details-hide');
                    bklDetails.html(data.data.join('<i class="bel-pfeil-l01"></i>'));
                    //bklLink.attr('data-uk-tooltip', 'true');
                    //bklLink.attr('title', data.data.join('<i class="bel-pfeil-l01"></i>'));

                    bklLink.qtip({
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

    (function ($, undefined) {
        "use strict";

        var searchLinkSpan = document.createElement('span');
        $(searchLinkSpan).html('<a href="" title="'+'" class="rvk-tree-search-link" target="_blank"><i class="bel-link01"></i></a>');

        $.jstree.plugins.rvkSearchLink = function (options, parent) {
            this.bind = function () {
                parent.bind.call(this);
                this.element
                    .on('click.jstree', '.jstree-rvkSearchLink', $.proxy(function (e) {
                        console.log('bind-click');
                    }, this));
            };
            this.teardown = function () {
                this.element.find('.jstree-rvkSearchLink').remove();
                parent.teardown.call(this);
            };
            this.redraw_node = function(obj, deep, callback, force_draw) {
                var rvkId = obj;
                rvkId = rvkId.replace(/_/g, '+');
                obj = parent.redraw_node.call(this, obj, deep, callback, force_draw);
                if(obj) {
                    var tempSearchLinkSpan = searchLinkSpan.cloneNode(true);
                    $(tempSearchLinkSpan).find('a').attr('href', '/vufind/Search/Results?lookfor="'+rvkId+' rvk"');
                    obj.insertBefore(tempSearchLinkSpan, obj.childNodes[2]);
                }
                return obj;
            };
        };
    })(jQuery);

    jQuery('.rvk-tree').each(function(event){
        $(this).jstree({
            'core' : {
                'data' : function (node, callback) {
                    $.ajax({
                        url : '/vufind/AJAX/JSON?method=getRVKTree&rvk='+encodeURIComponent(node.id),
                        dataType:'json',
                        success : function(data) {
                            callback(data.data);
                        }
                    });
                }
            },
            'plugins' : ['rvkSearchLink']
        });

        $(this).on('click', '.jstree-anchor', function (e) {
            $(this).jstree(true).toggle_node(e.target);
        });
    });

});