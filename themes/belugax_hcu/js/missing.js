// Sidebar
function moreFacets(id) {
    $('.' + id).removeClass('hidden');
    $('#more-' + id).addClass('hidden');
    return false;
}
function lessFacets(id) {
    $('.' + id).addClass('hidden');
    $('#more-' + id).removeClass('hidden');
    return false;
}
