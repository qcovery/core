<?php
/**
 * "Get Item Status" AJAX handler
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
 * @author   Chris Delis <cedelis@uillinois.edu>
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace RVK\AjaxHandler;

use Zend\Mvc\Controller\Plugin\Params;
use VuFind\AjaxHandler\AbstractBase;

/**
 * "Get Item Status" AJAX handler
 *
 * This is responsible for printing the holdings information for a
 * collection of records in JSON format.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Chris Delis <cedelis@uillinois.edu>
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetRVKTree extends \VuFind\AjaxHandler\AbstractBase
{
    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $id = $_GET['rvk'];
        if ($id == '#') {
            $id = '';
        }

        $rvk = json_decode(file_get_contents('https://rvk.uni-regensburg.de/api/json/children/'.urlencode($id)));

        return $this->formatResponse($this->getRvkNodes($rvk));
    }

    private function getRvkNodes($rvk) {
        /* $result = [
            [
                'id' => 'node_2',
				'text' => 'Root node with options',
				//'state' => [ 'opened' => true, 'selected' => true ],
				//'children' => [ [ 'text' => 'Child 1' ], 'Child 2'],
			],
			]; */

        $result = [];

        if (isset($rvk->node->children->node)) {
            foreach ($rvk->node->children->node as $node) {
                $result[] = [
                    'id' => $node->notation,
                    'text' => $node->benennung,
                    'children' => ($node->has_children == 'yes' ? true : false),
                ];
            }
        }

        return $result;
    }
}
