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
namespace Amazon\Payment\Gateway\Request;

use Amazon\Payment\Model\Ui\ConfigProvider;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Framework\App\ProductMetadata;
use Amazon\Core\Helper\Data;
use Magento\Checkout\Model\Session;
use Amazon\Payment\Api\Data\QuoteLinkInterfaceFactory;

class CaptureRequest implements BuilderInterface
{
    /**
     * @var ConfigInterface|ConfigProvider
     */
    private $_config;

    /**
     * @var Data
     */
    private $_coreHelper;

    /**
     * @var ProductMetadata
     */
    private $_productMetaData;

    /**
     * @var Session
     */
    private $_checkoutSession;

    /**
     * @var QuoteLinkInterfaceFactory
     */
    private $_quoteLinkFactory;

    /**
     * CaptureRequest constructor.
     * @param ConfigProvider $config
     * @param Data $coreHelper
     * @param ProductMetadata $productMetadata
     * @param Session $checkoutSession
     * @param QuoteLinkInterfaceFactory $quoteLinkInterfaceFactory
     */
    public function __construct(
        ConfigProvider $config,
        Data $coreHelper,
        ProductMetaData $productMetadata,
        Session $checkoutSession,
        QuoteLinkInterfaceFactory $quoteLinkInterfaceFactory
    ) {
        $this->_config = $config;
        $this->_coreHelper = $coreHelper;
        $this->_productMetaData = $productMetadata;
        $this->_checkoutSession = $checkoutSession;
        $this->_quoteLinkFactory = $quoteLinkInterfaceFactory;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $data = [];

        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];

        $order = $paymentDO->getOrder();

        $quote = $this->_checkoutSession->getQuote();
        $amazonId = $this->getAmazonId($quote->getId());

        if ($order && $amazonId) {

            $data = [
                'amazon_order_reference_id' => $amazonId,
                'amount' => $order->getGrandTotalAmount(),
                'currency_code' => $order->getCurrencyCode(),
                'seller_order_id' => $order->getOrderIncrementId(),
                'store_name' => $quote->getStore()->getName(),
                'custom_information' =>
                    'Magento Version : ' . $this->_productMetaData->getVersion() . ' ' .
                    'Plugin Version : ' . $this->_coreHelper->getVersion()
                ,
                'platform_id' => $this->_config->getPlatformId()
            ];
        }

        return $data;
    }

    /**
     * Get unique Amazon ID for order from custom table
     * @param $quoteId
     * @return mixed
     */
    private function getAmazonId($quoteId)
    {
        $quoteLink = $this->_quoteLinkFactory->create();
        $quoteLink->load($quoteId, 'quote_id');

        return $quoteLink->getAmazonOrderReferenceId();
    }
}
