<?php
/**
 * Functions to add basic MARC-driven functionality to a record driver not already
 * powered by the standard index spec. Depends upon MarcReaderTrait.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2017.
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
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
namespace Resolver\Resolver\Driver;

/**
 * Functions to add basic MARC-driven functionality to a record driver not already
 * powered by the standard index spec. Depends upon MarcReaderTrait.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
trait OpenUrlMapTrait
{
    protected $openUrlMap = [
        'book' => [
            'rft_title' => 'title',
            'rft_date' => 'publicationDate',
            'rft_genre' => 'genre',
            'rft_btitle' => 'title',
            'rft_series' => 'series',
            'rft_au' => 'author',
            'rft_pub' => 'publisher',
            'rft_edition' => 'edition',
            'rft_isbn' => 'isbn'],
            'rft_format' => 'format',
        'article' => [
            'rft_atitle' => 'title',
            'rft_date' => 'date',
            'rft_genre' => 'genre',
            'rft_jtitle' => 'journaltitle',
            'rft_volume' => 'volume',
            'rft_issue' => 'issue',
            'rft_spage' => 'startpage',
            'rft_au' => 'author',
            'rft_issn' => 'issn',
            'rft_isbn' => 'isbn',
            'rft_format' => 'format',
            'rft_language' => 'language'],
        'journal' => [
            'rft_title' => 'title',
            'rft_creator' => 'author',
            'rft_pub' => 'publisher',
            'rft_format' => 'format',
            'rft_language' => 'language',
            'rft_issn' => 'issn'],
        'unknown' => [
            'rft_title' => 'title',
            'rft_date' => 'date',
            'rft_creator' => 'author',
            'rft_pub' => 'publisher',
            'rft_format' => 'format',
            'rft_language' => 'language']
        ];


    /**
     * Get an array of all ISBNs associated with the record (may be empty).
     *
     * @return array
     */
    public function mapOpenUrl($openUrl)
    {
        parse_str($openUrl, $openUrlArray);
        $format = strtolower($openUrlArray['rft_format']) ?? 'unknown';
        $openUrlMap = array_flip($this->openUrlMap[$format]);
        $mapped = [];
        foreach ($this->map as $key => $value) {
            if (isset($openUrlMap[$value])) {
                $mapped[$key] = $openUrlArray[$openUrlMap[$value]] ?? '';
            }
        }
        if (empty($openUrlMap['format'])) {
            $mapped['format'] = $format;
        }
        return $mapped;
    }
}
