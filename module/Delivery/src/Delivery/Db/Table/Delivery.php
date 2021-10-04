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

    protected $resourceFields = ['record_id', 'title', 'author', 'year', 'source'];

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

    public function getCount($deliveryDomain, $notDelivered = true)
    {
        if ($notDelivered) {
            $callback = function ($select) use ($deliveryDomain) {
                $select->columns(['id' => new Expression('COUNT(id)')]);
                $select->where->isNull('delivered');
                $select->where->and->equalTo('delivery_domain', $deliveryDomain);
            };
            return $this->select($callback)->toArray();

        }
        return [];
    }

    public function getCompleteList($deliveryDomain, $notDelivered = true)
    {
        if ($notDelivered) {
            //$callback = function ($select) use ($user_delivery_id) {
            $callback = function ($select) use ($deliveryDomain) {
                $select->columns(['*']);
                $select->join(
                    ['d' => 'delivery'],
                    'resource.id = d.resource_id',
                    ['delivery' => 'id', 'order' => 'order_id', 'ordered' => 'ordered']
                );
                $select->join(
                    ['ud' => 'user_delivery'],
                    'd.user_delivery_id = ud.id',
                    ['email' => 'delivery_email']
                );
                $select->join(
                    ['u' => 'user'],
                    'ud.user_id = u.id',
                    ['user' => 'id', 'firstname' => 'firstname', 'lastname' => 'lastname', 'userid' => 'cat_id']
                );
                $select->where->isNull('d.delivered');
                $select->where->and->equalTo('d.delivery_domain', $deliveryDomain);
            };
        } else {
            $callback = function ($select) use ($user_delivery_id) {
                $select->columns(['*']);
                $select->join(
                    ['d' => 'delivery'],
                    'resource.id = d.resource_id',
                    ['*']
                );
            };
        }
        $resource = $this->getDbTable('Resource');
        return $resource->select($callback)->toArray();
    }

    public function createRowForUserDeliveryId($user_delivery_id, $order_id, $deliveryDomain, $data)
    {
        if (empty($user_delivery_id)) {
            return false;
        }
        if (empty($data['record_id'])) {
            $data['record_id'] = '-';
        }
        $resource = $this->getDbTable('Resource');
        $resourceRow = $resource->createRow();

        $extraMetadata = [];
        foreach ($data as $field => $value) {
            if (!empty($value)) {
                if (strpos($field, 'extra:') === 0) {
                    $extraMetadata[] = substr($field, 6) . ':' . $value;
                } elseif (in_array($field, $this->resourceFields)) {
                    $resourceRow->$field = $value;
                }
            }
        }
        if (!empty($extraMetadata)) {
            $resourceRow->extra_metadata = implode(';', $extraMetadata);
        }

        $resourceRow->save();
        $resource_id = $resourceRow->id;

        $date = new \DateTime();
        $row = $this->createRow();
        $row->user_delivery_id = $user_delivery_id;
        $row->resource_id = $resource_id;
        $row->delivery_domain = $deliveryDomain;
        if (isset($order_id)) {
            $row->order_id = $order_id;
            $row->delivered = $date->format('Y-m-d H:i:s');
        }
        $row->ordered = $date->format('Y-m-d H:i:s');
        $row->save();
        return $row->id;
    }

    public function upDateOrder($delivery_id, $order_id)
    {
        $delivery = $this->select(['id' => $delivery_id])->current();
        $delivery->order_id = $order_id;
        $date = new \DateTime();
        $delivery->delivered = $date->format('Y-m-d H:i:s');
        $delivery->save();
    }
}
