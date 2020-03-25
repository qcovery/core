<?php
/**
 *
 */
namespace Notifications\View\Helper\Notifications;

use Zend\View\Helper\AbstractHelper;

class Notifier extends AbstractHelper
{

    protected $defaultHome;
    protected $notificationsConfig;

    /**
     *
     */
    public function __construct($config, $notificationsConfig)
    {
        $defaultModule = $config['Search']['defaultModule'] ?: 'Search';
        $defaultAction = $config['Search']['defaultAction'] ?: 'Home';
        $this->defaultHome = '/vufind/' . $defaultModule . '/' . $defaultAction;
        $this->notificationsConfig = $notificationsConfig;
    }

    /**
     *
     */
    public function getNotifications($path) {
        $path = rtrim($path, '/');
        $messages = [];
        foreach ($this->notificationsConfig as $event => $action) {
            foreach ($action['conditions'] as $condition) {
                list($type, $criterion) = explode(':', $condition);
                if ($type == 'iprange') {
                    $actualIp = $_SERVER['REMOTE_ADDR'];
                    if (strpos($criterion, '!') === 0) {
                        $criterion = substr($criterion, 1);
                        if (strpos($actualIp, $criterion) === 0) {
                            continue 2;
                        }
                    } elseif (strpos($actualIp, $criterion) !== 0) {
                        continue 2;
                    }
                } elseif ($type == 'path') {
                    if (strpos($criterion, '!') === 0) {
                        $criterion = substr($criterion, 1);
                        if ($criterion == 'home') {
                            if ($path == '/vufind' || $path == $this->defaultHome) {
                                 continue 2;
                            }
                        } elseif ($path == $criterion) {
                             continue 2;
                        }
                    } else {
                        if ($criterion == 'home') {
                            if ($path != '/vufind' && $path != $this->defaultHome) {
                                 continue 2;
                            }
                        } elseif ($path != $criterion) {
                             continue 2;
                        }
                    }
                }
            }
            $messages[] = $action['message'];
        }
        return $messages;
    }
}
