jQuery(document).ready(function() {
    jQuery('#storage-info-geo-map').each(function(){
        var $this = jQuery(this);
        var map = L.map('storage-info-geo-map').setView({lon: $this.data('geo-long'), lat: $this.data('geo-lat')}, 17);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap contributors</a>'
        }).addTo(map);
        L.marker({lon: $this.data('geo-long'), lat: $this.data('geo-lat')}).bindPopup($this.data('geo-title')).addTo(map);
    });
});