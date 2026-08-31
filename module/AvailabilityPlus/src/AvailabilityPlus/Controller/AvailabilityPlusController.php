<?php
namespace AvailabilityPlus\Controller;

use VuFindSearch\ParamBag;

class AvailabilityPlusController extends \VuFind\Controller\AbstractBase
{
    /**
     * Display the list of test cases.
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
        $driver = $this->getRecordLoader()->load(
            $this->params()->fromRoute('id', $this->params()->fromQuery('id')),
            $this->params()->fromQuery('source') ?? 'Solr'
        );

        return $this->createViewModel(['driver' => $driver]);
    }
}
