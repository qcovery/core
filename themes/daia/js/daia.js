$(document).ready(function() {
	$('.daia-availability').each(function(){
		var $this = $(this);
        $.ajax({
			url:'/vufind/PAIA/availability?ppn='+jQuery(this).attr('data-ppn')+'&daia='+jQuery(this).data('daia')+'&openurl='+jQuery(this).data('openurl')+'&marcField951aValue='+jQuery(this).attr('data-marcField951aValue')+'&requesturi='+jQuery(this).data('requesturi')+'&list='+jQuery(this).data('list')+'&usedaia='+jQuery(this).data('usedaia')+'&format='+jQuery(this).data('format'),
			success:function(data) {
				$this.html(jQuery(data).find('#paia-availability'));
			}
		})
    });
 });