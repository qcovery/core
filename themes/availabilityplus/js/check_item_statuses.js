/*global Hunt, VuFind */
/*exported checkItemStatuses, itemStatusFail */

function displayItemStatus(results, item) {
    item.removeClass('js-item-pending');
    item.find('.ajax-availability').removeClass('ajax-availability hidden');
    item.find('.status').empty();
    let id = item.attr('data-id');
    $.each(results, function(index, result){
        if (typeof(result.error) != 'undefined'
            && result.error.length > 0
        ) {
            item.find('.status').append('error');
        } else {
            if (typeof(result.html) != 'undefined') {
                item.find('.status').append(result.html);
                if(itemStatusDebug != 1 ) item.find('.status').append("<span class='aplus_debug' style='display:none;'><pre>"+JSON.stringify(result, null, 2)+"</pre></span>");
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

let itemStatusIds = [];
let itemStatusEls = {};
let itemStatusTimer = null;
let itemStatusDelay = 200;
let itemStatusRunning = false;
let itemStatusList = false;
let itemStatusSource = '';
let itemStatusMediatype = '';
let itemLanguage = '';
let itemStatusDebug = '';

function runItemAjaxForQueue() {
    // Only run one item status AJAX request at a time:
    if (itemStatusRunning) {
        itemStatusTimer = setTimeout(runItemAjaxForQueue, itemStatusDelay);
        return;
    }
    itemStatusRunning = true;

    for (let i=0; i<itemStatusIds.length; i++) {
		let item = itemStatusEls[itemStatusIds[i]];
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
                for (let j = 0; j < response.data.statuses.length; j++) {
                    let status = response.data.statuses[j];
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
	let item = $(el);
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

function checkItemStatuses() {
    $('.availabilityItem').each(function() {
        if ($(this).offset().top < $(window).scrollTop() + $(window).height() && $(this).offset().top + $(this).height() > $(window).scrollTop() && $(this).find('.ajax-availability').length !== 0 ) {
            let id = $(this).attr('data-id');
            itemStatusSource = $(this).attr('data-src');
            itemStatusList = ($(this).attr('data-list') == 1);
            itemStatusMediatype = $(this).attr('data-mediatype');
            itemLanguage = $(this).attr('data-language');
            itemStatusDebug = $(this).attr('data-debug');
            itemQueueAjax(id, $(this));
        }
    });
}
$(document).ready(function() {
    checkItemStatuses();
    $(window).on('scroll resize', function() {
        checkItemStatuses();
    });
});

function initDaiaPlusOverlay () {
    $('.daiaplus-overlay').on('click', function(e){
        e.preventDefault();
        $('#modal .modal-body').html($('#'+$(this).data('daiaplus-overlay')).html());
        VuFind.modal('show');
    });
}
