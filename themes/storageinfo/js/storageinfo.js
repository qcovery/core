jQuery(document).ready(function() {
    jQuery('#storage-info-geo-map').each(function(){
        storageMap(jQuery(this));
    });
});

function storageMap (element) {
    if (typeof element !== 'undefined') {
        var map = L.map(element.attr('id')).setView({lon: element.data('geo-long'), lat: element.data('geo-lat')}, 17);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap contributors</a>'
        }).addTo(map);
        L.marker({
            lon: element.data('geo-long'),
            lat: element.data('geo-lat')
        }).bindPopup(element.data('geo-title')).addTo(map);
    }
}