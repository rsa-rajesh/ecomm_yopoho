<?php

namespace Webkul\Payment\Payment;

use Illuminate\Support\Facades\Storage;

class Esewa extends Payment
{
    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'esewa';

    /**
     * Get redirect url.
     *
     * @return string
     */

    // here we will return the url of the esewa payment gateway
    public function getRedirectUrl() {}

    /**
     * Is available.
     *
     * @return bool
     */
    public function isAvailable()
    {
        if (! $this->cart) {
            $this->setCart();
        }

        // here we will check if the payment method is active and the cart has stockable items
        return $this->getConfigData('active') && $this->cart?->haveStockableItems();
    }

    // need to check on details like 
    // make that units of the product lock when the user clicks on the payment button
    // make sure the user can't change/remove the quantity of the product after clicking on the payment button
    // redirection after payment from esewa to the website will send the user to order complete/decline page

    /**
     * Get payment method image.
     *
     * @return array
     */
    public function getImage()
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : bagisto_asset('images/cash-on-delivery.png', 'shop');
    }
}
