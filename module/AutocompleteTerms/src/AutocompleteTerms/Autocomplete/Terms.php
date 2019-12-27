<?php
/**
 * Solr Autocomplete Module
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
 * @package  Autocomplete
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Chris Hallberg <challber@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:autosuggesters Wiki
 */
namespace AutocompleteTerms\Autocomplete;

/**
 * Solr Autocomplete Module
 *
 * This class provides suggestions by using the local Solr index.
 *
 * @category VuFind
 * @package  Autocomplete
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:autosuggesters Wiki
 */
class Terms implements \VuFind\Autocomplete\AutocompleteInterface
{
    /**
     * Autocomplete handler
     *
     * @var string
     */
    protected $handler;

    /**
     * Solr field to use for display
     *
     * @var string
     */
    protected $displayField;

    /**
     * Default Solr display field if none is configured
     *
     * @var string
     */
    protected $defaultDisplayField = 'title';

    /**
     * Solr field to use for sorting
     *
     * @var string
     */
    protected $sortField;

    /**
     * Filters to apply to Solr search
     *
     * @var array
     */
    protected $filters;

    /**
     * Search object family to use
     *
     * @var string
     */
    protected $searchClassId = 'Solr';

    /**
     * Search results object
     *
     * @var \VuFind\Search\Base\Results
     */
    protected $searchObject;

    /**
     * Results plugin manager
     *
     * @var \VuFind\Search\Results\PluginManager
     */
    protected $resultsManager;

    protected $config;

    /**
     * Constructor
     *
     * @param \VuFind\Search\Results\PluginManager $results Results plugin manager
     */
    public function __construct(\VuFind\Search\Results\PluginManager $results, $config)
    {
        $this->resultsManager = $results;
        $this->config = $config;
    }

    /**
     * Set parameters that affect the behavior of the autocomplete handler.
     * These values normally come from the search configuration file.
     *
     * @param string $params Parameters to set
     *
     * @return void
     */
    public function setConfig($params)
    {
        // Save the basic parameters:
        $params = explode(':', $params);
        $this->handler = (isset($params[0]) && !empty($params[0])) ?
            $params[0] : null;
        $this->displayField = (isset($params[1]) && !empty($params[1])) ?
            explode(',', $params[1]) : [$this->defaultDisplayField];
        $this->sortField = (isset($params[2]) && !empty($params[2])) ?
            $params[2] : null;
        $this->filters = [];
        if (count($params) > 3) {
            for ($x = 3; $x < count($params); $x += 2) {
                if (isset($params[$x + 1])) {
                    $this->filters[] = $params[$x] . ':' . $params[$x + 1];
                }
            }
        }

        // Set up the Search Object:
        $this->initSearchObject();
    }

    /**
     * Add filters (in addition to the configured ones)
     *
     * @param array $filters Filters to add
     *
     * @return void
     */
    public function addFilters($filters)
    {
        $this->filters += $filters;
    }

    /**
     * Initialize the search object used for finding recommendations.
     *
     * @return void
     */
    protected function initSearchObject()
    {
        // Build a new search object:
        $this->searchObject = $this->resultsManager->get($this->searchClassId);
        $this->searchObject->getOptions()->spellcheckEnabled(false);
    }

    /**
     * Process the user query to make it suitable for a Solr query.
     *
     * @param string $query Incoming user query
     *
     * @return string       Processed query
     */
    protected function mungeQuery($query)
    {
        // Modify the query so it makes a nice, truncated autocomplete query:
        $forbidden = [':', '(', ')', '*', '+', '"'];
        $query = str_replace($forbidden, " ", $query);
        if (substr($query, -1) != " ") {
            $query .= "*";
        }
        return $query;
    }

    /**
     * This method returns an array of strings matching the user's query for
     * display in the autocomplete box.
     *
     * @param string $query The user query
     *
     * @return array        The suggestions for the provided query
     */
    public function getSuggestions($query)
    {
        if (!is_object($this->searchObject)) {
            throw new \Exception('Please set configuration first.');
        }

        try {
            $this->searchObject->getParams()->setBasicSearch(
                $this->mungeQuery($query), $this->handler
            );
            $this->searchObject->getParams()->setSort($this->sortField);
            foreach ($this->filters as $current) {
                $this->searchObject->getParams()->addFilter($current);
            }

            // Perform the search:
            $searchResults = $this->searchObject->getResults();

            $spellingSuggestions = $this->searchObject->getSpellingSuggestions();

            // Build the recommendation list -- first we'll try with exact matches;
            // if we don't get anything at all, we'll try again with a less strict
            // set of rules.
            $results = $this->getSuggestionsFromSearch($searchResults, $query, true);
            if (empty($results)) {
                $results = $this->getSuggestionsFromSearch(
                    $searchResults, $query, false
                );
            }
        } catch (\Exception $e) {
            // Ignore errors -- just return empty results if we must.
        }
        return isset($results) ? array_unique($results) : [];
    }

