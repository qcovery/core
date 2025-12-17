/* global VuFind */

$(document).ready(function triggerPrint() {
    if (!VuFind.isPrinting()) {
        return;
    }

    function defer(fn) {
        setTimeout(fn, 10);
    }

    window.addEventListener(
        "afterprint",
        function doAfterPrint() {
            // Return to previous page after a minimal timeout. This is
            // done to avoid problems with some browsers, which fire the
            // afterprint event while the print dialog is still open.
            defer(function doGoBack() { history.back(); });
        },
        { once: true }
    );

    // Trigger print after a minimal timeout. This is done to avoid
    // problems with some browsers, which might not fully update
    // ajax loaded page content before showing the print dialog.
    defer(function doPrint() { window.print(); });
});