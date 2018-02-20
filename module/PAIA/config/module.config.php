<?php
namespace PAIA\Module\Configuration;

$config = array(
    'controllers' => array(
        'invokables' => array(
            'paia' => 'PAIA\Controller\PAIAController',
            'MyResearch' => 'PAIA\Controller\MyResearchController',
        ),
        'factories' => [
            'PAIA\Controller\MyResearchController' => 'PAIA\Controller\Factory::getMyResearchController',
        ],
        'aliases' => [
            'MyResearch' => 'PAIA\Controller\MyResearchController',
            'myresearch' => 'PAIA\Controller\MyResearchController',
        ],
    ),
    'service_manager' => array(
        'factories' => array(
            'VuFind\ILSHoldLogic' => 'PAIA\Service\Factory::getILSHoldLogic',
            'VuFind\ILSTitleHoldLogic' => 'PAIA\Service\Factory::getILSTitleHoldLogic',
            'VuFind\AuthManager' => 'PAIA\Auth\Factory::getManager',
        ),
    ),
    'vufind' => array(
        'plugin_managers' => array(
            'ils_driver' => array(
                'invokables' => array(
                    'paia' => 'PAIA\ILS\Driver\PAIA',
                ),
            ),
            'recorddriver' => array(
                'factories' => array(
                    'solrdefault' => 'PAIA\RecordDriver\Factory::getSolrDefault',
               ),
            ),
        ),
    ),
);

// Define record view routes -- route name => controller
$recordRoutes = array(
);

// Define static routes -- Controller/Action strings
$staticRoutes = array(
);

// Build record routes
foreach ($recordRoutes as $routeBase => $controller) {
    // catch-all "tab" route:
    $config['router']['routes'][$routeBase] = array(
        'type'    => 'Zend\Mvc\Router\Http\Segment',
        'options' => array(
            'route'    => '/' . $controller . '/[:id[/:tab]]',
            'constraints' => array(
                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
            ),
            'defaults' => array(
                'controller' => $controller,
                'action'     => 'Home',
            )
        )
    );
    // special non-tab actions that each need their own route:
    foreach ($nonTabRecordActions as $action) {
        $config['router']['routes'][$routeBase . '-' . strtolower($action)] = array(
            'type'    => 'Zend\Mvc\Router\Http\Segment',
            'options' => array(
                'route'    => '/' . $controller . '/[:id]/' . $action,
                'constraints' => array(
                    'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                ),
                'defaults' => array(
                    'controller' => $controller,
                    'action'     => $action,
                )
            )
        );
    }
}

// Build static routes
foreach ($staticRoutes as $route) {
    list($controller, $action) = explode('/', $route);
    $routeName = str_replace('/', '-', strtolower($route));
    $config['router']['routes'][$routeName] = array(
        'type' => 'Zend\Mvc\Router\Http\Literal',
        'options' => array(
            'route'    => '/' . $route,
            'defaults' => array(
                'controller' => $controller,
                'action'     => $action,
            )
        )
    );
}

return $config;
