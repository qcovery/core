/*global Hunt, VuFind */
/*exported checkItemStatuses, itemStatusFail */

function linkCallnumbers(callnumber, callnumber_handler) {
    if (callnumber_handler) {
        var cns = callnumber.split(',\t');
        for (var i = 0; i < cns.length; i++) {
            cns[i] = '<a href="' + VuFind.path + '/Alphabrowse/Home?source=' + encodeURI(callnumber_handler) + '&amp;from=' + encodeURI(cns[i]) + '">' + cns[i] + '</a>';
        }
        return cns.join(',\t');
    }
    return callnumber;
}

function displayItemStatus(results, item) {
    item.removeClass('js-item-pending');
    item.find('.ajax-availability').removeClass('ajax-availability hidden');
    item.find('.status').empty();
    var id = item.attr('data-id');
    $.each(results, function(index, result){
        if (typeof(result.error) != 'undefined'
            && result.error.length > 0
        ) {
            item.find('.status').append('error');
        } else {
            if (typeof(result.html) != 'undefined') {
                item.find('.status').append(result.html);
            }
        }
    });
    if (typeof $('#testcase_expected_result_html_' + id).html() != "undefined") {
        let result_expected = $('#testcase_expected_result_html_' + id).html().replace(/(\r\n|\n|\r)/gm, "").replace(/\>[\t ]+\</g, "><");
        let result_actual =  item.find('.status').html().replace(/(\r\n|\n|\r)/gm, "").replace(/\>[\t ]+\</g, "><");;
        if (result_actual === result_expected) {
            $('#testcase_status_' + id).append('<span class="testcase_status_green">&nbsp;</span>');
        } else {
            $('#testcase_status_' + id).append('<span class="testcase_status_red">&nbsp;</span>');
        }
    }
}

function itemStatusFail(response, textStatus) {
    if (textStatus === 'abort' || typeof response.responseJSON === 'undefined') {
        return;
    }
    // display the error message on each of the ajax status place holder
    $('.js-item-pending .callnumAndLocation').addClass('text-danger').empty().removeClass('hidden')
        .append(typeof response.responseJSON.data === 'string' ? response.responseJSON.data : VuFind.translate('error_occurred'));
}

var itemStatusIds = [];
var itemStatusEls = {};
var itemStatusTimer = null;
var itemStatusDelay = 200;
var itemStatusRunning = false;
var itemStatusList = false;
var itemStatusSource = '';
var itemStatusHideLink = '';
var itemStatusType = '';
var itemStatusMediatype = '';
var itemLanguage = '';
var itemStatusDebug = '';

function runItemAjaxForQueue() {
    // Only run one item status AJAX request at a time:
    if (itemStatusRunning) {
        itemStatusTimer = setTimeout(runItemAjaxForQueue, itemStatusDelay);
        return;
    }
    itemStatusRunning = true;

    for (var i=0; i<itemStatusIds.length; i++) {
		var item = itemStatusEls[itemStatusIds[i]];
		itemStatusSource = item.attr('data-src');
		itemStatusList = (item.attr('data-list') == 1);
		itemStatusMediatype = item.attr('data-mediatype');
        itemLanguage = item.attr('data-language');
		itemStatusDebug = item.attr('data-debug');
        $.ajax({
            url: VuFind.path + '/AJAX/JSON?method=getItemStatuses',
            dataType: 'json',
            method: 'get',
            data: {id:[itemStatusIds[i]], list:itemStatusList, source:itemStatusSource, mediatype:itemStatusMediatype, language:itemLanguage, debug:itemStatusDebug}
        })
            .done(function checkItemStatusDone(response) {
                for (var j = 0; j < response.data.statuses.length; j++) {
                    var status = response.data.statuses[j];
                    displayItemStatus(status, itemStatusEls[status.id]);
                    itemStatusIds.splice(itemStatusIds.indexOf(status.id), 1);
                }
                itemStatusRunning = false;
            })
            .fail(function checkItemStatusFail(response, textStatus) {
                itemStatusFail(response, textStatus);
                itemStatusRunning = false;
            });
    }
}

function itemQueueAjax(id, el) {
    if (el.hasClass('js-item-pending')) {
        return;
    }
    clearTimeout(itemStatusTimer);
    itemStatusIds.push(id);
    itemStatusEls[id] = el;
	var item = $(el);
	itemStatusSource = item.attr('data-src');
    itemStatusList = (item.attr('data-list') == 1);
    itemStatusMediatype = item.attr('data-mediatype');
    itemLanguage = item.attr('data-language');
    itemStatusDebug = item.attr('data-debug');
    itemStatusTimer = setTimeout(runItemAjaxForQueue, itemStatusDelay);
    el.addClass('js-item-pending').removeClass('hidden');
    el.find('.callnumAndLocation').removeClass('hidden');
    el.find('.callnumAndLocation .ajax-availability').removeClass('hidden');
    el.find('.status').removeClass('hidden');
}

//Listenansicht
function checkItemStatus(el) {
    var item = $(el);
    var id = item.attr('data-id');
    itemStatusSource = item.attr('data-src');
    itemStatusList = (item.attr('data-list') == 1);
    itemStatusMediatype = item.attr('data-mediatype');
    itemLanguage = item.attr('data-language');
    itemStatusDebug = item.attr('data-debug');
    itemQueueAjax(id + '', item);
}

var itemStatusObserver = null;

function checkItemStatuses() {
    $(window).on('scroll resize', function() {
        $('.availabilityItem').each(function() {
            if ($(this).offset().top < $(window).scrollTop() + $(window).height() && $(this).offset().top + $(this).height() > $(window).scrollTop()) {
                var id = $(this).attr('data-id');
                itemStatusSource = $(this).attr('data-src');
                itemStatusList = ($(this).attr('data-list') == 1);
                itemStatusMediatype = $(this).attr('data-mediatype');
                itemLanguage = $(this).attr('data-language');
                itemStatusDebug = $(this).attr('data-debug');
                itemQueueAjax(id, $(this));
            }
        });
    });
    // Stop looking for a scroll loader
    if (itemStatusObserver) {
        itemStatusObserver.disconnect();
    }
}
$(document).ready(function() {
    checkItemStatuses();
});

function initDaiaPlusOverlay () {
    $('.daiaplus-overlay').on('click', function(e){
        e.preventDefault();
        $('#modal .modal-body').html($('#'+$(this).data('daiaplus-overlay')).html());
        VuFind.modal('show');
    });
}
