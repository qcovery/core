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

    function getLibraryList() {
        var queryString = jQuery('div#library-list').attr('data-query');
        var searchLink = jQuery('div#library-list').attr('data-searchlink');
        var searchClass = jQuery('div#library-list').attr('data-searchclass');
        var libraryTemplate = jQuery('div#library-list').html();
        var locationTemplate = jQuery('div#location-list').html();
        jQuery('div#library-list').html('');
        jQuery('div#location-list').html('');
        jQuery('div#side-panel-library').attr('style', 'display:none');
        jQuery('div#side-panel-location').attr('style', 'display:none');
        if (typeof(queryString) != "undefined") {
            queryString = queryString.substring(1, queryString.length - 1);
            //queryString = queryString.replace(/filter%5B%5D=%23.+&/, '');
            queryString = queryString.replace(/filter%5B%5D=%23.+%22&/, '');
            jQuery.ajax({
                url:'/vufind/AJAX/JSON?method=getLibraries',
                dataType:'json',
                data:{querystring:queryString, source:searchClass},
                success:function(data, textStatus) {
                    var newQueryString;
                    var libraryCount = Object.keys(data.data.libraryData).length;
                    if (libraryCount > 1) {
                        jQuery.each(data.data.libraryData, function(thisLibraryCode, libraryData) {
                            newQueryString = decodeURIComponent(queryString).replace(/&amp;/g, '&');
                            newQueryString = newQueryString.replace(/&library=.+$/, '');
                            jQuery('div#library-list').append(libraryTemplate);
                            jQuery('div#library-list .library-item-text').last().html(libraryData.fullname);
                            jQuery('div#library-list .library-item-count').last().html(formatNumber(libraryData.count));
                            if (searchClass == "Primo" && libraryData.primo == undefined) {
                                jQuery('div#library-list .library-item').last().attr('href', '#');
                            } else {
                                jQuery('div#library-list .library-item').last().attr('href', searchLink + newQueryString + '&library=' + thisLibraryCode);
                            }
                            if(thisLibraryCode == data.data.selectedLibraryCode) {
                                jQuery('div#library-list .library-item').last().addClass('library-item-selected');
                            }
                        });
                        jQuery('div#side-panel-library').attr('style', 'display:block');
                    } else {
                        jQuery('div#side-panel-library').remove();
                    }
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
                            jQuery('div#side-panel-location').attr('style', 'display:block');
                        } else if (data.data.locationFacets != '') {
                            jQuery.each(data.data.locationFacets, function(locationName, locationData) {
                                var filter = encodeURI(locationData.filter);
                                jQuery('div#location-list').append(locationTemplate);
                                jQuery('div#location-list .location-item-count').last().html(formatNumber(locationData.count));
                                jQuery('div#location-list .location-item-text').last().html(locationName);
                                jQuery('div#location-list .location-item').last().attr('href', searchLink + queryString + '&filter[]=' + filter);
                            });
                            jQuery('div#side-panel-location').attr('style', 'display:block');
                        } else {
                            jQuery('div#side-panel-location').remove();
                        }
                    } else {
                        jQuery('div#side-panel-location').remove();
                    }
                }
            });
        }
    }

    getLibraryList();

});

