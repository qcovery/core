<?php
/**
 *
 */
namespace RelevancePicker\View\Helper\RelevancePicker;

class ToolTip extends \Laminas\View\Helper\AbstractHelper
{
    protected $explainData = [];
    protected $scores = [];
    protected $clusterName = [
        'author' => 'Personendaten',
        'title-main' => 'Titeldaten' ,
        'title-alt' => 'Weitere Titel' ,
        'title-series' => 'Reihentitel' ,
        'topic' => 'Schlagwörter' ,
        'class' => 'Klassifikation' ,
        'fulltext' => 'Volltextdaten'
    ];
    protected $clusterConfig = [
        'author' => ['author', 'author2', 'author2_variant', 'author_corporate'],
        'title-main' => ['title', 'title_full', 'title_sub', 'title_auth', 'title_short'],
        'title-alt' => ['title_alt' ,'title_new', 'title_old'],
        'title-series' => ['series' ,'series2', 'journal'],
        'topic' => ['topic' ,'geographic'],
        'class' => ['class' ,'bklname'],
        'fulltext' => ['fulltext', 'contents', 'abstract'],
    ];
    protected $clusters;
    protected $searchTerm;
    protected $results;

    public function __construct() {
    }

    public function setData($explainData) {
        $this->explainData = explode("\n", $explainData);
        foreach ($this->clusterConfig as $cluster => $fields) {
            foreach ($fields as $field) {
                $this->clusters[$field] = $cluster;
            }
        }
        $this->parseValues();
    }

    public function getValues() {
        return $this->scores;
    }

    public function getData() {
        return $this->explainData;
    }

    public function getResults() {
        $allFieldTerms = $this->scores['clusters-terms']['all'];
        $allFieldPhrase = $this->scores['clusters-phrase']['all'];
        $allFields = $allFieldTerms + $allFieldPhrase;
        $allBoostings = $this->scores['bq']['all'];
        $all = $allFields + $allBoostings;

        foreach ($this->scores['bq'] as $key => $value) {
            $key = str_replace('bq-', '', $key);
            if ($key != 'all') {
                $term = $this->searchTerm[$key.$value];
                $this->results['boosting-'.$key][$term] = array('field' => $key, 'term' => $term, 'value' => $value, 'percent' => round( 100 * $value / $allBoostings));
                $this->results['all']['boosting-'.$key] = array('percent' => round(100 * $value / $all));
            }
        }

        if (isset($this->results['fields-terms'])) {
            foreach ($this->results['fields-terms'] as $term => $data) {
                $cluster = $this->getCluster($data['field']);
                $percent = round(100 * $data['value'] / $allFields);
                if (isset( $cluster ) && $percent > 0) {
                    $this->results['fields-terms'][$term]['cluster'] = $this->clusterName[$cluster];
                    $this->results['fields-terms'][$term]['percent'] = round(100 * $data['value'] / $allFields);
                } else {
                    unset($this->results['fields-terms'][$term]);
                }
            }
        }
        if (isset($this->results['fields-phrase'])) {
            foreach ($this->results['fields-phrase'] as $term => $data) {
                $cluster = $this->getCluster($data['field']);
                $percent = round(100 * $data['value'] / $allFields);
                if (isset($cluster) && $percent > 0) {
                    $this->results['fields-phrase'][$term]['cluster'] = $this->clusterName[$cluster];
                    $this->results['fields-phrase'][$term]['percent'] = round(100 * $data['value'] / $allFields);
                } else {
                    unset($this->results['fields-phrase'][$term]);
                }
            }
        }
        $this->results['all']['fields-terms'] = array('percent' => round(100 * $allFieldTerms / $all));
        $this->results['all']['fields-phrase'] = array('percent' => round(100 * $allFieldPhrase / $all));
        $this->results['all']['fields-all'] = array('percent' => round(100 * $allFields / $all));

        return $this->results;
    }


    protected function parseValues() {
        $tie = 0;
        $scores = $this->prepareScores();
        $this->scores = $scores;
        $this->results = [];
        $fieldFound = false;
        foreach ($this->explainData as $line) {
            $value = 0;
            if (preg_match( '/^(\s*)([0-9.]+) = (sum|max) (plus ([0-9.]+) times others )?of:$/', $line, $matches)) {
                $level = strlen( $matches[1] ) / 2;
                if ($matches[3] == 'max') {
                    if (isset( $matches[5])) {
                        $tie = $matches[5];
                    }
                    $this->calculateValues($scores, $tie);
                    $scores = $this->prepareScores();
                }
            } elseif (preg_match( '/^(\s*)([0-9.]+) = weight\((Synonym\()?([^:]+):(.+)\)? in [0-9]+\) \[SchemaSimilarity\], result of:/', $line, $matches)) {
                $statementLevel = strlen($matches[1]) / 2;
                $value = floatval($matches[2]);
                $field = $matches[4];
                $realField = str_replace('_unstemmed', '', $field);
                $string = $matches[5];
                $this->searchTerm[$realField.$value] = str_replace('"', '', $string);
                if ($statementLevel > $level || $statementLevel == 0) {
                    $fieldFound = true;
                    $suffix = (preg_match('/^"(.+)"$/' , $string)) ? 'phrase' : 'terms';
                    if (!isset( $scores['fields-'.$suffix][$field])) {
                        $scores['fields-'.$suffix][$field] = 0;
                    }
                    $scores['fields-'.$suffix][$field] += $value;
                } else {
                    if (!isset( $scores['bq-'.$field])) {
                        $scores['bq-'.$field] = [$string => 0];
                    }
                    $scores['bq-'.$field][$string] += $value;
                }
            } elseif (preg_match( '/^(\s*)([0-9.]+) = FunctionQuery\((.+)\), product of:/' ,$line ,$matches)) {
                $statementLevel = strlen( $matches[1] ) / 2;
                $value = floatval($matches[2]);
                $identifier = substr(md5($matches[3]) ,0 ,10);
                if ($statementLevel <= $level) {
                    if (!isset( $scores['bf'][$identifier])) {
                        $scores['bf'][$identifier] = 0;
                    }
                    $scores['bf'][$identifier] += $value;
                } elseif ( $value > 0 ) {
                    $scores['lost'] += $value;
                }
            } elseif ( $value > 0 ) {
                $scores['lost'] += $value;
            }
        }
        if ( $fieldFound ) {
            $this->calculateValues($scores ,$tie);
            $this->calculateClusters();
        }
    }

