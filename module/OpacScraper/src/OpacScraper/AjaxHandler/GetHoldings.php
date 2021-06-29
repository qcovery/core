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
namespace OpacScraper\AjaxHandler;

use Laminas\Config\Config;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\View\Renderer\RendererInterface;
use VuFind\Exception\ILS as ILSException;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\ILS\Logic\Holds;

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
class GetHoldings extends \VuFind\AjaxHandler\AbstractBase implements TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * View renderer
     *
     * @var RendererInterface
     */
    protected $renderer;

    /**
     * Holds logic
     *
     * @var Holds
     */
    protected $holdLogic;

    /**
     * Constructor
     *
     * @param SessionSettings   $ss        Session settings
     * @param Config            $config    Top-level configuration
     * @param Connection        $ils       ILS connection
     * @param RendererInterface $renderer  View renderer
     * @param Holds             $holdLogic Holds logic
     */
    public function __construct(RendererInterface $renderer, Holds $holdLogic
    ) {
        $this->renderer = $renderer;
        $this->holdLogic = $holdLogic;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $id = $params->fromPost('id', $params->fromQuery('id', ''));
        $libraryCodes = $params->fromPost('codes', $params->fromQuery('code', []));
        try {
            $holdings = $this->holdLogic->getHoldings($id, $libraryCodes);
        } catch (ILSException $e) {
            // If the ILS fails, send an error response instead of a fatal
            // error; we don't want to confuse the end user unnecessarily.
            error_log($e->getMessage());
            $holdings = [
                [
                    'id' => $id,
                    'error' => 'An error has occurred'
                ]
            ];
        }

        if (!is_array($holdings)) {
            // If getStatuses returned garbage, let's turn it into an empty array
            // to avoid triggering a notice in the foreach loop below.
            $holdings = [];
        }

        // Loop through all the status information that came back
        $holdStatements = [];

        foreach ($holdings['holdings'] as $holding) {
            foreach ($holding['items'] as $libraryItems) {
                if (isset($libraryItems['LibraryCode'])) {
                    $holdStatement = ['LibraryName' => $libraryItems['LibraryName']];
                    unset($libraryItem['LibraryCode'], $libraryItems['LibraryName']);
                    foreach ($libraryItems as $mediaItems) {
                        $mediaStatements = [];
                        foreach ($mediaItems as $mediaItem) {
                            foreach ($mediaItem as $key => $values) {
                                $statement = [];
                                foreach ($values as $value) {
                                    if (is_array($value)) {
                                        if (!empty($value['link'])) {
                                            $statement[] = ['target' => $value['link']['target'], 
                                                                  'text' => $value['link']['name']];
                                        }
                                    } else {
                                        $statement[] = ['text' => $value];
                                    }
                                }
                                $mediaStatement = [$key => $statement];
                            }
                            $mediaStatements = array_merge($mediaStatements, $mediaStatement);
                        }
                        if (!empty($mediaStatements)) {
                            $holdStatement['items'][] = $mediaStatements;
                        }
                    }
                    $holdStatements[] = $holdStatement;
                }
            }
        }
print_r($holdStatements);
        // Done
        return $this->formatResponse($holdStatements);
    }
}
