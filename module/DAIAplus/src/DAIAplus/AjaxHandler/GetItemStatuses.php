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
namespace DAIAplus\AjaxHandler;

use VuFind\Exception\ILS as ILSException;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\ILS\Connection;
use VuFind\ILS\Logic\Holds;
use VuFind\Session\Settings as SessionSettings;
use Zend\Config\Config;
use Zend\Mvc\Controller\Plugin\Params;
use Zend\View\Renderer\RendererInterface;

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
class GetItemStatuses extends \VuFind\AjaxHandler\GetItemStatuses
{
    /**
     * Constructor
     *
     * @param SessionSettings   $ss        Session settings
     * @param Config            $config    Top-level configuration
     * @param Connection        $ils       ILS connection
     * @param RendererInterface $renderer  View renderer
     * @param Holds             $holdLogic Holds logic
     */
    public function __construct(SessionSettings $ss, Config $config, Connection $ils,
                                RendererInterface $renderer, Holds $holdLogic
    ) {
        $this->sessionSettings = $ss;
        $this->config = $config;
        $this->ils = $ils;
        $this->renderer = $renderer;
        $this->holdLogic = $holdLogic;
    }
}
