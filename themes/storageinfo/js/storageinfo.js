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

function initStorageInfo (parentElement, elementIdentifer) {
    jQuery(document).ready(function () {
        tippy.delegate(parentElement, {
            target: elementIdentifer,
            content: 'Loading...',
            placement: 'left',
            interactive: true,
            onCreate(instance) {
                // Setup our own custom state properties
                instance._isFetching = false;
                instance._src = null;
                instance._error = null;
            },
            onShow(instance) {
                if (instance._isFetching || instance._src || instance._error) {
                    return;
                }

                instance._isFetching = true;

                fetch(VuFind.path + '/StorageInfo/storage?uri=' + instance.reference.dataset.storageuri)
                    .then((response) => response.text())
                    .then((data) => {
                        instance.setContent(jQuery(data).find('.storage-info').get(0));
                        jQuery('#storage-info-geo-map').each(function(){
                            storageMap(jQuery(this));
                        });
                    })
                    .catch((error) => {
                        instance._error = error;
                        instance.setContent('Request failed. ${error}');
                    })
                    .finally(() => {
                        instance._isFetching = false;
                    });
            },
            onHidden(instance) {
                instance.setContent('Loading...');
                // Unset these properties so new network requests can be initiated
                instance._src = null;
                instance._error = null;
            },

        });
    });
}
