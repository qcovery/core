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
class UserDelivery extends Gateway
{
    protected $userId;

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
        RowGateway $rowObj, $table = 'user_delivery'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    public function get($user_id)
    {
        if (!$user_id) {
            throw new LoginRequiredException('Login required');
        }
        $result = $this->select(['user_id' => $user_id])->current();
        if (empty($result)) {
            return null;
        }
        return $result;
    }

    public function createRowForUserId($user_id, $email)
    {
        $row = $this->createRow();
        $row->user_id = $user_id;
        $row->delivery_email = $email;
        $row->save();
        return $row;
    }

    public function updateEmail($id, $email)
    {
        $deliveryUser = $this->select(['id' => $id])->current();
        $deliveryUser->delivery_email = $email;
        $this->email = $email;
        $deliveryUser->save();
        return $deliveryUser;
    }
}
