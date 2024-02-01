<?php
/**
 * GetRecordCover AJAX handler.
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
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace UISettings\AjaxHandler;

use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\AjaxHandler\AbstractBase;
use VuFind\AjaxHandler\AjaxHandlerInterface;
use VuFind\Cookie\CookieManager;

/**
 * GetRecordCover AJAX handler.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class SetUISettings extends AbstractBase implements AjaxHandlerInterface
{
    protected $cookieManager;

    public function __construct(CookieManager $cookieManager) {
        $this->cookieManager = $cookieManager;
    }

    /**
     * Handle request
     *
     * @param Params $params Request parameters
     *
     * @return array
     * @throws \Exception
     */
    public function handleRequest(Params $params)
    {
        $response = [];

        $contrast = $params->fromQuery('ui_settings_contrast');
        if ($contrast) {
            if ($contrast != 'normal') {
                $this->cookieManager->set('ui_settings_contrast', $contrast);
            } else {
                $this->cookieManager->clear('ui_settings_contrast');
            }
        }

        return $this->formatResponse($response);
    }
}
