<?php
/**
 *
 */
namespace ListAdmin\View\Helper\ListAdmin;

class ListAdmin extends \Zend\View\Helper\AbstractHelper
{
    protected $config;

    /**
     *
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function isAdmin($user) {
        if (isset($this->config->ListAdmin->admins)) {
            foreach ($this->config->ListAdmin->admins as $adminId) {
                if ($user->username == $adminId) {
                    return true;
                }
            }
        }
        return false;
    }
}
