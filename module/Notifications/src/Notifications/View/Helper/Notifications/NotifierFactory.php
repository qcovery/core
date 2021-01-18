<?php
/**
 * Factory for RecordDriver view helper
 *
 */
namespace Notifications\View\Helper\Notifications;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * ConfigReader helper factory.
 *
 * @category BelugaConfig
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class NotifierFactory implements FactoryInterface
{
    /**
     *
     */
    public function __invoke(ContainerInterface $container, $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }
        return new $requestedName(
            $container->get('VuFind\Config')->get('config'), 
            $container->get('VuFind\Config')->get('notifications')
        );
    }
}
