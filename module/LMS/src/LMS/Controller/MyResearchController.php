<?php
/**
 * MyResearch Controller
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
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace LMS\Controller;

use VuFind\Exception\Auth as AuthException;
use VuFind\Exception\Forbidden as ForbiddenException;
use VuFind\Exception\ILS as ILSException;
use VuFind\Exception\ListPermission as ListPermissionException;
use VuFind\Exception\Mail as MailException;
use VuFind\Search\RecommendListener;
use Zend\Stdlib\Parameters;
use Zend\View\Model\ViewModel;

/**
 * Controller for the user account area.
 *
 * @category VuFind
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class MyResearchController extends \VuFind\Controller\MyResearchController
{
    /**
     * Send user's saved favorites from a particular list to the view
     *
     * @return mixed
     */
    public function mylistAction()
    {
        // Fail if lists are disabled:
        if (!$this->listsEnabled()) {
            throw new ForbiddenException('Lists disabled');
        }

        // Check for "delete item" request; parameter may be in GET or POST depending
        // on calling context.
        $deleteId = $this->params()->fromPost(
            'delete', $this->params()->fromQuery('delete')
        );
        if ($deleteId) {
            $deleteSource = $this->params()->fromPost(
                'source',
                $this->params()->fromQuery('source', DEFAULT_SEARCH_BACKEND)
            );
            // If the user already confirmed the operation, perform the delete now;
            // otherwise prompt for confirmation:
            $confirm = $this->params()->fromPost(
                'confirm', $this->params()->fromQuery('confirm')
            );
            if ($confirm) {
                $success = $this->performDeleteFavorite($deleteId, $deleteSource);
                if ($success !== true) {
                    return $success;
                }
            } else {
                return $this->confirmDeleteFavorite($deleteId, $deleteSource);
            }
        }

        // If we got this far, we just need to display the favorites:
        try {
            $runner = $this->serviceLocator->get('VuFind\Search\SearchRunner');

            // We want to merge together GET, POST and route parameters to
            // initialize our search object:
            $request = $this->getRequest()->getQuery()->toArray()
                + $this->getRequest()->getPost()->toArray()
                + ['id' => $this->params()->fromRoute('id')];

            // Set up listener for recommendations:
            $rManager = $this->serviceLocator
                ->get('VuFind\Recommend\PluginManager');
            $setupCallback = function ($runner, $params, $searchId) use ($rManager) {
                $listener = new RecommendListener($rManager, $searchId);
                $listener->setConfig(
                    $params->getOptions()->getRecommendationSettings()
                );
                $listener->attach($runner->getEventManager()->getSharedManager());
            };

            $results = $runner->run($request, 'Favorites', $setupCallback);

            $export = $this->params()->fromPost(
                'export', $this->params()->fromQuery('export')
            );

            if (!$export) {
                $showExportButton = false;
                if ($results->getListObject() && $results->getListObject()->isPublic()) {
                  if ($lmsConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/lms.ini'), true)) {
                      if ($this->getAuthManager()->isLoggedIn()) {
                          $patron = $this->catalogLogin();
                          if (is_array($patron)) {
                              foreach ($lmsConfig['lms-list-id-export']['allowed-user-types'] as $allowedUserType) {
                                  foreach ($patron['type'] as $type) {
                                      if ($type == $allowedUserType) {
                                          $showExportButton = true;
                                      }
                                  }
                              }
                          }
                      }
                  }
                }
                return $this->createViewModel(
                    ['params' => $results->getParams(), 'results' => $results, 'showExportButton' => $showExportButton]
                );
            } else {
                $response = $this->getResponse();

                $format = $this->params()->fromPost(
                    'format', $this->params()->fromQuery('format')
                );

                $result = '';
                if ($format == 'turbomarc') {
                    //$records = $this->getRecordLoader()->loadBatch($ids);
                    $turbomarcData = '';
                    foreach ($results->getResults() as $record) {
                        $temp = tmpfile();
                        fwrite($temp, $record->getXML('marc21'));
                        fseek($temp, 0);

                        $command = 'yaz-marcdump -i marcxml -o turbomarc ' . stream_get_meta_data($temp)['uri'];
                        $execResults = [];
                        exec($command, $execResults);

                        fclose($temp);

                        foreach ($execResults as $index => $execResult) {
                            if ($execResult == '</collection>' || $execResult == '<collection xmlns="http://www.indexdata.com/turbomarc">') {
                                unset($execResults[$index]);
                            }
                        }

                        if ($turbomarcData != '') {
                            $turbomarcData .= "\n";
                        }

                        $turbomarcData .= $this->addFormat(implode("\n", $execResults), $record);
                    }
                    $turbomarcData = str_ireplace('<?xml version="1.0"?>', '', $turbomarcData);
                    $result = '<?xml version="1.0"?>' . "\n" . '<collection xmlns="http://www.indexdata.com/turbomarc">' . "\n" . $turbomarcData . "\n" . '</collection>';
                } else if ($format == 'marc21') {
                    //$records = $this->getRecordLoader()->loadBatch($ids);
                    $marc21Data = [];
                    foreach ($results->getResults() as $record) {
                        $marc21Xml = $this->addFormat($record->getXML('marc21'), $record);
                        $marc21Data[] = str_ireplace('<?xml version="1.0"?>', '', $marc21Xml);
                    }
                    $result = '<?xml version="1.0"?>' . "\n" . '<collection>' . "\n" . implode("\n", $marc21Data) . "\n" . '</collection>';
                } else {
                    $ids = [];
                    foreach ($results->getResults() as $record) {
                        $ids[] = $record->getUniqueId();
                    }
                    $result = json_encode($ids);
                }

                $response->setContent($result);
                return $response;
            }
        } catch (ListPermissionException $e) {
            if (!$this->getUser()) {
                return $this->forceLogin();
            }
            throw $e;
        }
    }

    private function addFormat ($xml, $record) {
        $marcxml = simplexml_load_string($xml);
        $marcxml->addChild('format', implode(',', $record->getFormats()));
        return $marcxml->asXML();
    }

  /**
   * Display list of lists
   *
   * @return mixed
   */
  public function mylistsAction()
  {
    // Fail if lists are disabled:
    if (!$this->listsEnabled()) {
      throw new ForbiddenException('Lists disabled');
    }

    if (!$this->getAuthManager()->isLoggedIn()) {
      return $this->forceLogin();
    }

    return $this->createViewModel(
      []
    );
  }

  /**
   * Login Action
   *
   * @return mixed
   */
  public function loginAction()
  {
    $view = parent::loginAction();
    $view->showLmsHint = $this->params()->fromQuery('showLmsHint');
    return $view;
  }
}
