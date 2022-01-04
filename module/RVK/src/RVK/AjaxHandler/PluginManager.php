<?php
/**
 * AJAX handler plugin manager
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2018.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace RVK\AjaxHandler;

/**
 * AJAX handler plugin manager
 *
 * @category VuFind
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class PluginManager extends \VuFind\AjaxHandler\PluginManager
{
    /**
     * Default plugin aliases.
     *
     * @var array
     */
    protected $aliases = [
        'checkRequestIsValid' => 'VuFind\AjaxHandler\CheckRequestIsValid',
        'commentRecord' => 'VuFind\AjaxHandler\CommentRecord',
        'deleteRecordComment' => 'VuFind\AjaxHandler\DeleteRecordComment',
        'getACSuggestions' => 'VuFind\AjaxHandler\GetACSuggestions',
        'getArticleStatuses' => 'DAIAplus\AjaxHandler\GetArticleStatuses',
        'getFacetData' => 'VuFind\AjaxHandler\GetFacetData',
        'getDependentWorks' => 'DependentWorks\AjaxHandler\GetDependentWorks',
        'getIlsStatus' => 'VuFind\AjaxHandler\GetIlsStatus',
        'getItemStatuses' => 'DAIAplus\AjaxHandler\GetItemStatuses',
        'getItemStatusesAP' => 'AvailabilityPlus\AjaxHandler\GetItemStatusesAP',
        'getLibraries' => 'Libraries\AjaxHandler\GetLibraries',
        'getLibraryPickupLocations' =>
            'VuFind\AjaxHandler\GetLibraryPickupLocations',
        'getRecordCommentsAsHTML' => 'VuFind\AjaxHandler\GetRecordCommentsAsHTML',
        'getRecordDetails' => 'VuFind\AjaxHandler\GetRecordDetails',
        'getRecordTags' => 'VuFind\AjaxHandler\GetRecordTags',
        'getRequestGroupPickupLocations' =>
            'VuFind\AjaxHandler\GetRequestGroupPickupLocations',
        'getResolverLinks' => 'VuFind\AjaxHandler\GetResolverLinks',
        'getResultCount' => 'BelugaConfig\AjaxHandler\GetResultCount',
        'getSaveStatuses' => 'VuFind\AjaxHandler\GetSaveStatuses',
        'getRVKStatus' => 'RVK\AjaxHandler\GetRVKStatus',
        'getRVKTree' => 'RVK\AjaxHandler\GetRVKTree',
        'getBKLStatus' => 'RVK\AjaxHandler\GetBKLStatus',
        'getVisData' => 'VuFind\AjaxHandler\GetVisData',
        'keepAlive' => 'VuFind\AjaxHandler\KeepAlive',
        'recommend' => 'VuFind\AjaxHandler\Recommend',
        'relaisAvailability' => 'VuFind\AjaxHandler\RelaisAvailability',
        'relaisInfo' => 'VuFind\AjaxHandler\RelaisInfo',
        'relaisOrder' => 'VuFind\AjaxHandler\RelaisOrder',
        'systemStatus' => 'VuFind\AjaxHandler\SystemStatus',
        'tagRecord' => 'VuFind\AjaxHandler\TagRecord',
    ];

    /**
     * Default plugin factories.
     *
     * @var array
     */
    protected $factories = [
        'BelugaConfig\AjaxHandler\GetResultCount' =>
            'BelugaConfig\AjaxHandler\GetResultCountFactory',
        'DAIAplus\AjaxHandler\GetArticleStatuses' =>
            'DAIAplus\AjaxHandler\GetArticleStatusesFactory',
        'DAIAplus\AjaxHandler\GetItemStatuses' =>
            'DAIAplus\AjaxHandler\GetItemStatusesFactory',
        'Delivery\AjaxHandler\CheckAvailability' =>
            'Delivery\AjaxHandler\CheckAvailabilityFactory',
        'DependentWorks\AjaxHandler\GetDependentWorks' =>
            'DependentWorks\AjaxHandler\GetDependentWorksFactory',
        'Libraries\AjaxHandler\GetLibraries' =>
            'Libraries\AjaxHandler\GetLibrariesFactory',
        'RVK\AjaxHandler\GetRVKStatus' =>
            'RVK\AjaxHandler\GetRVKStatusFactory',
        'RVK\AjaxHandler\GetRVKTree' =>
            'RVK\AjaxHandler\GetRVKTreeFactory',
        'RVK\AjaxHandler\GetBKLStatus' =>
            'RVK\AjaxHandler\GetBKLStatusFactory',
        'VuFind\AjaxHandler\CheckRequestIsValid' =>
            'VuFind\AjaxHandler\AbstractIlsAndUserActionFactory',
        'VuFind\AjaxHandler\CommentRecord' =>
            'VuFind\AjaxHandler\CommentRecordFactory',
        'VuFind\AjaxHandler\DeleteRecordComment' =>
            'VuFind\AjaxHandler\DeleteRecordCommentFactory',
        'VuFind\AjaxHandler\GetACSuggestions' =>
            'VuFind\AjaxHandler\GetACSuggestionsFactory',
        'VuFind\AjaxHandler\GetFacetData' =>
            'VuFind\AjaxHandler\GetFacetDataFactory',
        'VuFind\AjaxHandler\GetIlsStatus' =>
            'VuFind\AjaxHandler\GetIlsStatusFactory',
        'VuFind\AjaxHandler\GetItemStatuses' =>
            'DAIAplus\AjaxHandler\GetItemStatusesFactory',
        'VuFind\AjaxHandler\GetLibraryPickupLocations' =>
            'VuFind\AjaxHandler\AbstractIlsAndUserActionFactory',
        'VuFind\AjaxHandler\GetRecordCommentsAsHTML' =>
            'VuFind\AjaxHandler\GetRecordCommentsAsHTMLFactory',
        'VuFind\AjaxHandler\GetRecordDetails' =>
            'VuFind\AjaxHandler\GetRecordDetailsFactory',
        'VuFind\AjaxHandler\GetRecordTags' =>
            'VuFind\AjaxHandler\GetRecordTagsFactory',
        'VuFind\AjaxHandler\GetRequestGroupPickupLocations' =>
            'VuFind\AjaxHandler\AbstractIlsAndUserActionFactory',
        'VuFind\AjaxHandler\GetResolverLinks' =>
            'VuFind\AjaxHandler\GetResolverLinksFactory',
        'VuFind\AjaxHandler\GetSaveStatuses' =>
            'VuFind\AjaxHandler\GetSaveStatusesFactory',
        'VuFind\AjaxHandler\GetVisData' => 'VuFind\AjaxHandler\GetVisDataFactory',
        'VuFind\AjaxHandler\KeepAlive' => 'VuFind\AjaxHandler\KeepAliveFactory',
        'VuFind\AjaxHandler\Recommend' => 'VuFind\AjaxHandler\RecommendFactory',
        'VuFind\AjaxHandler\RelaisAvailability' =>
            'VuFind\AjaxHandler\AbstractRelaisActionFactory',
        'VuFind\AjaxHandler\RelaisInfo' =>
            'VuFind\AjaxHandler\AbstractRelaisActionFactory',
        'VuFind\AjaxHandler\RelaisOrder' =>
            'VuFind\AjaxHandler\AbstractRelaisActionFactory',
        'VuFind\AjaxHandler\SystemStatus' =>
            'VuFind\AjaxHandler\SystemStatusFactory',
        'VuFind\AjaxHandler\TagRecord' => 'VuFind\AjaxHandler\TagRecordFactory',
    ];

    /**
     * Return the name of the base class or interface that plug-ins must conform
     * to.
     *
     * @return string
     */
    protected function getExpectedInterface()
    {
        return 'VuFind\AjaxHandler\AjaxHandlerInterface';
    }
}
