jQuery(document).ready(function() {
    var recordId;
    var pathParts = window.location.href.split('/');
    var recordIndex = pathParts.length - 1;
    if (pathParts[recordIndex] == 'Record') {
      recordId = pathParts[(recordIndex + 1)];
    } else {
      recordIndex--;
      if (pathParts[recordIndex] == 'Record') {
        recordId = pathParts[(recordIndex + 1)];
      } else {
        recordId = 0;
      }
    }

    jQuery.ajax({
        url:'/vufind/AJAX/JSON?method=getDependentWorks',
        dataType:'json',
        data:{ppn:recordId},
        success:function(data, textStatus) {
            if (data.data.length > 0) {
                var visibleItems = (data.data.length < 3) ? data.data.length : 3;
                for (var i = 0; i < visibleItems; i++) {
                    var title = data.data[i]['title'] + ' (' + data.data[i]['publishDate'] + ')';
                    var href = '<a href="/vufind/Record/' + data.data[i]['id'] + '" target="_blank">' + title + '</a>';
                    jQuery('ul#DependentWorks').append('<li>' + href + '</li>');                
                }
                if (data.data.length > visibleItems) {
                    jQuery('p#ToggleDependentWorksMore').attr('style', 'display:block');
                    for (var i = visibleItems; i < data.data.length; i++) {
                        var title = data.data[i]['title'] + ' (' + data.data[i]['publishDate'] + ')';
                        var href = '<a href="/vufind/Record/' + data.data[i]['id'] + '" target="_blank">' + title + '</a>';
                        jQuery('ul#DependentWorksHidden').append('<li>' + href + '</li>');                
                    }
                }
                jQuery('div#DependentWorks').attr('style', 'display:block');
            }
        }
    });
});

function toggleDependentWorks() {
    var dependentWorksLayer = document.getElementById('DependentWorksHidden');
    var toggleMore = document.getElementById('ToggleDependentWorksMore');
    var toggleLess = document.getElementById('ToggleDependentWorksLess');
    if (dependentWorksLayer.style.display == 'none') {
        dependentWorksLayer.style.display = 'block';
        toggleMore.style.display = 'none';
        toggleLess.style.display = 'block';
    } else {
        dependentWorksLayer.style.display = 'none';
        toggleMore.style.display = 'block';
        toggleLess.style.display = 'none';
    }
}

