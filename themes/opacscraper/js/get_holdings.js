/*global Hunt, VuFind */

VuFind.register('holdings', function Holdings() {

  function checkHoldings(el) {
    var $item = $(el);
    var id = $item.find('.hiddenId').val();
    var codeString = $item.find('.hiddenCodeString').val();

    jQuery.ajax({
      url:'/vufind/AJAX/JSON?method=getHoldings',
      dataType:'json',
      data:{id: id, codeString: codeString},
      success: function(data, textStatus) {
        for (var i = 0; i < data.data.length) {

        }
      }
    });
  }

  function init(_container) {
    var container = typeof _container === 'undefined'
      ? document.body
      : _container;
alert('Holdings');
    var holdingsContainer = $(container).find('.holdingsContainer');
    for (var i = 0; i < holdingsContainer.length; i++) {
        checkHoldings($(holdingsContainer[i]));
    }
  }

  return { init: init };
});
