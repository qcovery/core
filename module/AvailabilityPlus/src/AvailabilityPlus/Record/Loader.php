<?php

namespace AvailabilityPlus\Record;

use VuFind\Exception\RecordMissing as RecordMissingException;
use VuFindSearch\ParamBag;

class Loader extends \VuFind\Record\Loader
{
    /**
     * Given an ID and record source, load the requested record object.
     *
     * @param string   $id              Record ID
     * @param string   $source          Record source
     * @param bool     $tolerateMissing Should we load a "Missing" placeholder
     * instead of throwing an exception if the record cannot be found?
     * @param ParamBag $params          Search backend parameters
     * @param bool     $loadAVP         to create new Record Driver
     * @param array    $solrData        solr data to create Record Driver
     *
     * @throws \Exception
     * @return \VuFind\RecordDriver\AbstractBase
     */
    public function load(
        $id,
        $source = DEFAULT_SEARCH_BACKEND,
        $tolerateMissing = false,
        ParamBag $params = null,
        $loadAVP = false,
        $solrData = null
    ) {
        /**
         * Create new Record Driver to prevent further solr queries
         */
        if ($loadAVP && !empty($solrData)) {
            $record = $this->recordFactory->get('SolrMarc');
            $record->setRawData($solrData);
            $record->setSourceIdentifier($source);
            return $record;
        }

        if (null !== $id && '' !== $id) {
            $results = [];
            if (null !== $this->recordCache
                && $this->recordCache->isPrimary($source)
            ) {
                $results = $this->recordCache->lookup($id, $source);
            }
            if (empty($results)) {
                try {
                    $results = $this->searchService->retrieve($source, $id, $params)
                        ->getRecords();
                } catch (BackendException $e) {
                    if (!$tolerateMissing) {
                        throw $e;
                    }
                }
            }
            if (empty($results) && null !== $this->recordCache
                && $this->recordCache->isFallback($source)
            ) {
                $results = $this->recordCache->lookup($id, $source);
            }

            if (!empty($results)) {
                return $results[0];
            }

            if ($this->fallbackLoader
                && $this->fallbackLoader->has($source)
            ) {
                try {
                    $fallbackRecords = $this->fallbackLoader->get($source)
                        ->load([$id]);
                } catch (BackendException $e) {
                    if (!$tolerateMissing) {
                        throw $e;
                    }
                    $fallbackRecords = [];
                }

                if (count($fallbackRecords) == 1) {
                    return $fallbackRecords[0];
                }
            }
        }
        if ($tolerateMissing) {
            $record = $this->recordFactory->get('Missing');
            $record->setRawData(['id' => $id]);
            $record->setSourceIdentifier($source);
            return $record;
        }
        throw new RecordMissingException(
            'Record ' . $source . ':' . $id . ' does not exist.'
        );
    }
}
