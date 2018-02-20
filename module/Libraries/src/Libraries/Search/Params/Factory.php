<?php
/**
 * Search Params Object Factory Class
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2017.
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
 * @package  Search
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */
namespace Libraries\Search\Params;
use Zend\ServiceManager\ServiceManager;

/**
 * Search Params Object Factory Class
 *
 * @category VuFind
 * @package  Search
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 *
 * @codeCoverageIgnore
 */
class Factory
{
    /**
     * Factory for Solr params object.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return \VuFind\Search\Solr\Params
     */
    public static function getSolr(ServiceManager $sm)
    {
        $factory = new PluginFactory();
        $helper = $sm->getServiceLocator()->get('VuFind\HierarchicalFacetHelper');
        $searchMemory = \VuFind\Service\Factory::getSearchMemory($sm->getServiceLocator());
        return $factory->createServiceWithName($sm, 'solr', 'Solr', [$searchMemory, $helper]);
    }

    /**
     * Factory for Primo params object.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return \VuFind\Search\Primo\Params
     */
    public static function getPrimo(ServiceManager $sm)
    {
        $factory = new PluginFactory();
        $searchMemory = \VuFind\Service\Factory::getSearchMemory($sm->getServiceLocator());
        return $factory->createServiceWithName($sm, 'primo', 'Primo', [$searchMemory]);
    }

    /**
     * Factory for Findex params object.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return \VuFind\Search\Findex\Params
     */
    public static function getFindex(ServiceManager $sm)
    {
        $factory = new PluginFactory();
        $searchMemory = \VuFind\Service\Factory::getSearchMemory($sm->getServiceLocator());
        return $factory->createServiceWithName($sm, 'findex', 'Findex', [$searchMemory]);
    }
}
