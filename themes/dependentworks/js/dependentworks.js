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
                if (data.data[0]['resultString'] !== undefined) {
                    var href = '<a href="/vufind/Search/Results?lookfor=hierarchy_top_id:'
                            + recordId + ' -id:'  + recordId + '">'
                            + data.data[0]['resultString'] + '</a>'
                    jQuery('ul#DependentWorks').append('<li>' + href + '</li>');
                } else {
                    var visibleItems = (data.data.length < 3) ? data.data.length : 3;
                    for (var i = 0; i < visibleItems; i++) {
                        var title = data.data[i]['title'];
                        var href = '<a href="/vufind/Record/' + data.data[i]['id'] + '">' + title + '</a>';
                        var item = data.data[i]['prefix'] + href;
                        jQuery('ul#DependentWorks').append('<li>' + item + '</li>');
                    }
                    if (data.data.length > visibleItems) {
                        jQuery('p#ToggleDependentWorksMore').attr('style', 'display:block');
                        for (var i = visibleItems; i < data.data.length; i++) {
                            var title = data.data[i]['title'];
                            var href = '<a href="/vufind/Record/' + data.data[i]['id'] + '">' + title + '</a>';
                            var item = data.data[i]['prefix'] + href;
                            jQuery('ul#DependentWorksHidden').append('<li>' + item + '</li>');
                        }
                    }
                }
                jQuery('div#DependentWorks').attr('style', 'display:block');
            }
        }
    });

    jQuery('#ToggleDependentWorksMore').on('click', function(event) {
        event.preventDefault();
        toggleDependentWorks();
    });

    jQuery('#ToggleDependentWorksLess').on('click', function(event) {
        event.preventDefault();
        toggleDependentWorks();
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