    protected function getCluster($field) {
        return $this->clusters[$field];
    }

    protected function prepareScores() {
        return [
                'all' => ['all' => 0, 'bf' => 0, 'bq' => 0, 'br' => 0],
                'bf' => ['all' => 0],
                'bq' => ['all' => 0],
                'br' => ['all' => 0, 'terms' => 0, 'phrase' => 0],
                'fields-all' => ['all' => 0],
                'fields-phrase' => ['all' => 0],
                'fields-terms' => ['all' => 0],
                'clusters-all' => ['all' => 0],
                'clusters-phrase' => ['all' => 0],
                'clusters-terms' => ['all' => 0],
                'lost' => 0
            ];
    }

    protected function calculateValues($scores, $tie) {
        $maxItem = ['fields-phrase' => '' , 'fields-terms' => ''];
        $maxValue = ['fields-phrase' => 0 , 'fields-terms'  => 0];
        foreach ( $scores as $area => $areaScores ) {
            if ( is_array( $areaScores ) ) {
                foreach ( $areaScores as $item => $value ) {
                    $item = str_replace( '_unstemmed' , '' , $item );
                    if ( strpos( $area , 'fields-' ) === 0 ) {
                        $suffix = ( strpos( $area , 'terms' ) !== false ) ? 'terms' : 'phrase';
                        $this->scores['all']['all'] += $tie * $value;
                        $this->scores['all']['br'] += $tie * $value;
                        $this->scores['br']['all'] += $tie * $value;
                        $this->scores['br'][$suffix] += $tie * $value;
                        $this->scores['fields-all']['all'] += $tie * $value;
                        $this->scores['fields-all'][$item] += $tie * $value;
                        $this->scores[$area][$item] += $tie * $value;
                        $this->scores[$area]['all'] += $tie * $value;
                        if ( $value > $maxValue[$area] ) {
                            $maxItem[$area] = $item;
                            $maxValue[$area] = $value;
                        }
                    } else {
                        $this->scores['all']['all'] += $value;
                        $this->scores[$area]['all'] += $value;
                        if ( !isset( $this->scores[$area][$item])) {
                            $this->scores[$area][$item] = 0;
                        }
                        $this->scores[$area][$item] += $value;
                        if (strpos( $area , 'bq-' ) === 0 ) {
                            if (!isset( $this->scoreStructure['bq'][$area])) {
                                $this->scoreStructure['bq'][$area] = 0;
                            }
                            $this->scores['all']['bq'] += $value;
                            $this->scores['bq']['all'] += $value;
                            if (!isset( $this->scores['bq'][$area])) {
                                $this->scores['bq'][$area] = 0;
                            }
                            $this->scores['bq'][$area] += $value;
                        } elseif ($area == 'bf') {
                            $this->scores['all']['bf'] += $value;
                            if (!isset( $this->minimumBoostingValues[$item] ) || $value < $this->minimumBoostingValues[$item]) {
                                $this->minimumBoostingValues[$item] = $value;
                            }
                        }
                    }
		}
            }
            if (strpos($area, 'fields-') === 0 && $maxValue[$area] > 0) {
                $term = $this->searchTerm[$maxItem[$area].$maxValue[$area]];
                $this->results[$area][$term] = ['field' => $maxItem[$area] , 'term' => $term , 'value' => $maxValue[$area]];
                $suffix = (strpos($area, 'terms') !== false) ? 'terms' : 'phrase';
                $value = (1 - $tie) * $maxValue[$area];
                $this->scores['all']['all'] += $value;
                $this->scores['all']['br'] += $value;
                $this->scores['br']['all'] += $value;
                $this->scores['br'][$suffix] += $value;
                $this->scores['fields-all'][$maxItem[$area]] += $value;
                $this->scores['fields-all']['all'] += $value;
                $this->scores[$area][$maxItem[$area]] += $value;
                $this->scores[$area]['all'] += $value;
            }
        }
    }

    protected function calculateClusters() {
        foreach (['-all' ,'-terms' ,'-phrase'] as $suffix) {
            foreach ($this->scores['fields'.$suffix] as $item => $value) {
                if ($item != 'all') {
                    if (!isset( $this->clusters[$item])) {
                        $this->clusters[$item] = 'others';
                    }
                    if (!isset( $this->scores['clusters'.$suffix][$this->clusters[$item]])) {
                        $this->scores['clusters'.$suffix][$this->clusters[$item]] = 0;
                    }
                    $this->scores['clusters'.$suffix][$this->clusters[$item]] += $value;
                    $this->scores['clusters'.$suffix]['all'] += $value;
                }
            }
            asort($this->scores['fields'.$suffix], SORT_NUMERIC);
            $this->scores['fields'.$suffix] = array_reverse( $this->scores['fields'.$suffix] );
        }
    }

}
