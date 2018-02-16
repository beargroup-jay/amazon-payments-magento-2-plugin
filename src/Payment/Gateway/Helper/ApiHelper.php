<?php
/**
 * Copyright 2016 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 *  http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Amazon\Payment\Gateway\Helper;

use Magento\Checkout\Model\Session;
use Amazon\Payment\Api\Data\QuoteLinkInterfaceFactory;

/**
 * Class ApiHelper
 *
 * Consolidates commonly used calls
 *
 * @package Amazon\Payment\Gateway\Helper
 */
class ApiHelper
{

    /**
     * @var QuoteLinkInterfaceFactory
     */
    private $_quoteLinkFactory;

    /**
     * @var Session
     */
    private $_checkoutSession;

    /**
     * ApiHelper constructor.
     *
     * @param Session $checkoutSession
     * @param QuoteLinkInterfaceFactory $quoteLinkInterfaceFactory
     */
    public function __construct(
        Session $checkoutSession,
        QuoteLinkInterfaceFactory $quoteLinkInterfaceFactory
    )
    {
        $this->_quoteLinkFactory = $quoteLinkInterfaceFactory;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Gets quote from current checkout session and returns store ID
     * @return int
     */
    public function getStoreId()
    {
        $quote = $this->getQuote();

        return $quote->getStoreId();
    }

    /**
     * Get unique Amazon ID for order from custom table
     * @return mixed
     */
    public function getAmazonId()
    {
        $quoteLink = $this->getQuoteLink();

        return $quoteLink->getAmazonOrderReferenceId();
    }

    /**
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    /**
     * @return \Amazon\Payment\Model\QuoteLink
     */
    public function getQuoteLink()
    {
        $quote = $this->getQuote();

        $quoteLink = $this->_quoteLinkFactory->create();
        $quoteLink->load($quote->getId(), 'quote_id');

        return $quoteLink;
    }

}