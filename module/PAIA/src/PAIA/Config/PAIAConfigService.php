<?
namespace PAIA\Config;

use Zend\Session\Container;

class PAIAConfigService {

    private $session;

    private $isil;

    private $paiaConfig;

    public function __construct($sessionManager = null) {
        if ($sessionManager) {
            $this->session = new Container('PAIA', $sessionManager);
        }
        $this->paiaConfig = parse_ini_file(realpath(getenv('VUFIND_LOCAL_DIR') . '/config/vufind/PAIA.ini'), true);
    }

    public function getPaiaGlobalKey() {
        foreach (array_keys($this->paiaConfig) as $arrayKey) {
            if (stristr($arrayKey, 'Global')) {
                if (isset($paiaConfig[$arrayKey]['isil'])) {
                    if ($this->paiaConfig[$arrayKey]['isil'] == $this->getIsil()) {
                        return $arrayKey;
                    }
                }
            }
        }
        return 'Global';
    }

    public function hasMultipleLoginSources() {
        $count = 0;
        foreach (array_keys($this->paiaConfig) as $arrayKey) {
            if (stristr($arrayKey, 'Global')) {
                $count++;
            }
        }
        if ($count > 1) {
            return true;
        }
        return false;
    }

    public function getMultipleLoginSources() {
        $result = [];
        foreach (array_keys($this->paiaConfig) as $arrayKey) {
            if (stristr($arrayKey, 'Global')) {
                $result[] = $this->paiaConfig[$arrayKey];
            }
        }
        return $result;
    }

    public function getIsil() {
        return $this->session->offsetGet('PAIAisil');
    }
}

?>