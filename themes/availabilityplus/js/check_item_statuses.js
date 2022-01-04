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

function runItemAjaxForQueue() {
    // Only run one item status AJAX request at a time:
    if (itemStatusRunning) {
        itemStatusTimer = setTimeout(runItemAjaxForQueue, itemStatusDelay);
        return;
    }
    itemStatusRunning = true;

    for (var i=0; i<itemStatusIds.length; i++) {
        $.ajax({
            url: VuFind.path + '/AJAX/JSON?method=getItemStatuses',
            dataType: 'json',
            method: 'get',
            data: {id:[itemStatusIds[i]], list:itemStatusList, source:itemStatusSource, mediatype:itemStatusMediatype}
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
    itemQueueAjax(id + '', item);
}

var itemStatusObserver = null;

function checkItemStatuses(_container) {
    var container = typeof _container === 'undefined'
        ? document.body
        : _container;

    var availabilityItems = $(container).find('.availabilityItem');
    for (var i = 0; i < availabilityItems.length; i++) {
        var id = $(availabilityItems[i]).attr('data-id');
        itemStatusSource = $(availabilityItems[i]).attr('data-src');
        itemStatusMediatype = $(availabilityItems[i]).attr('data-mediatype');
        itemQueueAjax(id, $(availabilityItems[i]));
    }
    // Stop looking for a scroll loader
    if (itemStatusObserver) {
        itemStatusObserver.disconnect();
    }
}
$(document).ready(function() {
    function checkItemStatusReady() {
        if (typeof Hunt === 'undefined') {
            checkItemStatuses();
        } else {
            itemStatusObserver = new Hunt(
                $('.availabilityItem').toArray(),
                {
                    enter: checkItemStatus,
                    offset: 100000
                }
            );
        }
    }
    checkItemStatusReady();
});

function initDaiaPlusOverlay () {
    $('.daiaplus-overlay').on('click', function(e){
        e.preventDefault();
        $('#modal .modal-body').html($('#'+$(this).data('daiaplus-overlay')).html());
        VuFind.modal('show');
    });
}
