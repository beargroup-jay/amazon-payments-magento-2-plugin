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
use Amazon\Payment\Gateway\Helper\ApiHelper;
use Magento\Framework\Exception\LocalizedException;

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
     * @var ApiHelper
     */
    private $_apiHelper;

    /**
     * CaptureRequest constructor.
     * @param ConfigProvider $config
     * @param Data $coreHelper
     * @param ProductMetadata $productMetadata
     * @param ApiHelper $apiHelper
     */
    public function __construct(
        ConfigProvider $config,
        Data $coreHelper,
        ProductMetaData $productMetadata,
        ApiHelper $apiHelper
    ) {
        $this->_config = $config;
        $this->_coreHelper = $coreHelper;
        $this->_productMetaData = $productMetadata;
        $this->_apiHelper = $apiHelper;
    }


    /**
     * @param array $buildSubject
     * @return array
     * @throws LocalizedException
     */
    public function build(array $buildSubject)
    {
        $data = [];

        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];

        $order = $paymentDO->getOrder();

        $this->_validateCurrency($order->getCurrencyCode());

        $this->_setReservedOrderId();

        $quote = $this->_apiHelper->getQuote();
        $amazonId = $this->_apiHelper->getAmazonId();

        if ($order && $amazonId) {

            $data = [
                'amazon_order_reference_id' => $amazonId,
                'amount' => $order->getGrandTotalAmount(),
                'currency_code' => $order->getCurrencyCode(),
                'seller_order_id' => $order->getOrderIncrementId(),
                'store_name' => $quote->getStore()->getName(),
                'custom_information' =>
                    'Magento Version : ' . $this->_productMetaData->getVersion() . ' ' .
                    'Plugin Version : ' . $this->_coreHelper->getVersion(),
                'platform_id' => $this->_config->getPlatformId()
            ];
        }

        return $data;
    }

    /**
     * @throws \Exception
     */
    private function _setReservedOrderId()
    {
        $quote = $this->_apiHelper->getQuote();

        if (!$quote->getReservedOrderId()) {
            $quote
                ->reserveOrderId()
                ->save();
        }

    }

    /**
     * @param $code
     * @throws LocalizedException
     */
    private function _validateCurrency($code)
    {
        if ($this->_coreHelper->getCurrencyCode() !== $code) {
            throw new LocalizedException(__('The currency selected is not supported by Amazon Pay'));
        }
    }

}

