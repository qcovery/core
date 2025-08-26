<?php

/*
 * This file is part of the Solarium package.
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code.
 */

namespace VolumesTab\Solarium;

use Solarium\Core\Configurable;
use Solarium\Exception\UnexpectedValueException;

/**
 * Class for describing an endpoint.
 */
class Endpoint extends \Solarium\Core\Client\Endpoint
{
    /**
     * Get the V1 base url for all requests.
     *
     * Based on host, path, port and core options.
     *
     * @throws UnexpectedValueException
     *
     * @return string
     */
    public function getCoreBaseUri(): string
    {
        $uri = $this->getServerUri();
        $core = $this->getCore();

        if ($core) {
            // eWW: removed "/solr" to be used with  findex
            $uri .= $core.'/';
        } else {
            throw new UnexpectedValueException('No core set.');
        }

        return $uri;
    }
}
