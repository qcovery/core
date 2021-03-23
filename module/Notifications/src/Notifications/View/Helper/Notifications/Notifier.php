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
        $defaultModule = $config['Site']['defaultModule'] ?: 'Search';
        $defaultAction = $config['Site']['defaultAction'] ?: 'Home';
        $this->defaultHome = '/vufind/' . $defaultModule . '/' . $defaultAction;
        $this->notificationsConfig = $notificationsConfig;
    }

    /**
     *
     */
    public function getNotifications($path) {
        $path = rtrim($path, '/');
        $actualIp = $_SERVER['REMOTE_ADDR'];
        $messages = [];
        foreach ($this->notificationsConfig as $event => $action) {
            $ipOk = $pathOk = false;
            foreach ($action['conditions'] as $condition) {
                list($type, $criterion) = explode(':', $condition);
                if ($type == 'ip') {
                    if (strpos($criterion, '!') === 0) {
                        $criterion = substr($criterion, 1);
                        if (strpos($actualIp, $criterion) !== 0) {
                            $ipOk = true;
                        } else {
                            continue 2;
                        }
                    } elseif (strpos($actualIp, $criterion) === 0) {
                        $ipOk = true;
                    }
                } elseif ($type == 'path') {
                    if (strpos($criterion, '!') === 0) {
                        $criterion = substr($criterion, 1);
                        if ($criterion == 'home') {
                            if ($path != '/vufind' && $path != $this->defaultHome) {
                                 $pathOk = true;
                            } else {
                                 continue 2;
                            }
                        } elseif ($path != $criterion) {
                             $pathOk = true;
                        } else {
                            continue 2;
                        }
                    } else {
                        if ($criterion == 'home') {
                            if ($path == '/vufind' || $path == $this->defaultHome) {
                                $pathOk = true;
                            }
                        } elseif ($path == $criterion) {
                             $pathOk = true;
                        }
                    }
                }
            }
            if ($ipOk && $pathOk) {
                $messages[] = $action['message'];
            }
        }
        return $messages;
    }
}