    /**
     * Try to turn an array of record drivers into an array of suggestions.
     *
     * @param array  $searchResults An array of record drivers
     * @param string $query         User search query
     * @param bool   $exact         Ignore non-exact matches?
     *
     * @return array
     */
    protected function getSuggestionsFromSearch($searchResults, $query, $exact)
    {
        /*

        Array
            (
                [author_variant] => Array
                    (
                        [0] => j n jn
                    )

                [matchkey_str] => book:9783570010914:2008----
                [publishDate] => Array
                    (
                        [0] => 2008
                    )

                [allfields]           => 9783570010914 978-3-570-01091-4 3570010910 3-570-01091-0 0078_0099339 1178_KAT_D-0047206 OLDP870709313 ger GBVCP Neffe, Jürgen Darwin das Abenteuer des Lebens Jürgen Neffe 5. Aufl München C. Bertelsmann 2008 544 S Fototaf., Kt Naturwissenschaften gnd Biographien gnd Darwin, Charles gnd Naturwissenschaften Biographien DE-101 Darwin, Charles DE-601 GBV_ILN_475 SYSFLAG_1 GBV_OEVK GBV_ILN_478 BO 475 01 1 1360765301 Stadt/E R 11 u 20180305 1178 n 30-03-09 478 01 1381900798 N 910 Dar u 20180703 0078 n 05-03-09 475 01 20180305 478 01 20180703
                [spelling]            => 9783570010914 978-3-570-01091-4 3570010910 3-570-01091-0 0078_0099339 1178_KAT_D-0047206 OLDP870709313 ger GBVCP Neffe, Jürgen Darwin das Abenteuer des Lebens Jürgen Neffe 5. Aufl München C. Bertelsmann 2008 544 S Fototaf., Kt Naturwissenschaften gnd Biographien gnd Darwin, Charles gnd Naturwissenschaften Biographien DE-101 Darwin, Charles DE-601 GBV_ILN_475 SYSFLAG_1 GBV_OEVK GBV_ILN_478 BO 475 01 1 1360765301 Stadt/E R 11 u 20180305 1178 n 30-03-09 478 01 1381900798 N 910 Dar u 20180703 0078 n 05-03-09 475 01 20180305 478 01 20180703
                [allfields_unstemmed] => 9783570010914 978-3-570-01091-4 3570010910 3-570-01091-0 0078_0099339 1178_KAT_D-0047206 OLDP870709313 ger GBVCP Neffe, Jürgen Darwin das Abenteuer des Lebens Jürgen Neffe 5. Aufl München C. Bertelsmann 2008 544 S Fototaf., Kt Naturwissenschaften gnd Biographien gnd Darwin, Charles gnd Naturwissenschaften Biographien DE-101 Darwin, Charles DE-601 GBV_ILN_475 SYSFLAG_1 GBV_OEVK GBV_ILN_478 BO 475 01 1 1360765301 Stadt/E R 11 u 20180305 1178 n 30-03-09 478 01 1381900798 N 910 Dar u 20180703 0078 n 05-03-09 475 01 20180305 478 01 20180703
                [allfieldsGer]        => 9783570010914 978-3-570-01091-4 3570010910 3-570-01091-0 0078_0099339 1178_KAT_D-0047206 OLDP870709313 ger GBVCP Neffe, Jürgen Darwin das Abenteuer des Lebens Jürgen Neffe 5. Aufl München C. Bertelsmann 2008 544 S Fototaf., Kt Naturwissenschaften gnd Biographien gnd Darwin, Charles gnd Naturwissenschaften Biographien DE-101 Darwin, Charles DE-601 GBV_ILN_475 SYSFLAG_1 GBV_OEVK GBV_ILN_478 BO 475 01 1 1360765301 Stadt/E R 11 u 20180305 1178 n 30-03-09 478 01 1381900798 N 910 Dar u 20180703 0078 n 05-03-09 475 01 20180305 478 01 20180703
                [allfieldsSound]      => 9783570010914 978-3-570-01091-4 3570010910 3-570-01091-0 0078_0099339 1178_KAT_D-0047206 OLDP870709313 ger GBVCP Neffe, Jürgen Darwin das Abenteuer des Lebens Jürgen Neffe 5. Aufl München C. Bertelsmann 2008 544 S Fototaf., Kt Naturwissenschaften gnd Biographien gnd Darwin, Charles gnd Naturwissenschaften Biographien DE-101 Darwin, Charles DE-601 GBV_ILN_475 SYSFLAG_1 GBV_OEVK GBV_ILN_478 BO 475 01 1 1360765301 Stadt/E R 11 u 20180305 1178 n 30-03-09 478 01 1381900798 N 910 Dar u 20180703 0078 n 05-03-09 475 01 20180305 478 01 20180703
                [format_phy_str_mv] => Array
                    (
                        [0] => Book
                    )

                [building] => Array
                    (
                        [0] => 475
                        [1] => 478
                    )

                [institution] => Array
                    (
                        [0] => findex.gbv.de
                    )

                [topic_facet] => Array
                    (
                        [0] => Naturwissenschaften
                        [1] => Biographien
                        [2] => Darwin, Charles
                    )

                [isfreeaccess_bool] => false
                [id] => OEVK1328608808
                [signature_iln] => Array
                    (
                        [0] => 475:R 11
                        [1] => 478:N 910 Dar
                    )

                [signature_iln_str_mv] => Array
                    (
                        [0] => 475:R 11
                        [1] => 478:N 910 Dar
                    )

                [signature_iln_scis_mv] => Array
                    (
                        [0] => 475:R 11
                        [1] => 478:N 910 Dar
                    )

                [genre_facet] => Array
                    (
                        [0] => Naturwissenschaften
                        [1] => Biographien
                        [2] => Darwin, Charles
                    )

                [standort_str_mv] => Array
                    (
                        [0] => Stadt/E
                    )

                [callnumber-first] => R - Medicine
                [author] => Neffe, Jürgen
                [spellingShingle] => Array
                    (
                        [0] => Neffe, Jürgen
                        [1] => misc Naturwissenschaften
                        [2] => misc Biographien
                        [3] => misc Darwin, Charles
                        [4] => Darwin das Abenteuer des Lebens
                    )

                [authorStr] => Neffe, Jürgen
                [standort_iln_str_mv] => Array
                    (
                        [0] => 475:Stadt/E
                    )

                [format] => Array
                    (
                        [0] => Book
                    )

                [delete_txt_mv] => Array
                    (
                        [0] => keep
                    )

                [author_role] => Array
                    (
                        [0] =>
                    )

                [collection] => Array
                    (
                        [0] => ÖVK
                    )

                [publishPlace] => Array
                    (
                        [0] => München
                    )

                [remote_str] => false
                [abrufzeichen_iln_str_mv] => Array
                    (
                        [0] => 475@20180305
                        [1] => 478@20180703
                    )

                [abrufzeichen_iln_scis_mv] => Array
                    (
                        [0] => 475@20180305
                        [1] => 478@20180703
                    )

                [last_changed_iln_str_mv] => Array
                    (
                        [0] => 475@30-03-09
                        [1] => 478@05-03-09
                    )

                [illustrated] => Not Illustrated
                [topic_title] => Array
                    (
                        [0] => Darwin das Abenteuer des Lebens Jürgen Neffe
                        [1] => Naturwissenschaften
                        [2] => Biographien
                        [3] => DE-101
                        [4] => Darwin, Charles
                        [5] => DE-601
                    )

                [publisher] => Array
                    (
                        [0] => C. Bertelsmann
                    )

                [publisherStr] => Array
                    (
                        [0] => C. Bertelsmann
                    )

                [topic] => Array
                    (
                        [0] => misc Naturwissenschaften
                        [1] => misc Biographien
                        [2] => misc Darwin, Charles
                    )

                [topic_unstemmed] => Array
                    (
                        [0] => misc Naturwissenschaften
                        [1] => misc Biographien
                        [2] => misc Darwin, Charles
                    )

                [topic_browse] => Array
                    (
                        [0] => misc Naturwissenschaften
                        [1] => misc Biographien
                        [2] => misc Darwin, Charles
                    )

                [format_facet] => Array
                    (
                        [0] => Bücher
                        [1] => Gedruckte Bücher
                    )

                [standort_txtP_mv] => Array
                    (
                        [0] => Stadt/E
                    )

                [signature] => Array
                    (
                        [0] => R 11
                        [1] => N 910 Dar
                    )

                [signature_str_mv] => Array
                    (
                        [0] => R 11
                        [1] => N 910 Dar
                    )

                [isbn] => Array
                    (
                        [0] => 9783570010914
                        [1] => 3570010910
                    )

                [edition] => 5. Aufl
                [isfreeaccess_txt] => false
                [title] => Array
                    (
                        [0] => Darwin das Abenteuer des Lebens
                    )

                [title_full] => Array
                    (
                        [0] => Darwin das Abenteuer des Lebens Jürgen Neffe
                    )

                [fullrecord] => 01180nam a2200409 c 4500001001500000003000700015005001700022008004100039020003700080020003000117024001700147024002300164024001800187040001500205100001900220245005200239250001200291264003500303300002400338655002900362655002100391655002500412689002400437689001600461689001100477689002000488689001100508912001600519912001400535912001300549912001600562951000700578980007400585980006700659995002200726995002200748#30;OEVK1328608808#30;DE-601#30;20180809134032.0#30;120721s2008                  000 0 und d#30;  #31;a9783570010914#31;9978-3-570-01091-4#30;  #31;a3570010910#31;93-570-01091-0#30;8 #31;a0078_0099339#30;8 #31;a1178_KAT_D-0047206#30;8 #31;aOLDP870709313#30;  #31;bger#31;cGBVCP#30;1 #31;aNeffe, Jürgen#30;10#31;aDarwin#31;bdas Abenteuer des Lebens#31;cJürgen Neffe#30;  #31;a5. Aufl#30;31#31;aMünchen#31;bC. Bertelsmann#31;c2008#30;  #31;a544 S#31;bFototaf., Kt#30; 7#31;aNaturwissenschaften#31;2gnd#30; 7#31;aBiographien#31;2gnd#30; 7#31;aDarwin, Charles#31;2gnd#30;00#31;aNaturwissenschaften#30;01#31;aBiographien#30;0 #31;5DE-101#30;00#31;aDarwin, Charles#30;0 #31;5DE-601#30;  #31;aGBV_ILN_475#30;  #31;aSYSFLAG_1#30;  #31;aGBV_OEVK#30;  #31;aGBV_ILN_478#30;  #31;aBO#30;  #31;2475#31;101#31;a1#31;b1360765301#31;fStadt/E#31;dR 11#31;eu#31;h20180305#31;x1178#31;yn#31;z30-03-09#30;  #31;2478#31;101#31;b1381900798#31;dN 910 Dar#31;eu#31;h20180703#31;x0078#31;yn#31;z05-03-09#30;  #31;2475#31;101#31;a20180305#30;  #31;2478#31;101#31;a20180703#30;#29;
                [author_sort] => Neffe, Jürgen
                [callnumber-first-code] => R
                [isOA_bool] => false
                [recordtype] => marc
                [genre] => Array
                    (
                        [0] => Naturwissenschaften gnd
                        [1] => Biographien gnd
                        [2] => Darwin, Charles gnd
                    )

                [publishDateSort] => 2008
                [selectkey] => Array
                    (
                        [0] => 475:n
                        [1] => 478:n
                    )

                [physical] => Array
                    (
                        [0] => 544 S Fototaf., Kt
                    )

                [author-letter] => Array
                    (
                        [0] => Neffe, Jürgen
                    )

                [format_se] => Array
                    (
                        [0] => Bücher
                    )

                [title_sub] => Array
                    (
                        [0] => das Abenteuer des Lebens
                    )

                [title_sort] => darwindas abenteuer des lebens
                [title_auth] => Array
                    (
                        [0] => Darwin das Abenteuer des Lebens
                    )

                [title_short] => Array
                    (
                        [0] => Darwin
                    )

                [collection_details] => Array
                    (
                        [0] => GBV_ILN_475
                        [1] => SYSFLAG_1
                        [2] => GBV_OEVK
                        [3] => GBV_ILN_478
                    )

                [ausleihindikator_str_mv] => Array
                    (
                        [0] => 475:u
                        [1] => 478:u
                    )

                [remote_bool] => false
                [isOA_txt] => false
                [hochschulschrift_bool] => false
                [callnumber-a] => R 11
                [up_date] => 2019-09-29T01:24:33.505Z
                [_version_] => 1645971005511827456
                [fullrecord_marcxml] => <?xml version="1.0" encoding="UTF-8"?><collection xmlns="http://www.loc.gov/MARC21/slim"><record><leader>01180nam a2200409 c 4500</leader><controlfield tag="001">OEVK1328608808</controlfield><controlfield tag="003">DE-601</controlfield><controlfield tag="005">20180809134032.0</controlfield><controlfield tag="008">120721s2008                  000 0 und d</controlfield><datafield tag="020" ind1=" " ind2=" "><subfield code="a">9783570010914</subfield><subfield code="9">978-3-570-01091-4</subfield></datafield><datafield tag="020" ind1=" " ind2=" "><subfield code="a">3570010910</subfield><subfield code="9">3-570-01091-0</subfield></datafield><datafield tag="024" ind1="8" ind2=" "><subfield code="a">0078_0099339</subfield></datafield><datafield tag="024" ind1="8" ind2=" "><subfield code="a">1178_KAT_D-0047206</subfield></datafield><datafield tag="024" ind1="8" ind2=" "><subfield code="a">OLDP870709313</subfield></datafield><datafield tag="040" ind1=" " ind2=" "><subfield code="b">ger</subfield><subfield code="c">GBVCP</subfield></datafield><datafield tag="100" ind1="1" ind2=" "><subfield code="a">Neffe, Jürgen</subfield></datafield><datafield tag="245" ind1="1" ind2="0"><subfield code="a">Darwin</subfield><subfield code="b">das Abenteuer des Lebens</subfield><subfield code="c">Jürgen Neffe</subfield></datafield><datafield tag="250" ind1=" " ind2=" "><subfield code="a">5. Aufl</subfield></datafield><datafield tag="264" ind1="3" ind2="1"><subfield code="a">München</subfield><subfield code="b">C. Bertelsmann</subfield><subfield code="c">2008</subfield></datafield><datafield tag="300" ind1=" " ind2=" "><subfield code="a">544 S</subfield><subfield code="b">Fototaf., Kt</subfield></datafield><datafield tag="655" ind1=" " ind2="7"><subfield code="a">Naturwissenschaften</subfield><subfield code="2">gnd</subfield></datafield><datafield tag="655" ind1=" " ind2="7"><subfield code="a">Biographien</subfield><subfield code="2">gnd</subfield></datafield><datafield tag="655" ind1=" " ind2="7"><subfield code="a">Darwin, Charles</subfield><subfield code="2">gnd</subfield></datafield><datafield tag="689" ind1="0" ind2="0"><subfield code="a">Naturwissenschaften</subfield></datafield><datafield tag="689" ind1="0" ind2="1"><subfield code="a">Biographien</subfield></datafield><datafield tag="689" ind1="0" ind2=" "><subfield code="5">DE-101</subfield></datafield><datafield tag="689" ind1="0" ind2="0"><subfield code="a">Darwin, Charles</subfield></datafield><datafield tag="689" ind1="0" ind2=" "><subfield code="5">DE-601</subfield></datafield><datafield tag="912" ind1=" " ind2=" "><subfield code="a">GBV_ILN_475</subfield></datafield><datafield tag="912" ind1=" " ind2=" "><subfield code="a">SYSFLAG_1</subfield></datafield><datafield tag="912" ind1=" " ind2=" "><subfield code="a">GBV_OEVK</subfield></datafield><datafield tag="912" ind1=" " ind2=" "><subfield code="a">GBV_ILN_478</subfield></datafield><datafield tag="951" ind1=" " ind2=" "><subfield code="a">BO</subfield></datafield><datafield tag="980" ind1=" " ind2=" "><subfield code="2">475</subfield><subfield code="1">01</subfield><subfield code="a">1</subfield><subfield code="b">1360765301</subfield><subfield code="f">Stadt/E</subfield><subfield code="d">R 11</subfield><subfield code="e">u</subfield><subfield code="h">20180305</subfield><subfield code="x">1178</subfield><subfield code="y">n</subfield><subfield code="z">30-03-09</subfield></datafield><datafield tag="980" ind1=" " ind2=" "><subfield code="2">478</subfield><subfield code="1">01</subfield><subfield code="b">1381900798</subfield><subfield code="d">N 910 Dar</subfield><subfield code="e">u</subfield><subfield code="h">20180703</subfield><subfield code="x">0078</subfield><subfield code="y">n</subfield><subfield code="z">05-03-09</subfield></datafield><datafield tag="995" ind1=" " ind2=" "><subfield code="2">475</subfield><subfield code="1">01</subfield><subfield code="a">20180305</subfield></datafield><datafield tag="995" ind1=" " ind2=" "><subfield code="2">478</subfield><subfield code="1">01</subfield><subfield code="a">20180703</subfield></datafield></record></collection>

                [score] => 750
            )


        */

        $results = $this->getTerms($searchResults, $query);

        if (empty($results)) {
            $results = $this->getTerms($searchResults, $query, true);
        }

        return $results;
    }

