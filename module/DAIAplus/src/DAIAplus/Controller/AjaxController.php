<?php
/**
 * Ajax Controller Module
 *
 * PHP version 7
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
 * @package  Controller
 * @author   Chris Hallberg <challber@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
namespace DAIAplus\Controller;

use DAIAplus\AjaxHandler\PluginManager;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use Zend\Mvc\Controller\AbstractActionController;

/**
 * This controller handles global AJAX functionality
 *
 * @category VuFind
 * @package  Controller
 * @author   Chris Hallberg <challber@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:controllers Wiki
 */
class AjaxController extends \VuFind\Controller\AjaxController
{
    use \Vufind\Controller\AjaxResponseTrait;
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * Constructor
     *
     * @param PluginManager $am AJAX Handler Plugin Manager
     */
    public function __construct(PluginManager $am)
    {
        // Add notices to a key in the output
        set_error_handler([static::class, 'storeError']);
        $this->ajaxManager = $am;
    }
}
