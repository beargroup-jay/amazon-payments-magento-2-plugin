<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Amazon\Payment\Gateway\Config;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const CODE = 'amazon_payment';

    const PLATFORM_ID = 'A2ZAYEJU54T1BM';

    const AMAZON_ACTIVE = 'payment/amazon_payment/active';

    /**
     * Check whether payment method can be used
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     * @deprecated 100.2.0
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        $storeId = 0;

        if ($quote && $quote->getStoreId()) {
            $storeId = $quote->getStoreId();
        }

        if (!$this->getValue(self::AMAZON_ACTIVE, $storeId)) {
            return false;
        }

        return true;
    }
}