    private function getTerms($searchResults, $query, $allFields = false) {
        $results = [];
        foreach ($searchResults as $object) {
            $current = $object->getRawData();
            $matches = [];

            $searchContents = '';
            if (!$allFields) {
                if (isset($current[strtolower($this->handler)])) {
                    $searchContents = $current[strtolower($this->handler)];
                }
            } else {
                $searchContents = $current['allfields'];
            }

            if (!is_array($searchContents)) {
                $searchContents = [$searchContents];
            }

            foreach ($searchContents as $searchContent) {
                $completeFields = false;
                if (isset($this->config['Autocomplete_Types_Options']['CompleteFields'])) {
                    foreach ($this->config['Autocomplete_Types_Options']['CompleteFields'] as $completeField) {
                        if ($completeField == $this->handler) {
                            $completeFields = true;
                        }
                    }
                }

                if (!$completeFields) {
                    preg_match_all('~\b' . $query . '[a-z]*\b~i', $searchContent, $matches);
                    foreach ($matches as $terms) {
                        foreach ($terms as $term) {
                            $results[] = strtolower($term);
                        }
                    }
                } else {
                    if (stristr(strtolower($searchContent), $query)) {
                        $results[] = strtolower($searchContent);
                    }
                }
            }
        }

        array_unique($results);
        sort($results);

        return $results;
    }

