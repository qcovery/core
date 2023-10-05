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

function setupAutocomplete() {
    // If .autocomplete class is missing, autocomplete is disabled and we should bail out.
    var searchbox = $('#searchForm_lookfor.autocomplete');
    if (searchbox.length < 1) {
        return;
    }
    // Search autocomplete
    searchbox.autocomplete({
        rtl: $(document.body).hasClass("rtl"),
        maxResults: 10,
        loadingString: VuFind.translate('loading') + '...',
        handler: function vufindACHandler(input, cb) {
            var query = input.val();
            var searcher = extractClassParams(input);
            var hiddenFilters = [];
            $('#searchForm').find('input[name="hiddenFilters[]"]').each(function hiddenFiltersEach() {
                hiddenFilters.push($(this).val());
            });
            $.fn.autocomplete.ajax({
                url: VuFind.path + '/AJAX/JSON',
                data: {
                    q: query,
                    method: 'getACSuggestions',
                    searcher: searcher.searcher,
                    type: searcher.type ? searcher.type : $('#searchForm_type').val(),
                    hiddenFilters: hiddenFilters
                },
                dataType: 'json',
                success: function autocompleteJSON(json) {
                    if (json.data.suggestions.length > 0) {
                        var datums = [];
                        for (var j = 0; j < json.data.suggestions.length; j++) {
                            datums.push(json.data.suggestions[j]);
                        }
                        cb(datums);
                    } else {
                        cb([]);
                    }
                }
            });
        }
    });
    // Update autocomplete on type change
    $('#searchForm_type').change(function searchTypeChange() {
        searchbox.autocomplete().clearCache();
    });
}
