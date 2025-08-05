<?php
namespace AvailabilityPlus\Controller;

use VuFindSearch\ParamBag;

class AvailabilityPlusController extends \VuFind\Controller\AbstractBase
{
    /**
     * Record driver
     *
     * @var AbstractRecordDriver
     */
    protected $driver = null;

    /**
     * copy from \VuFind\Controller\AbstractRecord::loadRecord()
     * to load the record driver
     *
     * @param ParamBag $params Search backend parameters
     * @param bool     $force  Set to true to force a reload of the record, even if
     * already loaded (useful if loading a record using different parameters)
     * @param string   $source Source paramater because we can´t access $this->searchClassId
     *
     * @return AbstractRecordDriver
     */
    protected function loadRecordDriver(ParamBag $params = null, bool $force = false, $source = 'Solr') {
        // Only load the record if it has not already been loaded. Note that
        // when determining record ID, we check both the route match (the most
        // common scenario) and the GET parameters (a fallback used by some
        // legacy routes).
        if ($force || !is_object($this->driver)) {
            $recordLoader = $this->getRecordLoader();
            $cacheContext = $this->getRequest()->getQuery()->get('cacheContext');

            if (isset($cacheContext)) {
                $recordLoader->setCacheContext($cacheContext);
            }

            $this->driver = $recordLoader->load(
                $this->params()->fromRoute('id', $this->params()->fromQuery('id')),
                $source,
                false,
                $params
            );
        }

        return $this->driver;
    }

    /**
     * Display Feedback home form.
     *
     * @return \Laminas\View\Model\ViewModel
     */
    public function homeAction() {
        return $this->forwardTo('AvailabilityPlus', 'TestCases');
    }

    public function testcasesAction() {
        return $this->createViewModel();
    }

    public function debugAction() {
        $source = $this->params()->fromQuery('source') ?? 'Solr';
        $driver = $this->loadRecordDriver(null, false, $source);

        return $this->createViewModel(['driver' => $driver]);
    }
}
