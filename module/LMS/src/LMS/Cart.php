<?php

namespace LMS;

class Cart extends \VuFind\Cart
{
    const CART_COOKIE_LMS_ID =  'vufind_cart_lms_id';
    const CART_COOKIE_LMS_URL =  'vufind_cart_lms_url';

    public function isLmsActive() {
        if($this->cookieManager->get(self::CART_COOKIE_LMS_ID) && $this->cookieManager->get(self::CART_COOKIE_LMS_URL)) {
            return true;
        }
        return false;
    }

    /**
     * Initialize the cart model.
     *
     * @param array $cookies Current cookie values
     *
     * @return void
     */
    protected function init($cookies)
    {
        parent::init($cookies);
    }
}

