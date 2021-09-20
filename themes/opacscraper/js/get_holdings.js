/*global Hunt, VuFind */

VuFind.register('holdings', function Holdings() {

  function checkHoldings(el) {
    var $item = $(el);
    var id = $item.find('.hiddenId').val();
    var codeString = $item.find('.hiddenCodeString').val();

    jQuery.ajax({
      url:'/vufind/AJAX/JSON?method=getHoldings',
      dataType:'json',
      async:false,
      data:{id: id, codeString: codeString},
      success: function(data, textStatus) {
        for (var i = 0; i < data.data.length; i++) {
          var lib = data.data[i];
          el.append('<span id="os-lib-' + lib.LibraryCode + '" class="os-lib" style="display:block; font-size:14pt; font-weight:bold; background-color: #FFF; margin:5px 0 0 0">' + lib.LibraryName + '</span>');
          //el.append('<p style="margin-top:0px">');
          el.append('<span id="os-item-' + lib.LibraryCode + '" class="os-item" style="display:block; background-color: #FFF"></span>');
          var itemEl = $('#os-item-' + lib.LibraryCode);
          for (var j = 0; j < lib.items.length; j++) {
            var item = lib.items[j];
            if (item.Signatur !== undefined) {
              for (var k = 0; k < item.Signatur.length; k++) {
                itemEl.append(formatData('Signatur', item.Signatur[k]));
              }
            }
            if (item.Standort !== undefined) {
              for (var k = 0; k < item.Standort.length; k++) {
                itemEl.append(formatData('Standort', item.Standort[k]));
              }
            }
            if (item.Ausleihstatus !== undefined) {
              for (var k = 0; k < item.Ausleihstatus.length; k++) {
                if (lib.OrderLink !== undefined) {
                  itemEl.append(formatData('Ausleihstatus', item.Ausleihstatus[k], lib.OrderLink));
                } else {
                  itemEl.append(formatData('Ausleihstatus', item.Ausleihstatus[k]));
                }
              }
            }
            if (item.Link !== undefined) {
              for (var k = 0; k < item.Link.length; k++) {
                itemEl.append(formatData('Link', item.Link[k]));
              }
            }
            if (item.Volltext !== undefined) {
              for (var k = 0; k < item.Volltext.length; k++) {
                itemEl.append(formatData('Volltext', item.Volltext[k]));
              }
            }
            if (item['Elektron. Referenz'] !== undefined) {
              for (var k = 0; k < item['Elektron. Referenz'].length; k++) {
                itemEl.append(formatData('Elektron. Referenz', item['Elektron. Referenz'][k]));
              }
            }
            if (item.Bestand !== undefined) {
              for (var k = 0; k < item.Bestand.length; k++) {
                itemEl.append(formatData('Bestand', item.Bestand[k]));
              }
            }
          }
        }
      }
    });
    _refreshOSToggles();
  }

  var actualVar = '';

  function formatData(dataVar, dataItem, orderLink) {
    if (dataVar == actualVar) {
      if (dataItem.target !== undefined) {
        if (orderLink !== undefined) {
          dataItem.target = orderLink;
        }
        return '<p style="padding:0 0 0 5px; margin:0 0 0 0"><a href="' + dataItem.target + '" target="_new">' + dataItem.text + '</a></p>';
      } else {
        return '<p style="padding:0 0 0 5px; margin:0 0 0 0">' + dataItem.text + '</p>';
      }
    } else {
      actualVar = dataVar;
      if (dataItem.target !== undefined) {
        if (orderLink !== undefined) {
          dataItem.target = orderLink;
        }
        return '<p style="padding:5px 0 0 0; margin:0 0 0 0"><b>' + dataVar + ':</b> <a href="' + dataItem.target + '" target="_new">' + dataItem.text + '</a></p>';
      } else {
        return '<p style="padding:5px 0 0 0;margin:0 0 0 0"><b>'+ dataVar + ':</b> ' + dataItem.text + '</p>';
      }
    }
  }

  function _refreshOSToggles() {
    var libContainer = $('.os-lib');
    $('.os-item').hide();
    if (libContainer.length > 0) {
      libContainer.each(function toggleLib() {
        var idString = $(this).attr('id');
        $(this).click(function showLibItems() {
          var idArray = idString.split('-');
          var id = idArray[2];
          $('#os-item-' + id).toggle();
        });
      });
    }
  }

  function init(_container) {
    var container = typeof _container === 'undefined'
      ? document.body
      : _container;
    var holdingsContainer = $(container).find('.holdingsContainer');
    for (var i = 0; i < holdingsContainer.length; i++) {
        checkHoldings($(holdingsContainer[i]));
    }
  }

  return { init: init };
});
