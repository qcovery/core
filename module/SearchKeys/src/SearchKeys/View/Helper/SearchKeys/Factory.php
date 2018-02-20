<?php
/**

 */
namespace SearchKeys\View\Helper\SearchKeys;
use Zend\ServiceManager\ServiceManager;

class Factory
{
    /**
     * Construct the SearchBox helper.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SearchBox
     */
    public static function getSearchBox(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config');
        $mainConfig = $config->get('config');
        $searchboxConfig = $config->get('searchbox')->toArray();
        $searchkeyConfig = $config->get('searchkeys')->toArray();
        $includeAlphaOptions
            = isset($searchboxConfig['General']['includeAlphaBrowse'])
            && $searchboxConfig['General']['includeAlphaBrowse'];
        return new SearchBox(
            $sm->getServiceLocator()->get('VuFind\SearchOptionsPluginManager'),
            $searchboxConfig,
            $searchkeyConfig,
            isset($mainConfig->SearchPlaceholder)
                ? $mainConfig->SearchPlaceholder->toArray() : [],
            $includeAlphaOptions && isset($mainConfig->AlphaBrowse_Types)
                ? $mainConfig->AlphaBrowse_Types->toArray() : []
        );
    }
}
?>