    /**
     * Given the values from a Solr field and the user's search query, pick the best
     * match to display as a recommendation.
     *
     * @param array|string $value Field value (or array of field values)
     * @param string       $query User search query
     * @param bool         $exact Ignore non-exact matches?
     *
     * @return bool|string        String to use as recommendation, or false if
     * no appropriate value was found.
     */
    protected function pickBestMatch($value, $query, $exact)
    {
        // By default, assume no match:
        $bestMatch = false;

        // Different processing for arrays vs. non-arrays:
        if (is_array($value) && !empty($value)) {
            // Do any of the values within this multi-valued array match the
            // query?  Try to find the closest available match.
            foreach ($value as $next) {
                if ($this->matchQueryTerms($next, $query)) {
                    $bestMatch = $next;
                    break;
                }
            }

            // If we didn't find an exact match, use the first value unless
            // we have the "precise matches only" property set, in which case
            // we don't want to use any of these values.
            if (!$bestMatch && !$exact) {
                $bestMatch = $value[0];
            }
        } else {
            // If we have a single value, we will use it if we're in non-strict
            // mode OR if we're in strict mode and it actually matches.
            if (!$exact || $this->matchQueryTerms($value, $query)) {
                $bestMatch = $value;
            }
        }
        return $bestMatch;
    }

    /**
     * Set the display field list.  Useful for child classes.
     *
     * @param array $new Display field list.
     *
     * @return void
     */
    protected function setDisplayField($new)
    {
        $this->displayField = $new;
    }

    /**
     * Set the sort field list.  Useful for child classes.
     *
     * @param string $new Sort field list.
     *
     * @return void
     */
    protected function setSortField($new)
    {
        $this->sortField = $new;
    }

    /**
     * Return true if all terms in the query occurs in the field data string.
     *
     * @param string $data  The data field returned from solr
     * @param string $query The query string entered by the user
     *
     * @return bool
     */
    protected function matchQueryTerms($data, $query)
    {
        $terms = preg_split("/\s+/", $query);
        foreach ($terms as $term) {
            if (stripos($data, $term) === false) {
                return false;
            }
        }
        return true;
    }
}
