<?php

namespace IMS;

class Cart extends \VuFind\Cart
{
    const CART_COOKIE_IMS_ID =  'vufind_cart_ims_id';
    const CART_COOKIE_IMS_URL =  'vufind_cart_ims_url';

    public function isImsActive() {
        if($this->cookieManager->get(self::CART_COOKIE_IMS_ID) && $this->cookieManager->get(self::CART_COOKIE_IMS_URL)) {
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

