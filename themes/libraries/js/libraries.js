/**
 * Javascript for Libraries Module
 *
 * Copyright (C) Staats- und Universitätsbibliothek 2017.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2 Templates
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/subhh/beluga
 */
jQuery(document).ready(function() {

    function formatNumber(number) {
        number = number.toString();
        var len = number.length;
        var thousands = number.substring(len-3,len);
        var millions = number.substring(len-6,len-3);
        var billions = number.substring(len-9,len-6);
        var formattedNumber = '';
        if (billions != '') {
            formattedNumber += billions + '.';
        }
        if (millions != '') {
            formattedNumber += millions + '.';
        }
        if (thousands != '') {
            formattedNumber += thousands;
        }
        return formattedNumber;
    }

    function resolveBackend(searchClass, basic) {
        searchClass = searchClass.toLowerCase();
        if (searchClass == 'solr') {
            return (basic) ? '/Search/Results' : '/Search/Advanced';
        } else if (searchClass == 'primo') {
            return (basic) ? '/Primo/Search' : '/Primo/Advanced';
        } else if (searchClass == 'findex') {
            return (basic) ? '/Findex/Search' : '/Findex/Advanced';
        } else {
            return '';
        }
    }

    function getLibraryList() {
        var queryString = jQuery('div#library-list').attr('data-query');
        var searchClass = jQuery('div#library-list').attr('data-searchclass');
        var libraryTemplate = jQuery('div#library-list').html();
        var locationTemplate = jQuery('div#location-list').html();
        jQuery('div#library-list').html('');
        jQuery('div#location-list').html('');
        if (typeof(queryString) != "undefined" && typeof(searchClass) != "undefined") {
            queryString = queryString.substring(1, queryString.length - 1);
            var backendUrl = resolveBackend(searchClass, true);
            jQuery.ajax({
                url:'/vufind/LibrariesAjax/JSON?method=getLibraryFacets',
                dataType:'json',
                data:{querystring:queryString, searchclass:searchClass},
                success:function(data, textStatus) {
                    var newQueryString;
                    jQuery.each(data.data.libraryData, function(thisLibraryCode, libraryData) {
                        newQueryString = queryString.replace(/&library=.+$/, '');
                        jQuery('div#library-list').append(libraryTemplate);
                        jQuery('div#library-list .library-item-count').last().before(libraryData.fullname);
                        jQuery('div#library-list .library-item-count').last().html(formatNumber(libraryData.count));
                        if (searchClass == "Primo" && libraryData.primo == undefined) {
                            jQuery('div#library-list .library-item').last().attr('href', '#');
                        } else {
                            jQuery('div#library-list .library-item').last().attr('href', '/vufind' + backendUrl + newQueryString + '&library=' + thisLibraryCode);
                        }
                    });
                    jQuery('div#library-list-title').attr('style', 'block');
                    if (typeof(data.data.locationFilter) == "object") {
                        if (data.data.locationFilter.value != '' && data.data.locationFilter.value != null) {
                            var queryStringItems = queryString.split('&');
                            newQueryString = '';
                            for (i = 0; i < queryStringItems.length; i++) {
                                if (queryStringItems[i].indexOf('filter') == -1 || queryStringItems[i].indexOf(data.data.locationFilter.field) == -1) {
                                    newQueryString += queryStringItems[i] + '&';
                                }
                            }
                            jQuery('div#location-list').append(locationTemplate);
                            jQuery('div#location-list .location-item').last().html(data.data.locationFilter.value);
                            jQuery('div#location-list .location-item').last().attr('href', '/vufind' + backendUrl + newQueryString);
                            jQuery('div#location-list-title').attr('class', 'list-group-item title');
                            jQuery('div#side-collapse-location').attr('class', 'collapse in');
                            jQuery('div#side-collapse-location').attr('aria-expanded', 'true');
                            jQuery('div#location-list-title').attr('style', 'block');
                        } else if (data.data.locationFacets != '') {
                            jQuery.each(data.data.locationFacets, function(locationName, locationData) {
                                var filter = encodeURI(locationData.filter);
                                jQuery('div#location-list').append(locationTemplate);
                                jQuery('div#location-list .location-item-count').last().before(locationName);
                                jQuery('div#location-list .location-item-count').last().html(formatNumber(locationData.count));
                                jQuery('div#location-list .location-item').last().attr('href', '/vufind' + backendUrl + queryString + '&filter[]=' + filter);
                            });
                            jQuery('div#location-list-title').attr('style', 'block');
                        } else {
                            jQuery('div#side-panel-location').remove();
                        }
                    }
                }
            });
        }
    }

    getLibraryList();

});

