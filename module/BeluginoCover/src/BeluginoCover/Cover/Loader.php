<?php
/**
 * Book Cover Generator
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2007.
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
 * @package  Cover_Generator
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/configuration:external_content Wiki
 */
namespace BeluginoCover\Cover;

use VuFind\Content\Covers\PluginManager as ApiManager;
use VuFindCode\ISBN;

/**
 * Book Cover Generator
 *
 * @category VuFind
 * @package  Cover_Generator
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/configuration:external_content Wiki
 */
class Loader extends \VuFind\Cover\Loader
{
    /**
     * belugino configuration
     */
    protected $belugaConfig;

    /**
     * Constructor
     *
     * @param \Zend\Config\Config     $config      VuFind configuration
     * @param ApiManager              $manager     Plugin manager for API handlers
     * @param \VuFindTheme\ThemeInfo  $theme       VuFind theme tools
     * @param \VuFindHttp\HttpService $httpService HTTP client factory
     * @param string                  $baseDir     Directory to store downloaded
     * images (set to system temp dir if not otherwise specified)
     */
    public function __construct($config, ApiManager $manager,
        \VuFindTheme\ThemeInfo $theme, \VuFindHttp\HttpService $httpService,
        $baseDir = null
    ) {
        $this->setThemeInfo($theme);
        $this->config = $config;
        $this->configuredFailImage = isset($config->Content->noCoverAvailableImage)
            ? $config->Content->noCoverAvailableImage : null;
        $this->apiManager = $manager;
        $this->httpService = $httpService;
        $this->baseDir = (null === $baseDir)
            ? rtrim(sys_get_temp_dir(), '\\/') . '/covers'
            : rtrim($baseDir, '\\/');

        $this->belugaConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/BelugaConfig.ini'), true);
    }


    /**
     * Load an image given an ISBN and/or content type.
     *
     * @param array $settings Array of settings used to calculate a cover; may
     * contain any or all of these keys: 'isbn' (ISBN), 'size' (requested size),
     * 'type' (content type), 'title' (title of book, for dynamic covers), 'author'
     * (author of book, for dynamic covers), 'callnumber' (unique ID, for dynamic
     * covers), 'issn' (ISSN), 'oclc' (OCLC number), 'upc' (UPC number).
     *
     * @return void
     */
    public function loadImage($settings = [])
    {
        // Load settings from legacy function parameters if they are not passed
        // in as an array:
        $settings = is_array($settings)
            ? array_merge($this->getDefaultSettings(), $settings)
            : $this->getImageSettingsFromLegacyArgs(func_get_args());

        // Store sanitized versions of some parameters for future reference:
        $this->storeSanitizedSettings($settings);

        // Display a fail image unless our parameters pass inspection and we
        // are able to display an ISBN or content-type-based image.
        if (!in_array($this->size, $this->validSizes)) {
            $this->loadUnavailable();
        } elseif (!$this->fetchFromAPI()
            && !$this->fetchFromContentType()
        ) {
            if (isset($this->belugaConfig['belugino'])) {
                if (isset($settings['format'])) {
                    $format = str_ireplace(' ', '', $settings['format']);
                    $format = strtolower($format);
                    if ($im = imagecreate(64, 64)) {
                        $beluginoFile = APPLICATION_PATH . '/themes/belugax/css/belugino.css';
                        $beluginoParser = new \Sabberworm\CSS\Parser(file_get_contents($beluginoFile));
                        $beluginoCss = $beluginoParser->parse();

                        $belugino = [];
                        if (is_array($this->belugaConfig)) {
                            foreach ($this->belugaConfig['belugino'] as $key => $value) {
                                $key = str_ireplace(' ', '', $key);
                                $key = strtolower($key);
                                $belugino[$key] = $value;
                            }
                        }

                        $formatString = '';
                        foreach ($beluginoCss->getAllDeclarationBlocks() as $declarationBlock) {
                            $foundBeluginoClass = false;
                            foreach ($declarationBlock->getSelectors() as $selector) {
                                if ($selector->getSelector() == '.'.$belugino[$format].':before') {
                                    $foundBeluginoClass = true;
                                }
                            }
                            if ($foundBeluginoClass) {
                                foreach ($declarationBlock->getRules() as $rule) {
                                    if ($rule->getRule() == 'content') {
                                        $formatString = $rule->getValue()->getString();
                                    }
                                }
                            }
                        }

                        $bg = imagecolorallocate($im, 255, 255, 255);
                        $textcolor = imagecolorallocate($im, 0, 0, 0);

                        imagettftext($im, 64,0,-10,72, $textcolor, APPLICATION_PATH . '/themes/belugax/css/fonts/belugino.ttf', $formatString);

                        imageAlphaBlending($im, true);
                        imageSaveAlpha($im, true);

                        ob_start();
                        imagepng($im);
                        $img = ob_get_contents();
                        ob_end_clean();

                        imagedestroy($im);
                        $this->image = $img;
                        $this->contentType = 'image/png';
                    } else {
                        $this->loadUnavailable();
                    }
                }
            } else {
                if ($this->generator) {
                    $this->generator->setOptions($this->getCoverGeneratorSettings());
                    $this->image = $this->generator->generate(
                        $settings['title'], $settings['author'], $settings['callnumber']
                    );
                    $this->contentType = 'image/png';
                } else {
                    $this->loadUnavailable();
                }
            }
        }
    }
}
