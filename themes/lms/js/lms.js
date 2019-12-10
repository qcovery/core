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
});