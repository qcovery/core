<?php
/**
 * Libraries Module
 *
 * PHP version 5
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
 * @category VuFind2
 * @package  Libraries
 * @author   Hajo Seng <hajo.seng@sub.uni-hamburg.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://github.com/subhh/beluga
 */
namespace DependentWorks;
use VuFind\Search\QueryAdapter;
use VuFindSearch\Query\Query;
use VuFindSearch\ParamBag;
use Zend\ServiceManager\ServiceLocatorInterface;

class DependentWorks
{

    protected  $serviceLocator;
    protected $requestObject;

    public function __construct(ServiceLocatorInterface $serviceLocator, $requestObject)
    {
        $this->serviceLocator = $serviceLocator;
        $this->requestObject = $requestObject;
    }

    public function getDependentWorks($id, $searchClassId = 'Solr')
    {
        $params = new ParamBag();
        $params->add('hl', 'false');
        $params->add('fl', 'id');
        $params->add('fl', 'title');
        $params->add('fl', 'series');
        $params->add('fl', 'series2');
        $params->add('fl', 'publishDate');
        $params->add('fl', 'format');
        $params->add('sort', 'publishDateSort DESC');
        $requestObject = $this->requestObject;
        $requestObject->set('lookfor0', array('hierarchy_top_id:' . $id));

        $query = QueryAdapter::fromRequest($requestObject, 'hierarchy_top_id');
        $searchService = $this->serviceLocator->get('VuFind\Search');
        $result = $searchService->search($searchClassId, $query, 0, 100, $params);
        $records = $result->getRecords();

        $dependentWorksData = [];
        foreach ($records as $record) {
            $dependentWorksData[] = $record->getDependentWorksData();
        }
        return $dependentWorksData;
    }
}

?>

