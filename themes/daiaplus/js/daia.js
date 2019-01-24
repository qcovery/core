$(document).ready(function() {
	$('.electronic-availability').each(function(){
		var $this = $(this);
        $.ajax({
			url:'/vufind/PAIA/electronicavailability?ppn='+jQuery(this).attr('data-ppn')+'&site='+jQuery(this).attr('data-site')+'&openurl='+jQuery(this).data('openurl')+'&url-access='+jQuery(this).attr('data-url-access')+'&url_access_level='+jQuery(this).attr('data-url_access_level')+'&first-matching-issn='+jQuery(this).attr('data-first-matching-issn')+'&gvklink='+jQuery(this).attr('data-gvklink')+'&doi='+jQuery(this).attr('data-doi')+'&list='+jQuery(this).data('list')+'&mediatype='+jQuery(this).data('mediatype')+'&sfx='+jQuery(this).data('sfx'),
			success:function(data) {
				$this.html(jQuery(data).find('#electronic-availability'));
			}
		})
    });
 });