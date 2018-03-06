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

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Framework\App\ProductMetadata;
use Amazon\Payment\Gateway\Helper\SubjectReader;
use Amazon\Core\Helper\Data;

class AuthorizationRequest implements BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var ProductMetadata
     */
    private $productMetaData;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var Data
     */
    private $coreHelper;

    /**
     * AuthorizationRequest constructor.
     * @param ConfigInterface $config
     * @param ProductMetadata $productMetadata
     * @param SubjectReader $subjectReader
     * @param Data $coreHelper
     */
    public function __construct(
        ConfigInterface $config,
        ProductMetaData $productMetadata,
        SubjectReader $subjectReader,
        Data $coreHelper
    )
    {
        $this->config = $config;
        $this->coreHelper = $coreHelper;
        $this->productMetaData = $productMetadata;
        $this->subjectReader = $subjectReader;
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

        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDO->getOrder();

        $quote = $this->subjectReader->getQuote();

        if (!$quote->getReservedOrderId()) {
            try {
                $quote->reserveOrderId()->save();
            }
            catch(\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }

        $amazonId = $this->subjectReader->getAmazonId();

        if ($order && $amazonId) {

            $data = [
                'amazon_order_reference_id' => $amazonId,
                'amount' => $buildSubject['amount'],
                'currency_code' => $order->getCurrencyCode(),
                'seller_order_id' => $order->getOrderIncrementId(),
                'store_name' => $quote->getStore()->getName(),
                'custom_information' =>
                    'Magento Version : ' . $this->productMetaData->getVersion() . ' ' .
                    'Plugin Version : ' . $this->coreHelper->getVersion(),
                'platform_id' => $this->config::PLATFORM_ID,
                'request_payment_authorization' => true
            ];
        }

        return $data;
    }
}
