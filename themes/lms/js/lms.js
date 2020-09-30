$(document).ready(function() {
    $('#lms-toggle').on('click', function(e) {
        e.preventDefault();

        console.log($(this).data('display-height'));

        if ($('#lms-text').css('height') == $(this).data('display-height')) {
            $('#lms-text').css('height', 'auto');
            $(this).html($(this).data('display-less'));
        } else {
            $('#lms-text').css('height', $(this).data('display-height'));
            $(this).html($(this).data('display-more'));
        }
    });

    $('#copy-lms-id-button').on('click', function(e){
        e.preventDefault();
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val(window.location.href).select();
        document.execCommand("copy");
        $temp.remove();
        UIkit.modal.alert('<p class="uk-modal-body">'+lms_list_id_export_message+'</p>');
    });
});
