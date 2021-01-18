<?php
/**
 * Table Definition for user_list
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  Db_Table
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace Delivery\Db\Table;
use VuFind\Db\Table\Gateway;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\PluginManager;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Expression;
use VuFind\Exception\LoginRequired as LoginRequiredException;

/**
 * Table Definition for user_delivery
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Delivery extends Gateway
{
    /**
     * Constructor
     *
     * @param Adapter       $adapter Database adapter
     * @param PluginManager $tm      Table manager
     * @param array         $cfg     Laminas Framework configuration
     * @param RowGateway    $rowObj  Row prototype object (null for default)
     * @param string        $table   Name of database table to interface with
     */
    public function __construct(Adapter $adapter, PluginManager $tm, $cfg,
        RowGateway $rowObj, $table = 'delivery'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    public function getDeliveryList($user_delivery_id)
    {
        $callback = function ($select) use ($user_delivery_id) {
            $select->columns(['*']);
            $select->join(
                ['d' => 'delivery'],
                'resource.id = d.resource_id',
                ['*']
            );
            $select->where->equalTo('d.user_delivery_id', $user_delivery_id);
            $select->order('d.order_id DESC');
        };
        $resource = $this->getDbTable('Resource');
        return $resource->select($callback)->toArray();
    }

    public function getCompleteList()
    {
        $callback = function ($select) use ($user_delivery_id) {
            $select->columns(['*']);
            $select->join(
                ['d' => 'delivery'],
                'resource.id = d.resource_id',
                ['*']
            );
            //$select->where->equalTo('d.user_delivery_id', $user_delivery_id);
        };
        $resource = $this->getDbTable('Resource');
        return $resource->select($callback)->toArray();
    }

    public function createRowForUserDeliveryId($user_delivery_id, $order_id, $data)
    {
        if (empty($data['record_id']) || empty($user_delivery_id)) {
            return false;
        }
        $resource = $this->getDbTable('Resource');
        $resourceRow = $resource->createRow();
        $resourceRow->record_id = $data['record_id'];
        $resourceRow->title = $data['title'] ?? '';
        $resourceRow->author = $data['author'] ?? '';
        if (!empty($data['year'])) {
            $resourceRow->year = intval($data['year']);
        }
        $resourceRow->source = $data['source'] ?? 'Solr';
        $resourceRow->save();
        $resource_id = $resourceRow->id;

        $date = new \DateTime();
        $row = $this->createRow();
        $row->user_delivery_id = $user_delivery_id;
        $row->resource_id = $resource_id;
        if (isset($order_id)) {
            $row->order_id = $order_id;
        }
        $row->ordered = $date->format('Y-m-d H:i:s');
        $row->save();
        return $row->id;
    }
}
