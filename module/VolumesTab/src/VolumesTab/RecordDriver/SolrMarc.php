<?php

namespace VolumesTab\RecordDriver;

use Solarium\Exception\HttpException;
use \RecordDriver\RecordDriver\SolrMarc as RecordDriverSolrMarc;

class SolrMarc extends RecordDriverSolrMarc
{
    function getVolumes () {
        $result = [];

        try {
            $volumesTabConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/VolumesTab.ini'), true);

            $adapter = new \Solarium\Core\Client\Adapter\Http();
            $config = [];
            $eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
            $client = new \Solarium\Client($adapter, $eventDispatcher, $config);
            $endpoint = new \VolumesTab\Solarium\Endpoint();
            $endpoint->setHost($volumesTabConfig['Index']['host']);
            $endpoint->setPort($volumesTabConfig['Index']['port']);
            $endpoint->setCore($volumesTabConfig['Index']['core']);
            $endpoint->setPath($volumesTabConfig['Index']['path']);

            $query = $client->createSelect();
            //$query->setQuery('(hierarchy_parent_id:' . $this->getUniqueID() . ')');
            $query->setQuery('(hierarchy_parent_id:'.$this->getUniqueID().' AND ppnlink:'.$this->getUniqueID().' AND NOT (format:Article OR format:"electronic Article")) AND (collection_details:"GBV_ILN_23" OR (collection:"NL" AND NOT collection_details:"GBV_NL_CAJ") OR collection:"DOAJ")');
            $query->setStart(0);
            if (isset($volumesTabConfig['Volumes']['rows'])
                && $volumesTabConfig['Volumes']['rows'] != ''
                && is_numeric($volumesTabConfig['Volumes']['rows'])) {
                $query->setRows($volumesTabConfig['Volumes']['rows']);
            } else {
                $query->setRows(100);
            }
            $query->addSort('publishDateSort', $query::SORT_ASC);
            $query->addSort('title_sort', $query::SORT_ASC);
            $query->addSort('author_sort', $query::SORT_ASC);
            $resultset = $client->select($query, $endpoint);

            foreach ($resultset as $doc) {
                if ($doc->id != $this->getUniqueID()) {
                    $marcRecordDriver = new SolrMarc($this->mainConfig);
                    $marcRecordDriver->setRawData($doc->getFields());
                    $result[] = $marcRecordDriver;
                }
            }
        } catch (HttpException $e) {
            error_log(print_r($e->getMessage(), true));
        }

        return $result;
    }

    public function getVolumeIssue() {
        $issue = $this->getFirstFieldValue('490', ['v']);
        if ($issue == '') {
            $issue = $this->getFirstFieldValue('264', ['c']);
        }
        return $issue;
    }

    public function getVolumeTitle() {
        $return = '';
        if ($this->getFirstFieldValue('245', array('a'))) $return = $this->getFirstFieldValue('245', array('a'));
        if ($this->getFirstFieldValue('245', array('a')) && $this->getFirstFieldValue('245', array('b')) && substr(trim($this->getFirstFieldValue('245', array('a'))), -1) !== ':' && substr(trim($this->getFirstFieldValue('245', array('b'))), 0, 1) !== ':') $return .= " :";
        if ($this->getFirstFieldValue('245', array('b'))) $return .= " ".$this->getFirstFieldValue('245', array('b'));
        if ($this->getFirstFieldValue('245', array('n')) || $this->getFirstFieldValue('245', array('p'))) $return .= " (";
        if ($this->getFirstFieldValue('245', array('n'))) $return .= $this->getFirstFieldValue('245', array('n'));
        if ($this->getFirstFieldValue('245', array('n')) && $this->getFirstFieldValue('245', array('p'))) $return .= ";";
        if ($this->getFirstFieldValue('245', array('p'))) $return .= " ".$this->getFirstFieldValue('245', array('p'));
        if ($this->getFirstFieldValue('245', array('n')) || $this->getFirstFieldValue('245', array('p'))) $return .= ")";
        if ($return !== '') return $return;
        if ($this->getFirstFieldValue('490', array('a'))) $return = $this->getFirstFieldValue('490', array('a'));
        if ($this->getFirstFieldValue('490', array('v'))) $return .= " (".$this->getFirstFieldValue('490', array('v')).")";
        if ($return !== '') return $return;
        if ($this->getFirstFieldValue('773', array('t'))) $return = $this->getFirstFieldValue('773', array('t'));
        return $return;
    }
}
