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

function displayArticleStatus(results, $item) {
  $item.removeClass('js-item-pending');
  $item.find('.ajax-availability').removeClass('ajax-availability hidden');
//alert(result[0].level);
//alert(Object.keys(result[1]));
  $.each(results, function(index, result){
    if (typeof(result.error) != 'undefined'
      && result.error.length > 0
    ) {
      $item.find('.status').empty().append('error');
    } else {
alert(result.level);
      $item.find('.status').empty().append(result.level);
    }
  });
}

function displayItemStatus(result, $item) {
  $item.removeClass('js-item-pending');
  $item.find('.status').empty().append(result.availability_message);
  $item.find('.ajax-availability').removeClass('ajax-availability hidden');
  if (typeof(result.error) != 'undefined'
    && result.error.length > 0
  ) {
    // Only show error message if we also have a status indicator active:
    if ($item.find('.status').length > 0) {
      $item.find('.callnumAndLocation').empty().addClass('text-danger').append(result.error);
    } else {
      $item.find('.callnumAndLocation').addClass('hidden');
    }
    $item.find('.callnumber,.hideIfDetailed,.location').addClass('hidden');
  } else if (typeof(result.full_status) != 'undefined'
    && result.full_status.length > 0
    && $item.find('.callnumAndLocation').length > 0
  ) {
    // Full status mode is on -- display the HTML and hide extraneous junk:
    $item.find('.callnumAndLocation').empty().append(result.full_status);
    $item.find('.callnumber,.hideIfDetailed,.location,.status').addClass('hidden');
  } else if (typeof(result.missing_data) != 'undefined'
    && result.missing_data
  ) {
    // No data is available -- hide the entire status area:
    $item.find('.callnumAndLocation,.status').addClass('hidden');
  } else if (result.locationList) {
    // We have multiple locations -- build appropriate HTML and hide unwanted labels:
    $item.find('.callnumber,.hideIfDetailed,.location').addClass('hidden');
    var locationListHTML = "";
    for (var x = 0; x < result.locationList.length; x++) {
      locationListHTML += '<div class="groupLocation">';
      if (result.locationList[x].availability) {
        locationListHTML += '<span class="text-success"><i class="fa fa-ok" aria-hidden="true"></i> '
          + result.locationList[x].location + '</span> ';
      } else if (typeof(result.locationList[x].status_unknown) !== 'undefined'
          && result.locationList[x].status_unknown
      ) {
        if (result.locationList[x].location) {
          locationListHTML += '<span class="text-warning"><i class="fa fa-status-unknown" aria-hidden="true"></i> '
            + result.locationList[x].location + '</span> ';
        }
      } else {
        locationListHTML += '<span class="text-danger"><i class="fa fa-remove" aria-hidden="true"></i> '
          + result.locationList[x].location + '</span> ';
      }
      locationListHTML += '</div>';
      locationListHTML += '<div class="groupCallnumber">';
      locationListHTML += (result.locationList[x].callnumbers)
        ? linkCallnumbers(result.locationList[x].callnumbers, result.locationList[x].callnumber_handler) : '';
      locationListHTML += '</div>';
    }
    $item.find('.locationDetails').removeClass('hidden');
    $item.find('.locationDetails').html(locationListHTML);
  } else {
    // Default case -- load call number and location into appropriate containers:
    $item.find('.callnumber').empty().append(linkCallnumbers(result.callnumber, result.callnumber_handler) + '<br/>');
    $item.find('.location').empty().append(
      result.reserve === 'true'
        ? result.reserve_message
        : result.location
    );
  }
  if (typeof(result.daiaplus) != 'undefined'
    && result.daiaplus.length > 0) {
    $item.find('.callnumAndLocation').addClass('hidden');
    $item.find('.status').empty().append(result.daiaplus);
    $item.find('.status').removeClass('hidden');
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

function runItemAjaxForQueue() {
  // Only run one item status AJAX request at a time:
  if (itemStatusRunning) {
    itemStatusTimer = setTimeout(runItemAjaxForQueue, itemStatusDelay);
    return;
  }
  itemStatusRunning = true;
  if (itemStatusSource == 'Search2') {
    var method = 'getArticleStatuses';
  } else {
    var method = 'getItemStatuses';
  }

  $.ajax({
    url: VuFind.path + '/AJAX/JSON?method=' + method,
    dataType: 'json',
    method: 'get',
    data: {id:itemStatusIds, list:itemStatusList, source:itemStatusSource}
  })
    .done(function checkItemStatusDone(response) {
      for (var j = 0; j < response.data.statuses.length; j++) {
        var status = response.data.statuses[j];
        if (method == 'getItemStatuses') {
          displayItemStatus(status, itemStatusEls[status.id]);
        } else {
          displayArticleStatus(status, itemStatusEls[status.id]);
        }
        itemStatusIds.splice(itemStatusIds.indexOf(status.id), 1);
      }
      itemStatusRunning = false;
    })
    .fail(function checkItemStatusFail(response, textStatus) {
      itemStatusFail(response, textStatus);
      itemStatusRunning = false;
    });
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
  var $item = $(el);
  var id = $item.attr('data-id');
  itemStatusSource = $item.attr('data-src');
  itemStatusList = ($item.attr('data-list') == 1);
//alert(id + ' _ ' + source);
  itemQueueAjax(id + '', $item);
/*
  if ($item.find('.hiddenId').length === 0) {
    return false;
  }
  var id = $item.find('.hiddenId').val();
  itemQueueAjax(id + '', $item);
*/
}

var itemStatusObserver = null;
//Einzelansicht
function checkItemStatuses(_container) {
  var container = typeof _container === 'undefined'
    ? document.body
    : _container;

  var availabilityItems = $(container).find('.availabilityItem');
//alert(ajaxItems.attr('data-id'));
  for (var i = 0; i < availabilityItems.length; i++) {
//alert(ajaxItems[i].find('.hiddenId').val());
    //var id = $(ajaxItems[i]).find('.hiddenId').val();
    var id = $(availabilityItems[i]).attr('data-id');
    itemStatusSource = $(availabilityItems[i]).attr('data-src');
    itemStatusList = ($(availabilityItems[i]).attr('data-list') == 1);
//alert(id + ' - ' + source);
    //itemQueueAjax(id, $(ajaxItems[i]));
    itemQueueAjax(id, $(availabilityItem[i]));
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
        { enter: checkItemStatus }
      );
    }
  }
  function checkArticleStatusReady() {
    $('.availabilityItem').each(function(){
      var element = $(this);
      var id = $(this).attr('data-id');
      var list =  $(this).attr('data-list');
      var source = $(this).attr('data-src');
      if (source == 'Search2') {
        $.ajax({
          dataType:'json',
          method:'get',
          url:'/vufind/AJAX/JSON?method=getArticleStatuses',
          data:{id:id, list:list, source:source},
          success:function(data, textStatus) {
            element.html(data);
          }
        });
      }
    });
  }

  checkItemStatusReady();
  checkArticleStatusReady();
});
