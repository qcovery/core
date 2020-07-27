jQuery(document).ready(function() {
    var recordId;
    var SearchClassId = 'Solr';
    var ResultPath = 'Search';
    var RecordPath = 'Record';
    var pathParts = window.location.pathname.split('/');
    var recordIndex = pathParts.length - 1;
    if (pathParts[recordIndex] == 'Record' || pathParts[recordIndex] == 'Search2Record') {
      recordId = pathParts[(recordIndex + 1)];
    } else {
      recordIndex--;
      if (pathParts[recordIndex] == 'Record' || pathParts[recordIndex] == 'Search2Record') {
        recordId = pathParts[(recordIndex + 1)];
      } else {
        recordId = 0;
      }
    }
    
    if (pathParts[recordIndex] == 'Search2Record') {
      var SearchClassId = 'Search2';
      var ResultPath = 'Search2';
      var RecordPath = 'Search2Record';
    }

    jQuery.ajax({
        url:'/vufind/AJAX/JSON?method=getDependentWorks',
        dataType:'json',
        data:{ppn:recordId, source:SearchClassId},
        success:function(data, textStatus) {
            if (data.data.length > 0) {
                if (data.data[0]['resultString'] !== undefined) {
                    var href = '<a href="/vufind/'+ResultPath+'/Results?lookfor='+data.data[0]['searchfield']+':'
                            + recordId + ' -id:'  + recordId;
                    if (data.data[0]['filter'].length > 0) {
                        href += '&filter[]='+data.data[0]['filter'];
                    }
                    href += '&sort=year">' + data.data[0]['resultString'] + '</a>';
                    jQuery('ul#DependentWorks').append('<li>' + href + '</li>');
                } else {
                    var visibleItems = (data.data.length < 3) ? data.data.length : 3;
                    for (var i = 0; i < visibleItems; i++) {
                        var title = data.data[i]['title'];
                        var href = '<a href="/vufind/'+RecordPath+'/' + data.data[i]['id'] + '">' + title + '</a>';
                        var item = data.data[i]['prefix'] + href;
                        jQuery('ul#DependentWorks').append('<li>' + item + '</li>');
                    }
                    if (data.data.length > visibleItems) {
                        jQuery('p#ToggleDependentWorksMore').attr('style', 'display:block');
                        for (var i = visibleItems; i < data.data.length; i++) {
                            var title = data.data[i]['title'];
                            var href = '<a href="/vufind/'+RecordPath+'/' + data.data[i]['id'] + '">' + title + '</a>';
                            var item = data.data[i]['prefix'] + href;
                            jQuery('ul#DependentWorksHidden').append('<li>' + item + '</li>');
                        }
                    }
                }
                jQuery('div#DependentWorks').attr('style', 'display:block');
                jQuery('div#DependentWorksRotator').attr('style', 'display:none');
            } else {
                jQuery('div#DependentWorks').attr('style', 'display:none');
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

